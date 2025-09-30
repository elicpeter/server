<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Model;

use OCP\IUser;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\IShareFeatureFilter;
use OCP\Sharing\IShareRecipientType;

readonly class ShareAccessContext {
	public function __construct(
		public ?IUser $currentUser = null,
		/** @var array<class-string<IShareRecipientType|IShareFeatureFilter>, mixed> $arguments */
		public array $arguments = [],
		/**
		 * Ignore all checks and allow any operation. Only use it for admins and occ.
		 */
		public bool $force = false,
	) {
		foreach (array_keys($arguments) as $key) {
			/** @psalm-suppress DocblockTypeContradiction */
			if (!is_string($key)) {
				throw new ShareInvalidException('The argument key is not a string: ' . var_export($key, true));
			}
		}
	}
}
