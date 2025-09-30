<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

interface IRegistry {

	public function clear(): void;

	public function registerSourceType(IShareSourceType $sourceType): void;

	/**
	 * @return array<class-string<IShareSourceType>, IShareSourceType>
	 */
	public function getSourceTypes(): array;

	/**
	 * @param class-string<IShareFeature> $feature
	 * @return list<class-string<IShareSourceType>>
	 */
	public function getSourceTypesCompatibleWithFeature(string $feature): array;

	public function registerRecipientType(IShareRecipientType $recipientType): void;


	/**
	 * @return array<class-string<IShareRecipientType>, IShareRecipientType>
	 */
	public function getRecipientTypes(): array;

	/**
	 * @param class-string<IShareFeature> $feature
	 * @return list<class-string<IShareRecipientType>>
	 */
	public function getRecipientTypesCompatibleWithFeature(string $feature): array;


	public function registerFeature(IShareFeature $feature): void;

	/**
	 * @param class-string<IShareFeature> $feature
	 * @param class-string<IShareSourceType> $sourceType
	 */
	public function registerFeatureCompatibleWithSourceType(string $feature, string $sourceType): void;

	/**
	 * @param class-string<IShareFeature> $feature
	 * @param class-string<IShareRecipientType> $recipientType
	 */
	public function registerFeatureCompatibleWithRecipientType(string $feature, string $recipientType): void;

	/**
	 * @return array<class-string<IShareFeature>, IShareFeature>
	 */
	public function getFeatures(): array;

	public function registerPermissionCategory(ISharePermissionCategory $permissionCategory): void;

	/**
	 * @return array<class-string<ISharePermissionCategory>, ISharePermissionCategory>
	 */
	public function getPermissionCategories(): array;
}
