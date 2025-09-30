<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCA\Sharing\ResponseDefinitions;
use OCP\Sharing\Exception\ShareConflictException;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareInvalidOperationParameterException;
use OCP\Sharing\Exception\ShareInvalidPropertiesException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\Exception\ShareOperationNotAllowedException;
use OCP\Sharing\Model\Share;
use OCP\Sharing\Model\ShareAccessContext;
use OCP\Sharing\Model\ShareRecipient;

// TODO: Update throws annotations and catch them in the controller

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 * @psalm-import-type SharingPartialShare from ResponseDefinitions
 */
interface IManager {
	/**
	 * @param SharingPartialShare $share
	 * @param non-empty-string $owner
	 * @return SharingShare
	 */
	public function completePartialShareData(array $share, string $owner): array;

	/**
	 * @param ?class-string<IShareRecipientType> $recipientTypeClass
	 * @param non-empty-string $query
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @throws ShareInvalidOperationParameterException
	 */
	public function searchRecipients(?string $recipientTypeClass, string $query, int $limit, int $offset): array;

	/**
	 * The share might be updated during insertion (e.g. display name updates),
	 * but the share object is automatically updated for you.
	 *
	 * @throws ShareOperationNotAllowedException
	 * @throws ShareInvalidException
	 */
	public function insert(ShareAccessContext $accessContext, Share &$share): void;

	/**
	 * The share might be updated during insertion (e.g. display name updates),
	 * but the share object is automatically updated for you.
	 *
	 * @throws ShareConflictException
	 * @throws ShareInvalidException
	 * @throws ShareInvalidPropertiesException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationNotAllowedException
	 */
	public function update(ShareAccessContext $accessContext, Share &$share): void;

	/**
	 * @throws ShareNotFoundException
	 * @throws ShareOperationNotAllowedException
	 */
	public function delete(ShareAccessContext $accessContext, string $shareID): void;

	/**
	 * @throws ShareNotFoundException
	 */
	public function get(ShareAccessContext $accessContext, string $shareID): Share;

	/**
	 * @param ?class-string<IShareSourceType> $sourceType
	 * @return list<Share>
	 */
	public function list(ShareAccessContext $accessContext, ?string $sourceType, ?string $lastShareId, ?int $limit): array;
}
