<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Feature;

use DateTimeImmutable;
use Exception;
use OCP\IUser;
use OCP\Sharing\IShareFeature;
use OCP\Sharing\IShareFeatureFilter;

class ExpirationShareFeature implements IShareFeature, IShareFeatureFilter {
	public function validateProperties(array $properties): bool {
		if (array_keys($properties) !== ['date'] || count($properties['date']) !== 1 || $properties['date'][0] === '') {
			return false;
		}

		try {
			new DateTimeImmutable($properties['date'][0]);
			return true;
		} catch (Exception) {
			return false;
		}
	}

	/**
	 * @throws Exception
	 */
	public function isFiltered(?IUser $currentUser, mixed $arguments, array $properties): bool {
		return (new DateTimeImmutable($properties['date'][0]))->diff(new DateTimeImmutable())->invert === 0;
	}
}
