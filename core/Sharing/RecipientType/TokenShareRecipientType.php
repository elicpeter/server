<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\RecipientType;

use OC\Core\AppInfo\Application;
use OC\Share\Constants;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\Model\ShareIconSVG;
use OCP\Sharing\Model\ShareIconURL;

// TODO: Redact token when getting shares as non-owner
class TokenShareRecipientType implements IShareRecipientType {
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Public link');
	}

	public function validateRecipient(string $recipient): bool {
		return preg_match('/^[a-z0-9-]{' . Constants::MIN_TOKEN_LENGTH . ',' . Constants::MAX_TOKEN_LENGTH . '}$/i', $recipient) === 1;
	}

	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (is_string($arguments)) {
			return [$arguments];
		}

		return [];
	}

	public function getRecipientDisplayName(string $recipient): ?string {
		return null;
	}

	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}
}
