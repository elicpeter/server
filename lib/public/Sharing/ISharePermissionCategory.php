<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

interface ISharePermissionCategory {
	/**
	 * Returns a user friendly display name for this permission category.
	 *
	 * @return non-empty-string
	 */
	public function getDisplayName(): string;
}
