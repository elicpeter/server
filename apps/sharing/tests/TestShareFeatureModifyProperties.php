<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\Sharing\IShareFeatureModifyProperties;

class TestShareFeatureModifyProperties implements IShareFeatureModifyProperties {
	public function validateProperties(array $properties): bool {
		return (array_keys($properties) === ['before'] && count($properties['before']) === 1 && $properties['before'][0] === 'valid')
			|| (array_keys($properties) === ['after'] && count($properties['after']) === 1 && $properties['after'][0] === 'valid');
	}

	/**
	 * @param array<string, list<string>> $properties
	 * @return array<string, list<string>>
	 */
	public function modifyProperties(array $properties): array {
		return ['after' => $properties['before']];
	}
}
