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
use OCP\Sharing\IShareSourceType;
use RuntimeException;

/**
 * @psalm-import-type SharingSource from ResponseDefinitions
 */
readonly class ShareSource {
	public IShareSourceType $sourceType;

	public function __construct(
		/** @var class-string<IShareSourceType> $type */
		public string $type,
		/** @var non-empty-string $value */
		public string $value,
		/** @var ?non-empty-string $instance */
		public ?string $instance = null,
	) {
		$sourceType = Server::get(IRegistry::class)->getSourceTypes()[$type] ?? null;
		if ($sourceType === null) {
			throw new RuntimeException('The source type is not registered: ' . $type);
		}

		$this->sourceType = $sourceType;

		/** @psalm-suppress DocblockTypeContradiction */
		if ($value === '') {
			throw new RuntimeException('The value is empty.');
		}

		if ($instance !== null && !preg_match('/^https?:\/\//', $instance)) {
			throw new RuntimeException('The instance is not a valid absolute URL: ' . $instance);
		}
	}

	/**
	 * @param SharingSource $source
	 */
	public static function fromAPI(array $source): self {
		return new self(
			$source['type'],
			$source['value'],
			$source['instance'] ?? null,
		);
	}

	/**
	 * @return SharingSource
	 */
	public function toAPI(bool $isUnique): array {
		$sourceType = Server::get(IRegistry::class)->getSourceTypes()[$this->type];

		$displayName = $sourceType->getSourceDisplayName($this->value) ?? $this->value;
		if (!$isUnique) {
			$displayName .= ' (' . $sourceType->getDisplayName() . ': ' . $this->value . ')';
		}

		$out = [
			'type' => $this->type,
			'value' => $this->value,
			'display_name' => $displayName,
		];

		if ($this->instance !== null) {
			$out['instance'] = $this->instance;
		}

		return $out;
	}
}
