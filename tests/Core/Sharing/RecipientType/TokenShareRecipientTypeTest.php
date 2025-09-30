<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Tests\Core\Sharing\RecipientType;

use OC\Core\Sharing\RecipientType\TokenShareRecipientType;
use OC\Share\Constants;
use Test\TestCase;

class TokenShareRecipientTypeTest extends TestCase {
	private TokenShareRecipientType $recipientType;

	public function setUp(): void {
		parent::setUp();

		$this->recipientType = new TokenShareRecipientType();
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient(str_repeat('a', Constants::MIN_TOKEN_LENGTH)));
		$this->assertFalse($this->recipientType->validateRecipient(str_repeat('a', Constants::MIN_TOKEN_LENGTH - 1)));

		$this->assertTrue($this->recipientType->validateRecipient(str_repeat('a', Constants::MAX_TOKEN_LENGTH)));
		$this->assertFalse($this->recipientType->validateRecipient(str_repeat('a', Constants::MAX_TOKEN_LENGTH + 1)));

		$this->assertTrue($this->recipientType->validateRecipient('a-1b-2'));
	}

	public function testGetRecipientValues(): void {
		$this->assertEquals([], $this->recipientType->getRecipients(null, null));
		$this->assertEquals([], $this->recipientType->getRecipients(null, 1));
		$this->assertEquals([''], $this->recipientType->getRecipients(null, ''));
		$this->assertEquals(['abc'], $this->recipientType->getRecipients(null, 'abc'));
	}
}
