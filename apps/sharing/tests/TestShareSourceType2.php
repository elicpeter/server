<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\Sharing\ISharePermission;

class TestShareSourceType2 extends TestShareSourceType {
	/**
	 * @return non-empty-list<ISharePermission>
	 */
	public function getPermissions(): array {
		return [
			(new TestSharePermission2()),
		];
	}
}
