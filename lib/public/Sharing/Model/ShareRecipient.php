<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Model;

use OCA\Sharing\ResponseDefinitions;
use OCP\Server;
use OCP\Sharing\IRegistry;
use OCP\Sharing\IShareRecipientType;
use RuntimeException;

/**
 * @psalm-import-type SharingRecipient from ResponseDefinitions
 */
readonly class ShareRecipient {
	public IShareRecipientType $recipientType;

	public function __construct(
		/** @var class-string<IShareRecipientType> $type */
		public string $type,
		/** @var non-empty-string $value */
		public string $value,
		/** @var ?non-empty-string $instance */
		public ?string $instance = null,
	) {
		$recipientType = Server::get(IRegistry::class)->getRecipientTypes()[$type] ?? null;
		if ($recipientType === null) {
			throw new RuntimeException('The recipient type is not registered: ' . $type);
		}

		$this->recipientType = $recipientType;

		if (!isset(Server::get(IRegistry::class)->getRecipientTypes()[$type])) {
			throw new RuntimeException('The recipient type is not registered: ' . $type);
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($value === '') {
			throw new RuntimeException('The value is empty.');
		}

		if ($instance !== null && !preg_match('/^https?:\/\//', $instance)) {
			throw new RuntimeException('The instance is not a valid absolute URL: ' . $instance);
		}
	}

	/**
	 * @param SharingRecipient $recipient
	 */
	public static function fromAPI(array $recipient): self {
		return new self(
			$recipient['type'],
			$recipient['value'],
			$recipient['instance'] ?? null,
		);
	}

	/**
	 * @return SharingRecipient
	 */
	public function toAPI(bool $isUnique): array {
		$recipientType = Server::get(IRegistry::class)->getRecipientTypes()[$this->type];

		$displayName = $recipientType->getRecipientDisplayName($this->value) ?? $this->value;
		if (!$isUnique) {
			$displayName .= ' (' . $recipientType->getDisplayName() . ': ' . $this->value . ')';
		}

		$out = [
			'type' => $this->type,
			'value' => $this->value,
			'display_name' => $displayName,
		];

		if ($this->instance !== null) {
			$out['instance'] = $this->instance;
		}

		$icon = $recipientType->getRecipientIcon($this->value);
		if ($icon !== null) {
			$out['icon'] = $icon->toAPI();
		}

		return $out;
	}
}
