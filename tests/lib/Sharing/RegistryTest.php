<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Test\Sharing;

use OCA\Sharing\Tests\TestShareFeature;
use OCA\Sharing\Tests\TestSharePermissionCategory;
use OCA\Sharing\Tests\TestShareRecipientType;
use OCA\Sharing\Tests\TestShareRecipientType2;
use OCA\Sharing\Tests\TestShareSourceType;
use OCA\Sharing\Tests\TestShareSourceType2;
use OCP\Server;
use OCP\Sharing\IRegistry;
use RuntimeException;
use Test\TestCase;

class RegistryTest extends TestCase {
	private IRegistry $registry;

	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(IRegistry::class);
		$this->registry->clear();
	}

	protected function tearDown(): void {
		$this->registry->clear();

		parent::tearDown();
	}

	public function testClear(): void {
		$this->registry->registerSourceType(new TestShareSourceType([]));
		$this->registry->registerRecipientType(new TestShareRecipientType([], [], []));
		$this->registry->registerFeature(new TestShareFeature([]));
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeature::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeature::class, TestShareRecipientType::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());

		$this->registry->clear();

		$this->assertEquals([], $this->registry->getSourceTypes());
		$this->assertEquals([], $this->registry->getRecipientTypes());
		$this->assertEquals([], $this->registry->getFeatures());
		$this->assertEquals([], $this->registry->getSourceTypesCompatibleWithFeature(TestShareFeature::class));
		$this->assertEquals([], $this->registry->getRecipientTypesCompatibleWithFeature(TestShareFeature::class));
		$this->assertEquals([], $this->registry->getPermissionCategories());
	}

	public function testRegisterSourceType(): void {
		$sourceType = new TestShareSourceType([]);
		$this->registry->registerSourceType($sourceType);

		$this->assertEquals([TestShareSourceType::class => $sourceType], $this->registry->getSourceTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerSourceType($sourceType);
	}

	public function testRegisterRecipientType(): void {
		$recipientType = new TestShareRecipientType([], [], []);
		$this->registry->registerRecipientType($recipientType);

		$this->assertEquals([TestShareRecipientType::class => $recipientType], $this->registry->getRecipientTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerRecipientType($recipientType);
	}

	public function testRegisterFeature(): void {
		$feature = new TestShareFeature([]);
		$this->registry->registerFeature($feature);

		$this->assertEquals([TestShareFeature::class => $feature], $this->registry->getFeatures());

		$this->expectException(RuntimeException::class);
		$this->registry->registerFeature($feature);
	}

	public function testRegisterFeatureCompatibleWithSourceType(): void {
		$sourceType = new TestShareSourceType([]);
		$this->registry->registerSourceType($sourceType);

		$feature = new TestShareFeature([]);
		$this->registry->registerFeature($feature);
		$this->registry->registerFeatureCompatibleWithSourceType($feature::class, $sourceType::class);
		$this->registry->registerFeatureCompatibleWithSourceType($feature::class, $sourceType::class);

		$this->assertEquals([$sourceType::class], $this->registry->getSourceTypesCompatibleWithFeature($feature::class));

		$this->registry->registerFeatureCompatibleWithSourceType($feature::class, TestShareSourceType2::class);
		$this->expectException(RuntimeException::class);
		$this->registry->getSourceTypesCompatibleWithFeature($feature::class);
	}

	public function testRegisterFeatureCompatibleWithRecipientType(): void {
		$recipientType = new TestShareRecipientType([], [], []);
		$this->registry->registerRecipientType($recipientType);

		$feature = new TestShareFeature([]);
		$this->registry->registerFeature($feature);
		$this->registry->registerFeatureCompatibleWithRecipientType($feature::class, $recipientType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType($feature::class, $recipientType::class);

		$this->assertEquals([$recipientType::class], $this->registry->getRecipientTypesCompatibleWithFeature($feature::class));

		$this->registry->registerFeatureCompatibleWithRecipientType($feature::class, TestShareRecipientType2::class);
		$this->expectException(RuntimeException::class);
		$this->registry->getRecipientTypesCompatibleWithFeature($feature::class);
	}

	public function testRegisterPermissionCategory(): void {
		$permissionCategory = new TestSharePermissionCategory();
		$this->registry->registerPermissionCategory($permissionCategory);

		$this->assertEquals([TestSharePermissionCategory::class => $permissionCategory], $this->registry->getPermissionCategories());

		$this->expectException(RuntimeException::class);
		$this->registry->registerPermissionCategory($permissionCategory);
	}
}
