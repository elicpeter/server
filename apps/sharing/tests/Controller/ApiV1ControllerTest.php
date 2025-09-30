<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OCA\Sharing\Controller\ApiV1Controller;
use OCA\Sharing\ResponseDefinitions;
use OCA\Sharing\Tests\AbstractApiTests;
use OCA\Sharing\Tests\TestShareFeature;
use OCA\Sharing\Tests\TestShareFeature2;
use OCA\Sharing\Tests\TestShareFeatureFilter;
use OCA\Sharing\Tests\TestShareFeatureModifyProperties;
use OCA\Sharing\Tests\TestSharePermission;
use OCA\Sharing\Tests\TestSharePermissionCategory;
use OCA\Sharing\Tests\TestSharePermissionCategory2;
use OCA\Sharing\Tests\TestShareRecipientType;
use OCA\Sharing\Tests\TestShareRecipientType2;
use OCA\Sharing\Tests\TestShareRecipientTypeArguments;
use OCA\Sharing\Tests\TestShareSourceType;
use OCA\Sharing\Tests\TestShareSourceType2;
use OCP\AppFramework\Http;
use OCP\Server;
use OCP\Sharing\Model\ShareRecipient;
use PHPUnit\Framework\Attributes\Group;

/**
 * The abstract tests are executed as the owner, allowing all operations.
 *
 * @psalm-import-type SharingShare from ResponseDefinitions
 */
#[Group(name: 'DB')]
class ApiV1ControllerTest extends AbstractApiTests {
	private ApiV1Controller $controller;

	public function setUp(): void {
		parent::setUp();

		self::loginAsUser($this->owner1->getUID());

		$this->controller = Server::get(ApiV1Controller::class);
	}

	protected function createShare(array $data): array {
		$response = $this->controller->createShare($data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		return $responseData;
	}

	protected function getShare(string $shareID): array {
		$response = $this->controller->getShare($shareID);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		return $responseData;
	}

	protected function deleteShare(string $shareID): void {
		$response = $this->controller->deleteShare($shareID);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus(), var_export($responseData, true));
	}

	protected function updateShare(array $data): array {
		$response = $this->controller->updateShare($data['id'], $data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		return $responseData;
	}

	protected function updateShareExpectConflict(array $data): void {
		$response = $this->controller->updateShare($data['id'], $data);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CONFLICT, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals('The share has been updated in the meantime, so you cannot update it.', $responseData);
	}


	public function testSearchRecipients(): void {
		$displayNames = [
			'recipient1a' => 'Recipient 1A',
			'recipient1b' => 'Recipient 1B',
			'recipient1c' => 'Recipient 1C',
			'recipient2a' => 'Recipient 2A',
			'recipient2b' => 'Recipient 2B',
			'recipient2c' => 'Recipient 2C',
		];

		$recipientType1 = new TestShareRecipientType($displayNames, [], []);
		$recipientType2 = new TestShareRecipientType2($displayNames, [], []);
		$this->registry->registerRecipientType($recipientType1);
		$this->registry->registerRecipientType($recipientType2);

		$recipient1a = new ShareRecipient(TestShareRecipientType::class, 'recipient1a');
		$recipientType1->searchRecipients[] = $recipient1a;
		$recipient1b = new ShareRecipient(TestShareRecipientType::class, 'recipient1b');
		$recipientType1->searchRecipients[] = $recipient1b;
		$recipient1c = new ShareRecipient(TestShareRecipientType::class, 'recipient1c');
		$recipientType1->searchRecipients[] = $recipient1c;
		$recipient2a = new ShareRecipient(TestShareRecipientType2::class, 'recipient2a');
		$recipientType2->searchRecipients[] = $recipient2a;
		$recipient2b = new ShareRecipient(TestShareRecipientType2::class, 'recipient2b');
		$recipientType2->searchRecipients[] = $recipient2b;
		$recipient2c = new ShareRecipient(TestShareRecipientType2::class, 'recipient2c');
		$recipientType2->searchRecipients[] = $recipient2c;

		$response = $this->controller->searchRecipients(null, 'recipient');
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals([
			['type' => TestShareRecipientType::class, 'value' => 'recipient1a', 'display_name' => $displayNames['recipient1a']],
			['type' => TestShareRecipientType::class, 'value' => 'recipient1b', 'display_name' => $displayNames['recipient1b']],
			['type' => TestShareRecipientType::class, 'value' => 'recipient1c', 'display_name' => $displayNames['recipient1c']],
			['type' => TestShareRecipientType2::class, 'value' => 'recipient2a', 'display_name' => $displayNames['recipient2a']],
			['type' => TestShareRecipientType2::class, 'value' => 'recipient2b', 'display_name' => $displayNames['recipient2b']],
			['type' => TestShareRecipientType2::class, 'value' => 'recipient2c', 'display_name' => $displayNames['recipient2c']],
		], $responseData);

		$response = $this->controller->searchRecipients(TestShareRecipientType::class, 'recipient');
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals([
			['type' => TestShareRecipientType::class, 'value' => 'recipient1a', 'display_name' => $displayNames['recipient1a']],
			['type' => TestShareRecipientType::class, 'value' => 'recipient1b', 'display_name' => $displayNames['recipient1b']],
			['type' => TestShareRecipientType::class, 'value' => 'recipient1c', 'display_name' => $displayNames['recipient1c']],
		], $responseData);

		$response = $this->controller->searchRecipients(TestShareRecipientType::class, 'recipient', 1);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals([
			['type' => TestShareRecipientType::class, 'value' => 'recipient1a', 'display_name' => $displayNames['recipient1a']],
		], $responseData);

		$response = $this->controller->searchRecipients(TestShareRecipientType::class, 'recipient', offset: 1);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals([
			['type' => TestShareRecipientType::class, 'value' => 'recipient1b', 'display_name' => $displayNames['recipient1b']],
			['type' => TestShareRecipientType::class, 'value' => 'recipient1c', 'display_name' => $displayNames['recipient1c']],
		], $responseData);

		/** @psalm-suppress ArgumentTypeCoercion */
		$response = $this->controller->searchRecipients('abc', 'recipient');
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals('Invalid operation parameter: The recipient type is not registered.', $responseData);
	}

	public function testSearchRecipientsUniqueDisplayNames(): void {
		$recipientType1 = new TestShareRecipientType(['recipient1' => 'Recipient'], [], []);
		$recipientType2 = new TestShareRecipientType2(['recipient2' => 'Recipient'], [], []);
		$this->registry->registerRecipientType($recipientType1);
		$this->registry->registerRecipientType($recipientType2);

		$recipient1 = new ShareRecipient(TestShareRecipientType::class, 'recipient1');
		$recipientType1->searchRecipients[] = $recipient1;
		$recipient2 = new ShareRecipient(TestShareRecipientType2::class, 'recipient2');
		$recipientType2->searchRecipients[] = $recipient2;

		$response = $this->controller->searchRecipients(null, 'recipient');
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals([
			['type' => TestShareRecipientType::class, 'value' => 'recipient1', 'display_name' => 'Recipient (TestShareRecipientType: recipient1)'],
			['type' => TestShareRecipientType2::class, 'value' => 'recipient2', 'display_name' => 'Recipient (TestShareRecipientType2: recipient2)'],
		], $responseData);
	}

	public function testSearchRecipientsIcons(): void {
		$recipientType = new TestShareRecipientType(['svg' => 'SVG', 'url' => 'URL'], [], []);
		$this->registry->registerRecipientType($recipientType);

		$recipient1 = new ShareRecipient(TestShareRecipientType::class, 'svg');
		$recipientType->searchRecipients[] = $recipient1;
		$recipient2 = new ShareRecipient(TestShareRecipientType::class, 'url');
		$recipientType->searchRecipients[] = $recipient2;

		$response = $this->controller->searchRecipients(null, 'icon');
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals([
			['type' => TestShareRecipientType::class, 'value' => 'svg', 'display_name' => 'SVG', 'icon' => ['svg' => '<svg/>']],
			['type' => TestShareRecipientType::class, 'value' => 'url', 'display_name' => 'URL', 'icon' => ['light' => 'https://example.com/light.png', 'dark' => 'https://example.com/dark.png',]],
		], $responseData);
	}

	public function testCreateShareModifyProperties(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source' => 'Source']));
		$this->registry->registerRecipientType(new TestShareRecipientType(['recipient1' => 'Recipient 1', 'recipient2' => 'Recipient 2'], ['recipient1'], []));
		$this->registry->registerFeature(new TestShareFeatureModifyProperties());
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeatureModifyProperties::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeatureModifyProperties::class, TestShareRecipientType::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());

		$data = [
			'sources' => [['type' => TestShareSourceType::class, 'value' => 'source', 'display_name' => 'Source']],
			'recipients' => [['type' => TestShareRecipientType::class, 'value' => 'recipient1', 'display_name' => 'Recipient 1 (TestShareRecipientType: recipient1)'], ['type' => TestShareRecipientType::class, 'value' => 'recipient2', 'display_name' => 'Recipient 2 (TestShareRecipientType2: recipient2)']],
			'properties' => [TestShareFeatureModifyProperties::class => ['before' => ['valid']]],
			'permissions' => [TestSharePermission::class],
		];
		$response = $this->controller->createShare($data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		$this->assertEquals([TestShareFeatureModifyProperties::class => ['after' => ['valid']]], $responseData['properties']);
	}

	public function testGetShareAsRecipient(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source' => 'Source']));
		$this->registry->registerRecipientType(new TestShareRecipientType(['recipient1' => 'Recipient 1', 'recipient2' => 'Recipient 2'], ['recipient1'], []));
		$this->registry->registerFeature(new TestShareFeature(['key']));
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeature::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeature::class, TestShareRecipientType::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());

		$data = [
			'sources' => [['type' => TestShareSourceType::class, 'value' => 'source', 'display_name' => 'Source']],
			'recipients' => [['type' => TestShareRecipientType::class, 'value' => 'recipient1', 'display_name' => 'Recipient 1'], ['type' => TestShareRecipientType::class, 'value' => 'recipient2', 'display_name' => 'Recipient 2']],
			'properties' => [TestShareFeature::class => ['key' => ['value']]],
			'permissions' => [TestSharePermission::class],
		];
		$response = $this->controller->createShare($data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$data['owner'] = [
			'user_id' => $this->owner1->getUID(),
			'display_name' => $this->owner1->getDisplayName(),
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner1/64',
				'dark' => 'http://localhost/index.php/avatar/owner1/64/dark',
			],
		];
		$this->assertEquals($data, $responseData);

		self::logout();

		$response = $this->controller->getShare($id);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$this->assertEquals($data, $responseData);
	}

	public function testGetShareAsRecipientWithRecipientArguments(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source' => 'Source']));
		$this->registry->registerRecipientType(new TestShareRecipientTypeArguments());
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());

		$data = [
			'sources' => [['type' => TestShareSourceType::class, 'value' => 'source', 'display_name' => 'Source']],
			'recipients' => [['type' => TestShareRecipientTypeArguments::class, 'value' => 'secret', 'display_name' => 'secret']],
			'properties' => [],
			'permissions' => [TestSharePermission::class],
		];
		$response = $this->controller->createShare($data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$data['owner'] = [
			'user_id' => $this->owner1->getUID(),
			'display_name' => $this->owner1->getDisplayName(),
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner1/64',
				'dark' => 'http://localhost/index.php/avatar/owner1/64/dark',
			],
		];
		$this->assertEquals($data, $responseData);

		self::logout();

		$response = $this->controller->getShare($id);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals('Share ' . $id . ' not found.', $responseData);

		$response = $this->controller->getShare($id, [TestShareRecipientTypeArguments::class => 'secret']);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$this->assertEquals($data, $responseData);
	}

	public function testGetShareAsNonRecipient(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source' => 'Source']));
		$this->registry->registerRecipientType(new TestShareRecipientType(['recipient' => 'Recipient'], [], []));
		$this->registry->registerFeature(new TestShareFeature(['key']));
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeature::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeature::class, TestShareRecipientType::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());

		$data = [
			'sources' => [['type' => TestShareSourceType::class, 'value' => 'source', 'display_name' => 'Source']],
			'recipients' => [['type' => TestShareRecipientType::class, 'value' => 'recipient', 'display_name' => 'Recipient']],
			'properties' => [TestShareFeature::class => ['key' => ['value']]],
			'permissions' => [TestSharePermission::class],
		];
		$response = $this->controller->createShare($data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$data['owner'] = [
			'user_id' => $this->owner1->getUID(),
			'display_name' => $this->owner1->getDisplayName(),
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner1/64',
				'dark' => 'http://localhost/index.php/avatar/owner1/64/dark',
			],
		];
		$this->assertEquals($data, $responseData);

		self::logout();

		$response = $this->controller->getShare($id);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals('Share ' . $id . ' not found.', $responseData);
	}

	public function testGetShareFilteredProperties(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source' => 'Source']));
		$this->registry->registerRecipientType(new TestShareRecipientType(['recipient' => 'Recipient'], ['recipient'], []));
		$this->registry->registerFeature(new TestShareFeatureFilter());
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeatureFilter::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeatureFilter::class, TestShareRecipientType::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());

		$data = [
			'sources' => [['type' => TestShareSourceType::class, 'value' => 'source', 'display_name' => 'Source']],
			'recipients' => [['type' => TestShareRecipientType::class, 'value' => 'recipient', 'display_name' => 'Recipient']],
			'properties' => [TestShareFeatureFilter::class => ['filtered' => ['false']]],
			'permissions' => [TestSharePermission::class],
		];
		$response = $this->controller->createShare($data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$data['owner'] = [
			'user_id' => $this->owner1->getUID(),
			'display_name' => $this->owner1->getDisplayName(),
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner1/64',
				'dark' => 'http://localhost/index.php/avatar/owner1/64/dark',
			],
		];
		$this->assertEquals($data, $responseData);

		self::logout();

		$response = $this->controller->getShare($id);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		$lastUpdated = $responseData['last_updated'];
		unset($responseData['last_updated']);
		$this->assertEquals($data, $responseData);

		self::loginAsUser($this->owner1->getUID());

		$data['id'] = $id;
		$data['last_updated'] = $lastUpdated;
		$data['owner'] = ['user_id' => $this->owner1->getUID(), 'display_name' => $this->owner1->getDisplayName(), 'icon' => ['light' => 'http://localhost/index.php/avatar/owner1/64', 'dark' => 'http://localhost/index.php/avatar/owner1/64/dark']];
		$data['properties'] = [TestShareFeatureFilter::class => ['filtered' => ['true']]];
		$response = $this->controller->updateShare($id, $data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		unset($data['last_updated']);
		$this->assertEquals($data, $responseData);

		self::logout();

		$response = $this->controller->getShare($id);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals('Share ' . $id . ' not found.', $responseData);
	}

	public function testGetShareFilteredArguments(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source' => 'Source']));
		$this->registry->registerRecipientType(new TestShareRecipientType(['recipient' => 'Recipient'], ['recipient'], []));
		$this->registry->registerFeature(new TestShareFeatureFilter());
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeatureFilter::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeatureFilter::class, TestShareRecipientType::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());

		$data = [
			'sources' => [['type' => TestShareSourceType::class, 'value' => 'source', 'display_name' => 'Source']],
			'recipients' => [['type' => TestShareRecipientType::class, 'value' => 'recipient', 'display_name' => 'Recipient']],
			'properties' => [],
			'permissions' => [TestSharePermission::class],
		];
		$response = $this->controller->createShare($data);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$data['owner'] = [
			'user_id' => $this->owner1->getUID(),
			'display_name' => $this->owner1->getDisplayName(),
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner1/64',
				'dark' => 'http://localhost/index.php/avatar/owner1/64/dark',
			],
		];
		$this->assertEquals($data, $responseData);

		self::logout();

		$response = $this->controller->getShare($id, [TestShareFeatureFilter::class => 'filtered']);
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), var_export($responseData, true));
		$this->assertEquals('Share ' . $id . ' not found.', $responseData);

		$response = $this->controller->getShare($id);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$this->assertEquals($data, $responseData);
	}

	public function testGetShares(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source1' => 'Source 1']));
		$this->registry->registerSourceType(new TestShareSourceType2(['source2' => 'Source 2']));
		$this->registry->registerRecipientType(new TestShareRecipientType(['recipient1' => 'Recipient 1'], [], []));
		$this->registry->registerRecipientType(new TestShareRecipientType2(['recipient2' => 'Recipient 2'], [], []));
		$this->registry->registerFeature(new TestShareFeature(['key1']));
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeature::class, TestShareSourceType::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeature::class, TestShareRecipientType::class);
		$this->registry->registerFeature(new TestShareFeature2(['key2']));
		$this->registry->registerFeatureCompatibleWithSourceType(TestShareFeature2::class, TestShareSourceType2::class);
		$this->registry->registerFeatureCompatibleWithRecipientType(TestShareFeature2::class, TestShareRecipientType2::class);
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory());
		$this->registry->registerPermissionCategory(new TestSharePermissionCategory2());

		$data1 = [
			'sources' => [
				[
					'type' => TestShareSourceType::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
				],
				[
					'type' => TestShareSourceType2::class,
					'value' => 'source2',
					'display_name' => 'Source 2',
				],
			],
			'recipients' => [
				[
					'type' => TestShareRecipientType::class,
					'value' => 'recipient1',
					'display_name' => 'Recipient 1',
				],
				[
					'type' => TestShareRecipientType2::class,
					'value' => 'recipient2',
					'display_name' => 'Recipient 2',
				],
			],
			'properties' => [
				TestShareFeature::class => [
					'key1' => ['value1'],
				],
				TestShareFeature2::class => [
					'key2' => ['value2'],
				],
			],
			'permissions' => [
				TestSharePermission::class,
			],
		];
		$response = $this->controller->createShare($data1);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$data1['owner'] = [
			'user_id' => $this->owner1->getUID(),
			'display_name' => $this->owner1->getDisplayName(),
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner1/64',
				'dark' => 'http://localhost/index.php/avatar/owner1/64/dark',
			],
		];
		$this->assertEquals($data1, $responseData);

		$data2 = [
			'sources' => [
				[
					'type' => TestShareSourceType::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
				],
				[
					'type' => TestShareSourceType2::class,
					'value' => 'source2',
					'display_name' => 'Source 2',
				],
			],
			'recipients' => [
				[
					'type' => TestShareRecipientType::class,
					'value' => 'recipient1',
					'display_name' => 'Recipient 1',
				],
				[
					'type' => TestShareRecipientType2::class,
					'value' => 'recipient2',
					'display_name' => 'Recipient 2',
				],
			],
			'properties' => [
				TestShareFeature::class => [
					'key1' => ['value1'],
				],
				TestShareFeature2::class => [
					'key2' => ['value2'],
				],
			],
			'permissions' => [
				TestSharePermission::class,
			],
		];
		$response = $this->controller->createShare($data2);
		/** @var SharingShare $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus(), var_export($responseData, true));
		$this->assertArrayHasKey('id', $responseData);
		unset($responseData['id']);
		$this->assertArrayHasKey('last_updated', $responseData);
		unset($responseData['last_updated']);
		$data2['owner'] = [
			'user_id' => $this->owner1->getUID(),
			'display_name' => $this->owner1->getDisplayName(),
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner1/64',
				'dark' => 'http://localhost/index.php/avatar/owner1/64/dark',
			],
		];
		$this->assertEquals($data2, $responseData);

		$response = $this->controller->getShares();
		/** @var list<SharingShare> $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertCount(2, $responseData);
		$this->assertArrayHasKey('id', $responseData[0]);
		$this->assertArrayHasKey('last_updated', $responseData[0]);
		$this->assertArrayHasKey('id', $responseData[1]);
		$this->assertArrayHasKey('last_updated', $responseData[1]);
		unset($responseData[0]['id'], $responseData[0]['last_updated'], $responseData[1]['id'], $responseData[1]['last_updated']);
		$this->assertEquals($data1, $responseData[0]);
		$this->assertEquals($data2, $responseData[1]);

		$response = $this->controller->getShares(TestShareSourceType::class);
		/** @var list<SharingShare> $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertCount(2, $responseData);
		$this->assertArrayHasKey('id', $responseData[0]);
		$this->assertArrayHasKey('last_updated', $responseData[0]);
		$this->assertArrayHasKey('id', $responseData[1]);
		$this->assertArrayHasKey('last_updated', $responseData[1]);
		unset($responseData[0]['id'], $responseData[0]['last_updated'], $responseData[1]['id'], $responseData[1]['last_updated']);
		$this->assertEquals($data1, $responseData[0]);
		$this->assertEquals($data2, $responseData[1]);

		/** @psalm-suppress ArgumentTypeCoercion */
		$response = $this->controller->getShares('invalid');
		/** @var list<SharingShare> $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertCount(0, $responseData);

		$response = $this->controller->getShares(limit: 1);
		/** @var list<SharingShare> $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertCount(1, $responseData);
		$this->assertArrayHasKey('id', $responseData[0]);
		$this->assertArrayHasKey('last_updated', $responseData[0]);
		$lastShareId = $responseData[0]['id'];
		unset($responseData[0]['id'], $responseData[0]['last_updated']);
		$this->assertEquals($data1, $responseData[0]);

		$response = $this->controller->getShares(lastShareId: $lastShareId);
		/** @var list<SharingShare> $responseData */
		$responseData = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), var_export($responseData, true));
		$this->assertCount(1, $responseData);
		$this->assertArrayHasKey('id', $responseData[0]);
		$this->assertArrayHasKey('last_updated', $responseData[0]);
		unset($responseData[0]['id'], $responseData[0]['last_updated']);
		$this->assertEquals($data2, $responseData[0]);
	}
}
