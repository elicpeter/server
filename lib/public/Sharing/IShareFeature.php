<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

interface IShareFeature {
	/**
	 * Validate properties of new shares.
	 *
	 * TODO: Maybe tighten to non-empty-list
	 * @param array<string, list<string>> $properties
	 */
	public function validateProperties(array $properties): bool;
}
