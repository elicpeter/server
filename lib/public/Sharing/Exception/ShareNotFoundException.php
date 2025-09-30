<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Exception;

class ShareNotFoundException extends AShareException {
	public function __construct(string $shareID) {
		parent::__construct('Share ' . $shareID . ' not found.');
	}
}
