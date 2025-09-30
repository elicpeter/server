<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Controller;

use OCA\Sharing\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Sharing\Exception\ShareConflictException;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareInvalidOperationParameterException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\Exception\ShareOperationNotAllowedException;
use OCP\Sharing\IManager;
use OCP\Sharing\IShareFeatureFilter;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareSourceType;
use OCP\Sharing\Model\Share;
use OCP\Sharing\Model\ShareAccessContext;
use OCP\Sharing\Model\ShareRecipient;

// TODO: Rate limit recipients during share create and update
// TODO: Add federation

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 * @psalm-import-type SharingPartialShare from ResponseDefinitions
 * @psalm-import-type SharingRecipient from ResponseDefinitions
 */
class ApiV1Controller extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IUserSession $userSession,
		private readonly IManager $manager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Searches for recipients
	 *
	 * @param ?class-string<IShareRecipientType> $recipientType Type of the recipients
	 * @param non-empty-string $query The query to search for
	 * @param int<1, 100> $limit The maximum number of participants
	 * @param non-negative-int $offset The offset of the participants
	 * @return DataResponse<Http::STATUS_OK, list<SharingRecipient>, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, string, array{}>
	 *
	 * 200: Recipients returned
	 * 400: Bad recipient search parameters
	 */
	#[UserRateLimit(limit: 1, period: 1)]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/recipients')]
	public function searchRecipients(?string $recipientType, string $query, int $limit = 10, int $offset = 0): DataResponse {
		/** @psalm-suppress TypeDoesNotContainType */
		if ($query === '') {
			throw new ShareInvalidOperationParameterException('The query is empty.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit < 1) {
			throw new ShareInvalidOperationParameterException('The limit is too low.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit > 100) {
			throw new ShareInvalidOperationParameterException('The limit is too high.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($offset < 0) {
			throw new ShareInvalidOperationParameterException('The offset is too low.');
		}

		try {
			$recipients = $this->manager->searchRecipients($recipientType, $query, $limit, $offset);
		} catch (ShareInvalidOperationParameterException $shareInvalidOperationParameterException) {
			return new DataResponse($shareInvalidOperationParameterException->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		$recipientDisplayNames = [];
		foreach ($recipients as $recipient) {
			$displayName = $recipient->recipientType->getRecipientDisplayName($recipient->value) ?? $recipient->value;
			$recipientDisplayNames[$displayName] ??= 0;
			++$recipientDisplayNames[$displayName];
		}

		return new DataResponse(array_map(static fn (ShareRecipient $recipient): array => $recipient->toAPI($recipientDisplayNames[$recipient->recipientType->getRecipientDisplayName($recipient->value) ?? $recipient->value] === 1), $recipients));
	}

	/**
	 * Creates a new share
	 *
	 * @param SharingPartialShare $data The new share data
	 * @return DataResponse<Http::STATUS_CREATED, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_FORBIDDEN, string, array{}>
	 *
	 * 201: Share created successfully
	 * 400: Invalid share data
	 * 403: Creating the share is not allowed
	 */
	#[UserRateLimit(limit: 1, period: 5)]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/share')]
	public function createShare(array $data): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		$share = Share::fromAPI($this->manager->completePartialShareData($data, $user->getUID()));

		try {
			$this->manager->insert(new ShareAccessContext($user), $share);
		} catch (ShareInvalidException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (ShareOperationNotAllowedException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_FORBIDDEN);
		}

		return new DataResponse($share->toAPI(), Http::STATUS_CREATED);
	}


	/**
	 * Gets a share
	 *
	 * @param string $id ID of the share
	 * @param array<class-string<IShareRecipientType|IShareFeatureFilter>, mixed> $arguments Arguments for accessing the share
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share returned
	 * 404: Share not found
	 */
	#[UserRateLimit(limit: 1, period: 1)]
	#[AnonRateLimit(limit: 1, period: 5)]
	#[PublicPage]
	// This should be a GET, but GET doesn't allow a request body which is required for the $arguments.
	#[ApiRoute(verb: 'POST', url: '/api/v1/share/{id}')]
	public function getShare(string $id, array $arguments = []): DataResponse {
		$user = $this->userSession->getUser();

		try {
			$share = $this->manager->get(new ShareAccessContext($user, $arguments), $id);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getMessage(), Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($share->toAPI());
	}

	/**
	 * Gets all shares
	 *
	 * @param ?class-string<IShareSourceType> $sourceType Filter by source type.
	 * @param ?string $lastShareId The ID of the previous share. This is used as an offset and only shares with higher IDs are returned.
	 * @param int<1, 100> $limit The number of shares to return.
	 * @return DataResponse<Http::STATUS_OK, list<SharingShare>, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED, string, array{}>
	 *
	 * 200: Shares returned
	 */
	#[UserRateLimit(limit: 1, period: 5)]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/shares')]
	public function getShares(?string $sourceType = null, ?string $lastShareId = null, int $limit = 100) {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit < 1) {
			throw new ShareInvalidOperationParameterException('The limit is too low.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit > 100) {
			throw new ShareInvalidOperationParameterException('The limit is too high.');
		}

		$shares = $this->manager->list(new ShareAccessContext($user), $sourceType, $lastShareId, $limit);

		return new DataResponse(array_map(static fn (Share $share): array => $share->toAPI(), $shares));
	}

	/**
	 * Updates a share
	 *
	 * @param non-empty-string $id ID of the share
	 * @param SharingShare $data The updated share data
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND|Http::STATUS_CONFLICT, string, array{}>
	 *
	 * 200: Share updated
	 * 400: Invalid share data
	 * 404: Share not found
	 * 409: The share has been updated in the meantime, so you cannot update it.
	 */
	#[UserRateLimit(limit: 1, period: 1)]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/share/{id}')]
	public function updateShare(string $id, array $data): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		if ($data['id'] !== $id) {
			return new DataResponse('Share IDs do not match', Http::STATUS_BAD_REQUEST);
		}

		$share = Share::fromAPI($data);

		try {
			$this->manager->update(new ShareAccessContext($user), $share);
		} catch (ShareInvalidException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (ShareNotFoundException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_NOT_FOUND);
		} catch (ShareConflictException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_CONFLICT);
		}

		return new DataResponse($share->toAPI());
	}

	/**
	 * Deletes a share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_NO_CONTENT, list<empty>, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 204: Share deleted
	 * 404: Share not found
	 */
	#[UserRateLimit(limit: 1, period: 1)]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/share/{id}')]
	public function deleteShare(string $id): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->manager->delete(new ShareAccessContext($user), $id);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getMessage(), Http::STATUS_NOT_FOUND);
		}

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}
}
