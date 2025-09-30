<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OCA\Files\Sharing\Feature\NodeGridViewShareFeature;
use OCP\Sharing\IShareFeature;
use Test\TestCase;

class NodeGridViewShareFeatureTest extends TestCase {
	private IShareFeature $feature;

	public function setUp(): void {
		parent::setUp();

		$this->feature = new NodeGridViewShareFeature();
	}

	public function testValidateProperties(): void {
		$this->assertTrue($this->feature->validateProperties(['enabled' => ['true']]));
		$this->assertTrue($this->feature->validateProperties(['enabled' => ['false']]));

		$this->assertFalse($this->feature->validateProperties([]));
		$this->assertFalse($this->feature->validateProperties(['a' => ['true']]));
		$this->assertFalse($this->feature->validateProperties(['enabled' => ['true'], 'a' => ['true']]));

		$this->assertFalse($this->feature->validateProperties(['enabled' => []]));
		$this->assertFalse($this->feature->validateProperties(['enabled' => ['true', 'false']]));

		$this->assertFalse($this->feature->validateProperties(['enabled' => ['']]));
		$this->assertFalse($this->feature->validateProperties(['enabled' => ['a']]));
	}
}
