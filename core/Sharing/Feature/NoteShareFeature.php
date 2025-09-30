<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Feature;

use OCP\Sharing\IShareFeature;

class NoteShareFeature implements IShareFeature {
	public function validateProperties(array $properties): bool {
		return array_keys($properties) === ['text'] && count($properties['text']) === 1 && $properties['text'][0] !== '';
	}
}
