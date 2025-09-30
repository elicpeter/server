<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\RecipientType;

use OC\Core\AppInfo\Application;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IShare;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareRecipientTypeSearch;
use OCP\Sharing\Model\ShareIconSVG;
use OCP\Sharing\Model\ShareIconURL;
use OCP\Sharing\Model\ShareRecipient;
use RuntimeException;

class UserShareRecipientType implements IShareRecipientType, IShareRecipientTypeSearch {
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('User');
	}

	public function validateRecipient(string $recipient): bool {
		return Server::get(IUserManager::class)->userExists($recipient);
	}

	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return [$currentUser->getUID()];
	}

	public function getRecipientDisplayName(string $recipient): ?string {
		return Server::get(IUserManager::class)->getDisplayName($recipient);
	}

	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		/** @var IUser $user */
		$user = Server::get(IUserManager::class)->get($recipient);

		return new ShareIconURL(
			$user->getUserAvatarUrlLight(64),
			$user->getUserAvatarUrlDark(64),
		);
	}

	/**
	 * @return list<ShareRecipient>
	 */
	public function searchRecipients(string $query, int $limit, int $offset): array {
		// TODO: Maybe enable lookup?
		/** @var array{array{users: list<array>, exact: array{users: list<array>}}, bool} $results */
		$results = Server::get(ISearch::class)->search($query, [IShare::TYPE_USER], false, $limit, $offset);
		$results = array_merge($results[0]['exact']['users'], $results[0]['users']);

		return array_map(static function (array $result): ShareRecipient {
			if (!isset($result['value'])) {
				throw new RuntimeException('The value is missing.');
			}

			if (!is_array($result['value'])) {
				throw new RuntimeException('The value is not an array.');
			}

			if (!isset($result['value']['shareWith'])) {
				throw new RuntimeException('The shareWith is missing.');
			}

			if (!is_string($result['value']['shareWith'])) {
				throw new RuntimeException('The shareWith is not a string.');
			}

			if ($result['value']['shareWith'] === '') {
				throw new RuntimeException('The shareWith is empty.');
			}

			$uid = $result['value']['shareWith'];


			return new ShareRecipient(
				self::class,
				$uid,
			);
		}, $results);
	}
}
