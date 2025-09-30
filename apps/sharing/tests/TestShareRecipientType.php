<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\IUser;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareRecipientTypeSearch;
use OCP\Sharing\Model\ShareIconSVG;
use OCP\Sharing\Model\ShareIconURL;
use OCP\Sharing\Model\ShareRecipient;

class TestShareRecipientType implements IShareRecipientType, IShareRecipientTypeSearch {
	public function __construct(
		/** @var array<string, non-empty-string> $validRecipients */
		private readonly array $validRecipients,
		/** @var list<non-empty-string> $recipients */
		private readonly array $recipients,
		/** @var list<ShareRecipient> $searchRecipients */
		public array $searchRecipients,
	) {
	}

	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	public function validateRecipient(string $recipient): bool {
		return array_key_exists($recipient, $this->validRecipients);
	}

	/**
	 * @return list<string>
	 */
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		return $this->recipients;
	}

	public function getRecipientDisplayName(string $recipient): ?string {
		return $this->validRecipients[$recipient];
	}

	public function searchRecipients(string $query, int $limit, int $offset): array {
		return array_slice($this->searchRecipients, $offset, $limit);
	}

	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return match ($recipient) {
			'svg' => new ShareIconSVG('<svg/>'),
			'url' => new ShareIconURL('https://example.com/light.png', 'https://example.com/dark.png'),
			default => null,
		};
	}
}
