<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Exception;

use OCP\Sharing\IShareFeature;

class ShareInvalidPropertiesException extends ShareInvalidException {
	/**
	 * @param class-string<IShareFeature> $feature
	 */
	public function __construct(string $feature) {
		parent::__construct('The properties for feature ' . $feature . ' are not valid.');
	}
}
