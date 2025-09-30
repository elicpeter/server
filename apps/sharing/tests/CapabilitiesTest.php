<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


use OCA\Sharing\AppInfo\Application;
use OCA\Sharing\Capabilities;
use OCA\Sharing\Tests\TestShareFeature;
use OCA\Sharing\Tests\TestShareFeature2;
use OCA\Sharing\Tests\TestSharePermission;
use OCA\Sharing\Tests\TestSharePermission2;
use OCA\Sharing\Tests\TestSharePermissionCategory;
use OCA\Sharing\Tests\TestSharePermissionCategory2;
use OCA\Sharing\Tests\TestShareRecipientType;
use OCA\Sharing\Tests\TestShareRecipientType2;
use OCA\Sharing\Tests\TestShareSourceType;
use OCA\Sharing\Tests\TestShareSourceType2;
use OCP\Server;
use OCP\Sharing\IRegistry;
use Test\TestCase;

class CapabilitiesTest extends TestCase {
	private IRegistry $registry;

	private Capabilities $capabilities;

	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(IRegistry::class);
		$this->registry->clear();

		$this->capabilities = Server::get(Capabilities::class);
	}

	protected function tearDown(): void {
		$this->registry->clear();

		parent::tearDown();
	}

	public function testGetCapabilities(): void {
		$this->registry->registerSourceType(new TestShareSourceType([]));
		$this->registry->registerSourceType(new TestShareSourceType2([]));
		$this->registry->registerRecipientType(new TestShareRecipientType([], [], []));
		$this->registry->registerRecipientType(new TestShareRecipientType2([], [], []));
		$this->registry->registerFeature(new TestShareFeature([]));
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeature::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeature::class, TestShareRecipientType::class);
		$this->registry->registerFeature(new TestShareFeature2([]));
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeature2::class, TestShareSourceType2::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeature2::class, TestShareRecipientType2::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory2());

		$this->assertEquals(
			[
				Application::APP_ID => [
					'api_versions' => ['v1'],
					'legacy' => [
						'max_sources' => 1,
						'max_recipients' => 1,
					],
					'source_types' => [
						[
							'type' => TestShareSourceType::class,
							'display_name' => 'TestShareSourceType',
						],
						[
							'type' => TestShareSourceType2::class,
							'display_name' => 'TestShareSourceType2',
						],
					],
					'recipient_types' => [
						[
							'type' => TestShareRecipientType::class,
							'display_name' => 'TestShareRecipientType',
						],
						[
							'type' => TestShareRecipientType2::class,
							'display_name' => 'TestShareRecipientType2',
						],
					],
					'features' => [
						[
							'type' => TestShareFeature::class,
							'compatible_source_types' => [TestShareSourceType::class],
							'compatible_recipient_types' => [TestShareRecipientType::class],
						],
						[
							'type' => TestShareFeature2::class,
							'compatible_source_types' => [TestShareSourceType2::class],
							'compatible_recipient_types' => [TestShareRecipientType2::class],
						],
					],
					'permission_categories' => [
						[
							'type' => TestSharePermissionCategory::class,
							'display_name' => 'TestSharePermissionCategory',
						],
						[
							'type' => TestSharePermissionCategory2::class,
							'display_name' => 'TestSharePermissionCategory2',
						],
					],
					'permissions' => [
						[
							'type' => TestSharePermission::class,
							'display_name' => 'TestSharePermission',
							'category' => TestSharePermissionCategory::class,
						],
						[
							'type' => TestSharePermission2::class,
							'display_name' => 'TestSharePermission2',
							'category' => TestSharePermissionCategory2::class,
						],
					],
				],
			],
			$this->capabilities->getCapabilities(),
		);
	}
}
