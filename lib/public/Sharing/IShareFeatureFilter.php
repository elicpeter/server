<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\IUser;

interface IShareFeatureFilter extends IShareFeature {
	/**
	 * Evaluates if a share should be filtered out.
	 *
	 * Only validated properties will be passed, so validating them again is not necessary.
	 *
	 * @param mixed $arguments Defaults to null if no arguments were passed to the Manager
	 *
	 * @param array<string, list<string>> $properties
	 */
	public function isFiltered(?IUser $currentUser, mixed $arguments, array $properties): bool;
}
