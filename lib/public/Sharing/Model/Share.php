<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Model;

use OCA\Sharing\ResponseDefinitions;
use OCP\Server;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareInvalidPropertiesException;
use OCP\Sharing\IRegistry;
use OCP\Sharing\IShareFeature;
use OCP\Sharing\ISharePermission;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareSourceType;

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 */
readonly class Share {
	public function __construct(
		/** @var non-empty-string $id */
		public string $id,
		/** @var non-negative-int $lastUpdated Unix time in milliseconds */
		public int $lastUpdated,
		public ShareOwner $owner,
		/** @var non-empty-list<ShareSource> $sources */
		public array $sources,
		/** @var non-empty-list<ShareRecipient> $recipients */
		public array $recipients,
		/** @var array<class-string<IShareFeature>, array<string, non-empty-list<string>>> */
		public array $properties,
		/** @var non-empty-list<class-string<ISharePermission>> */
		public array $permissions,
	) {
		// TODO: Some of these might need to be skipped when loading existing shares from the DB

		/** @psalm-suppress DocblockTypeContradiction */
		if ($id === '') {
			throw new ShareInvalidException('The id is empty.');
		}

		/** {@see \OC\Snowflake\Decoder::decode} */
		if (!ctype_digit($id)) {
			throw new ShareInvalidException('The id is not a valid Snowflake ID.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($lastUpdated < 0) {
			throw new ShareInvalidException('The last updated is negative.');
		}

		$registry = Server::get(IRegistry::class);

		/** @psalm-suppress DocblockTypeContradiction */
		if ($sources === []) {
			throw new ShareInvalidException('The sources are missing.');
		}

		if (!array_is_list($sources)) {
			throw new ShareInvalidException('The sources are not a list.');
		}

		if ((!defined('PHPUNIT_RUN') || !PHPUNIT_RUN) && count($sources) > 1) {
			throw new ShareInvalidException('More than one source is currently not allowed for legacy compatibility.');
		}

		/** @var array<class-string<IShareSourceType>, bool> $shareSourceTypes */
		$shareSourceTypes = [];
		/** @var array<class-string<ISharePermission>, bool> $shareSourceTypePermissions */
		$shareSourceTypePermissions = [];
		$sourceTypes = $registry->getSourceTypes();
		foreach ($sources as $source) {
			if (!isset($sourceTypes[$source->type])) {
				throw new ShareInvalidException('The source type is not registered: ' . $source->type);
			}

			if (!$sourceTypes[$source->type]->validateSource($this->owner->getUser(), $source->value)) {
				throw new ShareInvalidException('The source ' . $source->value . ' for ' . $source->type . ' is not valid.');
			}

			$shareSourceTypes[$source->type] = true;

			foreach ($sourceTypes[$source->type]->getPermissions() as $permission) {
				$permissionCategory = $permission->getCategory();
				if ($permissionCategory !== null && !isset($registry->getPermissionCategories()[$permissionCategory])) {
					throw new ShareInvalidException('Share permission category ' . $permissionCategory . ' is not registered');
				}

				$shareSourceTypePermissions[$permission::class] = true;
			}
		}

		$shareSourceTypes = array_keys($shareSourceTypes);
		$shareSourceTypePermissions = array_keys($shareSourceTypePermissions);

		/** @psalm-suppress DocblockTypeContradiction */
		if ($recipients === []) {
			throw new ShareInvalidException('The recipients are missing.');
		}

		if (!array_is_list($recipients)) {
			throw new ShareInvalidException('The recipients are not a list.');
		}

		if ((!defined('PHPUNIT_RUN') || !PHPUNIT_RUN) && count($recipients) > 1) {
			throw new ShareInvalidException('More than one recipien is currently not allowed for legacy compatibility.');
		}

		/** @var array<class-string<IShareRecipientType>, bool> $shareRecipientTypes */
		$shareRecipientTypes = [];
		$recipientTypes = $registry->getRecipientTypes();
		foreach ($recipients as $recipient) {
			if (!isset($recipientTypes[$recipient->type])) {
				throw new ShareInvalidException('The recipient type is not registered: ' . $recipient->type);
			}

			if (!$recipientTypes[$recipient->type]->validateRecipient($recipient->value)) {
				throw new ShareInvalidException('The recipient ' . $recipient->value . ' for ' . $recipient->type . ' is not valid.');
			}

			$shareRecipientTypes[$recipient->type] = true;
		}

		$shareRecipientTypes = array_keys($shareRecipientTypes);

		$features = $registry->getFeatures();
		foreach ($properties as $featureClass => $featureProperties) {
			/** @psalm-suppress DocblockTypeContradiction */
			if (!is_string($featureClass)) {
				throw new ShareInvalidException('The property is not a string: ' . var_export($featureClass, true));
			}

			if (!isset($features[$featureClass])) {
				throw new ShareInvalidException('The property is not registered: ' . var_export($featureClass, true));
			}

			// TODO: Instead of failing we might need to strip them silently, in case the app that provided the feature is disabled now.
			if (array_intersect($registry->getSourceTypesCompatibleWithFeature($featureClass), $shareSourceTypes) === []) {
				throw new ShareInvalidException('The property is not compatible with any of the source types of the share: ' . var_export($featureClass, true));
			}

			if (array_intersect($registry->getRecipientTypesCompatibleWithFeature($featureClass), $shareRecipientTypes) === []) {
				throw new ShareInvalidException('The property is not compatible with any of the recipient types of the share: ' . var_export($featureClass, true));
			}

			/** @psalm-suppress DocblockTypeContradiction */
			if (!is_array($featureProperties)) {
				throw new ShareInvalidException('The properties are not an array: ' . var_export($featureProperties, true));
			}

			foreach ($featureProperties as $key => $values) {
				/** @psalm-suppress DocblockTypeContradiction */
				if (!is_string($key)) {
					throw new ShareInvalidException('The property key is not a string: ' . var_export($key, true));
				}

				if (!array_is_list($values)) {
					throw new ShareInvalidException('The property values are not an array: ' . var_export($values, true));
				}

				foreach ($values as $value) {
					/** @psalm-suppress DocblockTypeContradiction */
					if (!is_string($value)) {
						throw new ShareInvalidException('The property value is not a string: ' . var_export($value, true));
					}
				}
			}

			if (!$features[$featureClass]->validateProperties($featureProperties)) {
				throw new ShareInvalidPropertiesException($featureClass);
			}
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($permissions === []) {
			throw new ShareInvalidException('The permissions are missing.');
		}

		if (!array_is_list($permissions)) {
			throw new ShareInvalidException('The permissions are not a list.');
		}

		foreach ($permissions as $permission) {
			if (!in_array($permission, $shareSourceTypePermissions, true)) {
				throw new ShareInvalidException('The permission is not compatible with any of the source types of the share: ' . var_export($permission, true));
			}
		}
	}

	/**
	 * @param SharingShare $share
	 */
	public static function fromAPI(array $share): self {
		return new self(
			$share['id'],
			$share['last_updated'],
			ShareOwner::fromAPI($share['owner']),
			array_map(ShareSource::fromAPI(...), $share['sources']),
			array_map(ShareRecipient::fromAPI(...), $share['recipients']),
			$share['properties'],
			$share['permissions'],
		);
	}

	/**
	 * @return SharingShare
	 */
	public function toAPI(): array {
		$sourceDisplayNames = [];
		foreach ($this->sources as $source) {
			$displayName = $source->sourceType->getSourceDisplayName($source->value) ?? $source->value;
			$sourceDisplayNames[$displayName] ??= 0;
			++$sourceDisplayNames[$displayName];
		}

		$recipientDisplayNames = [];
		foreach ($this->recipients as $recipient) {
			$displayName = $recipient->recipientType->getRecipientDisplayName($recipient->value) ?? $recipient->value;
			$recipientDisplayNames[$displayName] ??= 0;
			++$recipientDisplayNames[$displayName];
		}

		return [
			'id' => $this->id,
			'owner' => $this->owner->toAPI(),
			'last_updated' => $this->lastUpdated,
			'sources' => array_map(static fn (ShareSource $source): array => $source->toAPI($sourceDisplayNames[$source->sourceType->getSourceDisplayName($source->value) ?? $source->value] === 1), $this->sources),
			'recipients' => array_map(static fn (ShareRecipient $recipient): array => $recipient->toAPI($recipientDisplayNames[$recipient->recipientType->getRecipientDisplayName($recipient->value) ?? $recipient->value] === 1), $this->recipients),
			'properties' => $this->properties,
			'permissions' => $this->permissions,
		];
	}
}
