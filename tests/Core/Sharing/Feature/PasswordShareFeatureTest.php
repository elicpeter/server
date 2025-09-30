<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Tests\Core\Sharing\Feature;

use OC\Core\Sharing\Feature\PasswordShareFeature;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\Security\IHasher;
use OCP\Server;
use Test\TestCase;

class PasswordShareFeatureTest extends TestCase {
	private PasswordShareFeature $feature;

	private IEventDispatcher $eventDispatcher;

	/**
	 * @var callable(ValidatePasswordPolicyEvent):void $validatePasswordPolicyEventListener
	 */
	private $validatePasswordPolicyEventListener;


	public function setUp(): void {
		parent::setUp();

		$this->feature = new PasswordShareFeature();

		$this->eventDispatcher = Server::get(IEventDispatcher::class);
		$this->validatePasswordPolicyEventListener = static function (ValidatePasswordPolicyEvent $event): void {
			if ($event->getPassword() !== 'secure') {
				throw new HintException('insecure');
			}
		};

		$this->eventDispatcher->addListener(ValidatePasswordPolicyEvent::class, $this->validatePasswordPolicyEventListener);
	}

	protected function tearDown(): void {
		$this->eventDispatcher->removeListener(ValidatePasswordPolicyEvent::class, $this->validatePasswordPolicyEventListener);

		parent::tearDown();
	}

	public function testValidateProperties(): void {
		$hash = Server::get(IHasher::class)->hash('secure');

		$this->assertTrue($this->feature->validateProperties(['hash' => [$hash]]));
		$this->assertTrue($this->feature->validateProperties(['password' => ['secure']]));

		$this->assertFalse($this->feature->validateProperties([]));
		$this->assertFalse($this->feature->validateProperties(['a' => []]));
		$this->assertFalse($this->feature->validateProperties(['hash' => [$hash], 'a' => ['']]));
		$this->assertFalse($this->feature->validateProperties(['password' => ['secure'], 'a' => ['']]));
		$this->assertFalse($this->feature->validateProperties(['hash' => []]));
		$this->assertFalse($this->feature->validateProperties(['password' => []]));
		$this->assertFalse($this->feature->validateProperties(['hash' => ['']]));
		$this->assertFalse($this->feature->validateProperties(['password' => ['']]));
		$this->assertFalse($this->feature->validateProperties(['hash' => [$hash, $hash]]));
		$this->assertFalse($this->feature->validateProperties(['password' => ['secure', 'secure']]));
		$this->assertFalse($this->feature->validateProperties(['password' => ['insecure']]));
	}

	public function testModifyProperties(): void {
		$properties = $this->feature->modifyProperties(['password' => ['123']]);
		$this->assertEquals(['hash'], array_keys($properties));
		$this->assertCount(1, $properties['hash']);
		$this->assertTrue(Server::get(IHasher::class)->validate($properties['hash'][0]));
		$this->assertTrue(Server::get(IHasher::class)->verify('123', $properties['hash'][0]));
	}

	public function testIsFiltered(): void {
		$this->assertFalse($this->feature->isFiltered(null, '123', ['hash' => [Server::get(IHasher::class)->hash('123')]]));
		$this->assertTrue($this->feature->isFiltered(null, '456', ['hash' => [Server::get(IHasher::class)->hash('123')]]));
		$this->assertTrue($this->feature->isFiltered(null, null, ['hash' => [Server::get(IHasher::class)->hash('123')]]));
	}
}
