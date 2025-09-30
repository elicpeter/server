<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Model;

use OCA\Sharing\ResponseDefinitions;
use RuntimeException;

/**
 * @psalm-import-type SharingIconURL from ResponseDefinitions
 */
readonly class ShareIconURL {
	public function __construct(
		/** @var non-empty-string $light */
		public string $light,
		/** @var non-empty-string $dark */
		public string $dark,
	) {
		if (!preg_match('/^https?:\/\//', $light)) {
			throw new RuntimeException('The light is not a valid absolute URL: ' . $light);
		}

		if (!preg_match('/^https?:\/\//', $dark)) {
			throw new RuntimeException('The dark is not a valid absolute URL: ' . $dark);
		}
	}

	/**
	 * @return SharingIconURL
	 */
	public function toAPI(): array {
		return [
			'light' => $this->light,
			'dark' => $this->dark,
		];
	}
}
