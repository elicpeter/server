<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Exception;

class ShareInvalidOperationParameterException extends AShareException {
	public function __construct(string $parameter) {
		parent::__construct('Invalid operation parameter: ' . $parameter);
	}
}
