<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\Sharing\IShareFeature;

class TestShareFeature implements IShareFeature {
	public function __construct(
		/** @var list<string> $validProperties */
		private readonly array $validProperties,
	) {
	}

	public function validateProperties(array $properties): bool {
		return array_intersect(array_keys($properties), $this->validProperties) !== [];
	}
}
