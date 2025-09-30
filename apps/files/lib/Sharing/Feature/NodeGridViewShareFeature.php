<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Feature;

use OCA\Files\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\IShareFeature;

class NodeGridViewShareFeature implements IShareFeature {
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Show files and folders in a grid');
	}

	public function validateProperties(array $properties): bool {
		return array_keys($properties) === ['enabled'] && count($properties['enabled']) === 1 && ($properties['enabled'][0] === 'true' || $properties['enabled'][0] === 'false');
	}
}
