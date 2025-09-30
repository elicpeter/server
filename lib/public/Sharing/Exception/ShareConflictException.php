<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Exception;

class ShareConflictException extends AShareException {
	public function __construct() {
		parent::__construct('The share has been updated in the meantime, so you cannot update it.');
	}
}
