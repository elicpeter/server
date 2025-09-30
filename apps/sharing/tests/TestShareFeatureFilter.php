<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\IUser;
use OCP\Sharing\IShareFeatureFilter;

class TestShareFeatureFilter implements IShareFeatureFilter {
	public function validateProperties(array $properties): bool {
		return true;
	}

	public function isFiltered(?IUser $currentUser, mixed $arguments, array $properties): bool {
		return $arguments === 'filtered' || (($properties['filtered'] ?? ['false'])[0] === 'true');
	}
}
