<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use OCA\Sharing\ResponseDefinitions;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use OCP\Sharing\Exception\ShareConflictException;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareInvalidOperationParameterException;
use OCP\Sharing\Exception\ShareInvalidPropertiesException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\Exception\ShareOperationNotAllowedException;
use OCP\Sharing\IManager;
use OCP\Sharing\IShareFeature;
use OCP\Sharing\IShareFeatureFilter;
use OCP\Sharing\IShareFeatureModifyProperties;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareRecipientTypeSearch;
use OCP\Sharing\IShareSourceType;
use OCP\Sharing\Model\Share;
use OCP\Sharing\Model\ShareAccessContext;
use OCP\Sharing\Model\ShareOwner;
use OCP\Sharing\Model\ShareRecipient;
use OCP\Snowflake\ISnowflakeGenerator;
use RuntimeException;

// TODO: Add draft shares
// TODO: Add reshares
// TODO: Add listeners to remove recipients and sources when they are deleted

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 * @psalm-import-type SharingPartialShare from ResponseDefinitions
 */
class Manager implements IManager {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly Registry $registry,
	) {
	}

	public function completePartialShareData(array $share, string $owner): array {
		$share['id'] = Server::get(ISnowflakeGenerator::class)->nextId();
		$share['last_updated'] = $this->generateLastUpdated();
		$share['owner'] = (new ShareOwner($owner))->toAPI();
		return $share;
	}

	/**
	 * @return non-negative-int
	 */
	private function generateLastUpdated(): int {
		$time = (int)(microtime(true) * 1000);
		if ($time < 0) {
			throw new RuntimeException('Have you invented time travel?');
		}

		return $time;
	}

	/**
	 * For some reason rector always tries to add ShareRecipient[] as the return type and there is no way to stop it.
	 * @param ?class-string<IShareRecipientType> $recipientTypeClass
	 * @param non-empty-string $query
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @throws ShareInvalidOperationParameterException
	 */
	public function searchRecipients(?string $recipientTypeClass, string $query, int $limit, int $offset): array {
		$recipientTypes = $this->registry->getRecipientTypes();

		if ($recipientTypeClass !== null) {
			if (!isset($recipientTypes[$recipientTypeClass])) {
				throw new ShareInvalidOperationParameterException('The recipient type is not registered.');
			}

			$recipientTypes = [$recipientTypeClass => ($recipientTypes[$recipientTypeClass])];
		}

		$searchableRecipientTypes = array_values(array_filter(
			$recipientTypes,
			static fn (IShareRecipientType $recipientType): bool => $recipientType instanceof IShareRecipientTypeSearch,
		));

		return array_merge(...array_map(
			static fn (IShareRecipientTypeSearch $recipientType): array => $recipientType->searchRecipients($query, $limit, $offset),
			$searchableRecipientTypes,
		));
	}

	/**
	 * @throws ShareOperationNotAllowedException
	 */
	private function validateShareOwnerOperation(ShareAccessContext $accessContext, Share $share): void {
		if ($accessContext->force) {
			return;
		}

		if (!$accessContext->currentUser instanceof IUser) {
			throw new ShareOperationNotAllowedException();
		}

		if ($share->owner->userId !== $accessContext->currentUser->getUID()) {
			throw new ShareOperationNotAllowedException();
		}
	}

	public function insert(ShareAccessContext $accessContext, Share &$share): void {
		$this->connection->beginTransaction();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('sharing_share')
			->values([
				'id' => $qb->createNamedParameter((int)$share->id, IQueryBuilder::PARAM_INT),
				'owner' => $qb->createNamedParameter($share->owner->userId),
				'last_updated' => $qb->createNamedParameter($share->lastUpdated),
			])
			->executeStatement();

		$this->insertSources($share);
		$this->insertRecipients($share);
		$this->insertProperties($share);
		$this->insertPermissions($share);

		try {
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}

		$share = $this->get($accessContext, $share->id);
	}

	private function insertSources(Share $share): void {
		$qb = $this->connection->getQueryBuilder()
			->insert('sharing_share_sources');
		foreach ($share->sources as $source) {
			$qb
				->values([
					'id' => $qb->createNamedParameter((int)$share->id, IQueryBuilder::PARAM_INT),
					'source_type' => $qb->createNamedParameter($source->type),
					'source_value' => $qb->createNamedParameter($source->value),
				])
				->executeStatement();
		}
	}

	// TODO: Query recipient types to check if sharing with the recipient is allowed
	private function insertRecipients(Share $share): void {
		$qb = $this->connection->getQueryBuilder()
			->insert('sharing_share_recipients');
		foreach ($share->recipients as $recipient) {
			$qb
				->values([
					'id' => $qb->createNamedParameter((int)$share->id, IQueryBuilder::PARAM_INT),
					'recipient_type' => $qb->createNamedParameter($recipient->type),
					'recipient_value' => $qb->createNamedParameter($recipient->value),
				])
				->executeStatement();
		}
	}

	private function insertProperties(Share $share): void {
		$features = $this->registry->getFeatures();

		$qb = $this->connection->getQueryBuilder()
			->insert('sharing_share_properties');
		foreach ($share->properties as $featureClass => $properties) {
			if ($features[$featureClass] instanceof IShareFeatureModifyProperties) {
				// Properties are already validated when the Share object is created.
				$properties = $features[$featureClass]->modifyProperties($properties);
				if (!$features[$featureClass]->validateProperties($properties)) {
					throw new ShareInvalidPropertiesException($featureClass);
				}
			}

			foreach ($properties as $key => $values) {
				foreach ($values as $value) {
					$qb
						->values([
							'id' => $qb->createNamedParameter((int)$share->id, IQueryBuilder::PARAM_INT),
							'feature' => $qb->createNamedParameter($featureClass),
							'feature_key' => $qb->createNamedParameter($key),
							'feature_value' => $qb->createNamedParameter($value),
						])
						->executeStatement();
				}
			}
		}
	}

	private function insertPermissions(Share $share): void {
		$qb = $this->connection->getQueryBuilder()
			->insert('sharing_share_permissions');
		foreach ($share->permissions as $permission) {
			$qb
				->values([
					'id' => $qb->createNamedParameter((int)$share->id, IQueryBuilder::PARAM_INT),
					'permission' => $qb->createNamedParameter($permission),
				])
				->executeStatement();
		}
	}

	public function update(ShareAccessContext $accessContext, Share &$share): void {
		$originalShare = $this->get($accessContext, $share->id);
		$this->validateShareOwnerOperation($accessContext, $originalShare);

		if ($share->id !== $originalShare->id) {
			throw new ShareInvalidException('The id cannot be updated.');
		}

		if ($share->lastUpdated !== $originalShare->lastUpdated) {
			throw new ShareConflictException();
		}

		// TODO: If the owner is updated, check that all sources are valid for the new owner!

		$this->connection->beginTransaction();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->update('sharing_share')
			->set('owner', $qb->createNamedParameter($share->owner->userId))
			->set('last_updated', $qb->createNamedParameter($this->generateLastUpdated()))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->id)))
			->executeStatement();

		$this->deleteSources($share->id);
		$this->insertSources($share);

		$this->deleteRecipients($share->id);
		$this->insertRecipients($share);

		$this->deleteProperties($share->id);
		$this->insertProperties($share);

		$this->deletePermissions($share->id);
		$this->insertPermissions($share);

		try {
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}

		$share = $this->get($accessContext, $share->id);
	}

	public function delete(ShareAccessContext $accessContext, string $shareID): void {
		$originalShare = $this->get($accessContext, $shareID);
		$this->validateShareOwnerOperation($accessContext, $originalShare);

		$this->connection->beginTransaction();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$shareID, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		$this->deleteSources($shareID);
		$this->deleteRecipients($shareID);
		$this->deleteProperties($shareID);
		$this->deletePermissions($shareID);

		try {
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}
	}

	private function deleteSources(string $shareID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_sources')
			->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$shareID, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	private function deleteRecipients(string $shareID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_recipients')
			->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$shareID, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	private function deleteProperties(string $shareID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_properties')
			->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$shareID, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	private function deletePermissions(string $shareID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_permissions')
			->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$shareID, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	public function get(ShareAccessContext $accessContext, string $shareID): Share {
		$shares = $this->internalList($accessContext, $shareID, null, null, null);
		if (count($shares) !== 1) {
			throw new ShareNotFoundException($shareID);
		}

		return $shares[0];
	}

	public function list(ShareAccessContext $accessContext, ?string $sourceType, ?string $lastShareId, ?int $limit): array {
		return $this->internalList($accessContext, null, $sourceType, $lastShareId, $limit);
	}

	/**
	 * @param ?class-string<IShareSourceType> $sourceType
	 * @return list<Share>
	 */
	private function internalList(ShareAccessContext $accessContext, ?string $shareID, ?string $sourceType, ?string $lastShareId, ?int $limit): array {
		// LEFT JOINing all tables works, but causes a lot of rows to be selected and would require deduplication.

		/** @var list<IQueryBuilder> $queries */
		$queries = [];
		if ($accessContext->force) {
			$queries[] = $this->connection->getQueryBuilder();
		} else {
			// Because doctrine has no UNION support, individual queries have to be used

			if ($accessContext->currentUser instanceof IUser) {
				$qb = $this->connection->getQueryBuilder();
				$qb->where($qb->expr()->eq('s.owner', $qb->createNamedParameter($accessContext->currentUser->getUID())));
				$queries[] = $qb;
			}

			/** @var array<class-string<IShareRecipientType>, list<string>> $recipients */
			$recipients = [];
			foreach ($this->registry->getRecipientTypes() as $recipientType) {
				$recipientValues = $recipientType->getRecipients($accessContext->currentUser, $accessContext->arguments[$recipientType::class] ?? null);
				if ($recipientValues !== []) {
					$recipients[$recipientType::class] = $recipientValues;
				}
			}

			// Do not add a query if no recipients matched, otherwise all shares will be returned.
			if ($recipients !== []) {
				$qb = $this->connection->getQueryBuilder();
				$qb->innerJoin('s', 'sharing_share_recipients', 'sr', $qb->expr()->eq('sr.id', 's.id'));

				foreach ($recipients as $recipientTypeClass => $recipientValues) {
					$qb->orWhere($qb->expr()->andX(
						$qb->expr()->eq('recipient_type', $qb->createNamedParameter($recipientTypeClass)),
						// TODO: Add chunking
						$qb->expr()->in('recipient_value', $qb->createNamedParameter($recipientValues, IQueryBuilder::PARAM_STR_ARRAY)),
					));
				}

				$queries[] = $qb;
			}
		}

		/** @var array<string, SharingShare> $shares */
		$shares = [];
		foreach ($queries as $qb) {
			$qb
				->select(
					's.id',
					's.owner',
					's.last_updated',
				)
				->from('sharing_share', 's')
				->orderBy('s.id', 'ASC');

			if ($shareID !== null) {
				$qb->andWhere($qb->expr()->eq('s.id', $qb->createNamedParameter((int)$shareID, IQueryBuilder::PARAM_INT)));
			}

			if ($sourceType !== null) {
				$qb->innerJoin('s', 'sharing_share_sources', 'ss', $qb->expr()->andX(
					$qb->expr()->eq('s.id', 'ss.id'),
					$qb->expr()->eq('ss.source_type', $qb->createNamedParameter($sourceType)),
				));
			}

			if ($lastShareId !== null) {
				if (!ctype_digit($lastShareId)) {
					throw new ShareInvalidOperationParameterException('The lastShareId is invalid.');
				}

				$qb->andWhere($qb->expr()->gt('s.id', $qb->createNamedParameter((int)$lastShareId, IQueryBuilder::PARAM_INT)));
			}

			if ($limit !== null) {
				$qb->setMaxResults($limit);
			}

			$result = $qb->executeQuery();
			$rows = $result->fetchAll();
			foreach ($rows as $row) {
				$id = (string)$row['id'];
				$shares[$id] ??= [
					'id' => $id,
					'owner' => [
						'user_id' => (string)$row['owner'],
					],
					'last_updated' => (int)$row['last_updated'],
					'sources' => [],
					'recipients' => [],
					'properties' => [],
					'permissions' => [],
				];
			}
		}

		// If multiple queries are used the shares are not automatically sorted already.
		if (count($queries) > 1) {
			ksort($shares);
		}

		// The queries are limited already, but could return more results in total, so discard them here.
		if ($limit !== null) {
			$shares = array_slice($shares, 0, $limit, true);
		}

		$chunks = array_chunk(array_keys($shares), 1000);

		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'recipient_type',
					'recipient_value',
				)
				->from('sharing_share_recipients')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				$id = (string)$row['id'];
				$shares[$id]['recipients'][] = [
					'type' => (string)$row['recipient_type'],
					'value' => (string)$row['recipient_value'],
				];
			}
		}

		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'feature',
					'feature_key',
					'feature_value',
				)
				->from('sharing_share_properties')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				$id = (string)$row['id'];

				$shares[$id]['properties'][(string)$row['feature']] ??= [];
				$shares[$id]['properties'][(string)$row['feature']][(string)$row['feature_key']] ??= [];
				$shares[$id]['properties'][(string)$row['feature']][(string)$row['feature_key']][] = (string)$row['feature_value'];
			}
		}

		// We need to already select the sources here to check if the features are compatible.
		// Otherwise we could do it after the filtering.
		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'source_type',
					'source_value',
				)
				->from('sharing_share_sources')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				$id = (string)$row['id'];

				$shares[$id]['sources'][] = [
					'type' => (string)$row['source_type'],
					'value' => (string)$row['source_value'],
				];
			}
		}

		if (!$accessContext->force) {
			$filterFeatures = array_filter($this->registry->getFeatures(), static fn (IShareFeature $feature): bool => $feature instanceof IShareFeatureFilter);
			if ($filterFeatures !== []) {
				// Some filtering needs more logic than the database is able to provide, so it is done in the backend.
				// TODO: This can still be quite expensive for many shares, so caching the filter results might be sensible at some point.
				$shares = array_filter($shares, function (array $share) use ($filterFeatures, $accessContext): bool {
					if ($accessContext->currentUser?->getUID() === $share['owner']['user_id']) {
						return true;
					}

					/** @var array<class-string<IShareSourceType>, bool> $shareSourceTypes */
					$shareSourceTypes = [];
					foreach ($share['sources'] as $source) {
						$shareSourceTypes[$source['type']] = true;
					}

					$shareSourceTypes = array_keys($shareSourceTypes);

					/** @var array<class-string<IShareRecipientType>, bool> $shareRecipientTypes */
					$shareRecipientTypes = [];
					foreach ($share['recipients'] as $recipient) {
						$shareRecipientTypes[$recipient['type']] = true;
					}

					$shareRecipientTypes = array_keys($shareRecipientTypes);

					foreach ($filterFeatures as $feature) {
						if (array_intersect($this->registry->getSourceTypesCompatibleWithFeature($feature::class), $shareSourceTypes) === []) {
							continue;
						}

						if (array_intersect($this->registry->getRecipientTypesCompatibleWithFeature($feature::class), $shareRecipientTypes) === []) {
							continue;
						}

						if ($feature->isFiltered(
							$accessContext->currentUser,
							$accessContext->arguments[$feature::class] ?? null,
							$share['properties'][$feature::class] ?? [],
						)) {
							return false;
						}
					}

					return true;
				});
			}
		}

		// Some shares might have been filtered, so the chunks need to be recomputed.
		$chunks = array_chunk(array_keys($shares), 1000);

		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'permission',
				)
				->from('sharing_share_permissions')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				$id = (string)$row['id'];

				$shares[$id]['permissions'][] = (string)$row['permission'];
			}
		}

		/** @psalm-suppress ArgumentTypeCoercion */
		return array_map(Share::fromAPI(...), array_values($shares));
	}
}
