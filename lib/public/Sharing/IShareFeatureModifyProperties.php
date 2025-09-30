<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

interface IShareFeatureModifyProperties extends IShareFeature {
	/**
	 * Modify the properties whenever a share is created or updated.
	 *
	 * The properties will be passed to {@see IShareFeature::validateProperties()} before and after the invOCPtion of this method.
	 * This means you don't need to validate the properties again in the implementation of this method.
	 *
	 * TODO: Maybe tighten to non-empty-list
	 * @param array<string, list<string>> $properties
	 * @return array<string, list<string>>
	 */
	public function modifyProperties(array $properties): array;
}
