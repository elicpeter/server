<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

/**
 * Describes a permission the recipient has on the source through the share.
 */
interface ISharePermission {
	/**
	 * Returns a user friendly display name for this permission.
	 *
	 * @return non-empty-string
	 */
	public function getDisplayName(): string;

	/**
	 * Returns the category the permission belongs to.
	 * If no category matches, it may return null.
	 *
	 * @return class-string<ISharePermissionCategory>
	 */
	public function getCategory(): ?string;
}
