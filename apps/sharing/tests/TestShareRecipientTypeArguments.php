<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\IUser;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\Model\ShareIconSVG;
use OCP\Sharing\Model\ShareIconURL;

class TestShareRecipientTypeArguments implements IShareRecipientType {
	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	public function validateRecipient(string $recipient): bool {
		return true;
	}

	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (is_string($arguments)) {
			return [$arguments];
		}

		return [];
	}

	public function getRecipientDisplayName(string $recipient): ?string {
		return null;
	}

	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}
}
