<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use OCA\Sharing\AppInfo\Application;
use OCP\Capabilities\ICapability;
use OCP\Sharing\IRegistry;
use OCP\Sharing\IShareFeature;
use OCP\Sharing\ISharePermission;
use OCP\Sharing\ISharePermissionCategory;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareSourceType;
use RuntimeException;

class Capabilities implements ICapability {
	public function __construct(
		private readonly IRegistry $registry,
	) {
	}

	/**
	 * @return array{
	 *     sharing: array{
	 *         api_versions: list<'v1'>,
	 *         // Information for legacy compatibility with files_sharing.
	 *         // As long as Unified Sharing is a translation layer to files_sharing this is needed.
	 *         // As soon as this turns around and files_sharing becomes a translation layer for Unified Sharing,
	 *         // this information will be removed.
	 *         legacy?: array{
	 *             // Maximum number of sources allowed to be selected by the user.
	 *             max_sources: positive-int,
	 *             // Maximum number of recipients allowed to be selected by the user.
	 *             max_recipients: positive-int,
	 *         },
	 *         source_types: list<array{
	 *             type: class-string<IShareSourceType>,
	 *             display_name: non-empty-string,
	 *         }>,
	 *         recipient_types: list<array{
	 *             type: class-string<IShareRecipientType>,
	 *             display_name: non-empty-string,
	 *         }>,
	 *         // For a feature to be considered compatible with a particular share,
	 *         // it has to be compatible with at least one source type and one recipient type.
	 *         features: list<array{
	 *             type: class-string<IShareFeature>,
	 *             compatible_source_types: list<class-string<IShareSourceType>>,
	 *             compatible_recipient_types: list<class-string<IShareRecipientType>>,
	 *         }>,
	 *         permission_categories: list<array{
	 *             type: class-string<ISharePermissionCategory>,
	 *             display_name: non-empty-string,
	 *         }>,
	 *         permissions: list<array{
	 *             type: class-string<ISharePermission>,
	 *             display_name: non-empty-string,
	 *             category: ?class-string<ISharePermissionCategory>,
	 *         }>,
	 *     },
	 * }
	 */
	public function getCapabilities(): array {
		$permissionCategories = $this->registry->getPermissionCategories();

		$permissions = [];
		foreach ($this->registry->getSourceTypes() as $sourceType) {
			foreach ($sourceType->getPermissions() as $permission) {
				$permissionCategory = $permission->getCategory();
				if ($permissionCategory !== null && !isset($permissionCategories[$permissionCategory])) {
					throw new RuntimeException('Share permission category ' . $permissionCategory . ' is not registered');
				}

				$permissions[] = [
					'type' => $permission::class,
					'display_name' => $permission->getDisplayName(),
					'category' => $permissionCategory,
				];
			}
		}

		return [
			Application::APP_ID => [
				'api_versions' => ['v1'],
				'legacy' => [
					'max_sources' => 1,
					'max_recipients' => 1,
				],
				'source_types' => array_map(static fn (IShareSourceType $sourceType): array => [
					'type' => $sourceType::class,
					'display_name' => $sourceType->getDisplayName(),
				], array_values($this->registry->getSourceTypes())),
				'recipient_types' => array_map(static fn (IShareRecipientType $recipientType): array => [
					'type' => $recipientType::class,
					'display_name' => $recipientType->getDisplayName(),
				], array_values($this->registry->getRecipientTypes())),
				'features' => array_map(fn (IShareFeature $feature): array => [
					'type' => $feature::class,
					'compatible_source_types' => $this->registry->getSourceTypesCompatibleWithFeature($feature::class),
					'compatible_recipient_types' => $this->registry->getRecipientTypesCompatibleWithFeature($feature::class),
					// TODO: Add display name and input fields
				], array_values($this->registry->getFeatures())),
				'permission_categories' => array_map(static fn (ISharePermissionCategory $permissionCategory): array => [
					'type' => $permissionCategory::class,
					'display_name' => $permissionCategory->getDisplayName(),
				], array_values($this->registry->getPermissionCategories())),
				'permissions' => $permissions,
			],
		];
	}
}
