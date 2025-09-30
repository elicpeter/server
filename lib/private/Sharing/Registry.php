<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use OCP\Sharing\IRegistry;
use OCP\Sharing\IShareFeature;
use OCP\Sharing\ISharePermissionCategory;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareSourceType;
use RuntimeException;

class Registry implements IRegistry {
	/** @var array<class-string<IShareSourceType>, IShareSourceType> */
	private array $sourceTypes = [];

	/** @var array<class-string<IShareRecipientType>, IShareRecipientType> */
	private array $recipientTypes = [];

	/** @var array<class-string<IShareFeature>, IShareFeature> */
	private array $features = [];

	/** @var array<class-string<IShareFeature>, array<class-string<IShareSourceType>, bool>> */
	private array $featureCompatibleSourceTypes = [];

	/** @var array<class-string<IShareFeature>, array<class-string<IShareRecipientType>, bool>> */
	private array $featureCompatibleRecipientTypes = [];

	/** @var array<class-string<ISharePermissionCategory>, ISharePermissionCategory> */
	private array $permissionCategories = [];

	public function clear(): void {
		$this->sourceTypes = [];
		$this->recipientTypes = [];
		$this->features = [];
		$this->featureCompatibleSourceTypes = [];
		$this->featureCompatibleRecipientTypes = [];
		$this->permissionCategories = [];
	}

	public function registerSourceType(IShareSourceType $sourceType): void {
		$class = $sourceType::class;

		if (isset($this->sourceTypes[$class])) {
			throw new RuntimeException('Share source type ' . $class . ' is already registered');
		}

		$this->sourceTypes[$class] = $sourceType;
	}

	/**
	 * @return array<class-string<IShareSourceType>, IShareSourceType>
	 */
	public function getSourceTypes(): array {
		return $this->sourceTypes;
	}

	public function getSourceTypesCompatibleWithFeature(string $feature): array {
		$sourceTypes = array_keys($this->featureCompatibleSourceTypes[$feature] ?? []);
		foreach ($sourceTypes as $sourceType) {
			if (!isset($this->sourceTypes[$sourceType])) {
				// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
				throw new RuntimeException('Share source type ' . $sourceType . ' is not registered');
			}
		}

		return $sourceTypes;
	}

	public function registerRecipientType(IShareRecipientType $recipientType): void {
		$class = $recipientType::class;

		if (isset($this->recipientTypes[$class])) {
			throw new RuntimeException('Share recipient type ' . $class . ' is already registered');
		}

		$this->recipientTypes[$class] = $recipientType;
	}

	/**
	 * @return array<class-string<IShareRecipientType>, IShareRecipientType>
	 */
	public function getRecipientTypes(): array {
		return $this->recipientTypes;
	}

	public function getRecipientTypesCompatibleWithFeature(string $feature): array {
		$recipientTypes = array_keys($this->featureCompatibleRecipientTypes[$feature] ?? []);
		foreach ($recipientTypes as $recipientType) {
			if (!isset($this->recipientTypes[$recipientType])) {
				// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
				throw new RuntimeException('Share recipient type ' . $recipientType . ' is not registered');
			}
		}

		return $recipientTypes;
	}

	public function registerFeature(IShareFeature $feature): void {
		$class = $feature::class;

		if (isset($this->features[$class])) {
			throw new RuntimeException('Share feature ' . $class . ' is already registered');
		}

		$this->features[$class] = $feature;
	}

	public function registerFeatureCompatibleWithSourceType(string $feature, string $sourceType): void {
		// Because we can't control the order in which apps are booted, we can't ensure that the source type is already registered.
		$this->featureCompatibleSourceTypes[$feature] ??= [];
		$this->featureCompatibleSourceTypes[$feature][$sourceType] = true;
	}

	public function registerFeatureCompatibleWithRecipientType(string $feature, string $recipientType): void {
		// Because we can't control the order in which apps are booted, we can't ensure that the source type is already registered.
		$this->featureCompatibleRecipientTypes[$feature] ??= [];
		$this->featureCompatibleRecipientTypes[$feature][$recipientType] = true;
	}

	/**
	 * @return array<class-string<IShareFeature>, IShareFeature>
	 */
	public function getFeatures(): array {
		return $this->features;
	}

	public function registerPermissionCategory(ISharePermissionCategory $permissionCategory): void {
		$class = $permissionCategory::class;

		if (isset($this->permissionCategories[$class])) {
			throw new RuntimeException('Share permission category ' . $class . ' is already registered');
		}

		$this->permissionCategories[$class] = $permissionCategory;
	}

	/**
	 * @return array<class-string<ISharePermissionCategory>, ISharePermissionCategory>
	 */
	public function getPermissionCategories(): array {
		return $this->permissionCategories;
	}
}
