<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Feature;

use Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\Security\IHasher;
use OCP\Security\PasswordContext;
use OCP\Server;
use OCP\Sharing\IShareFeature;
use OCP\Sharing\IShareFeatureFilter;
use OCP\Sharing\IShareFeatureModifyProperties;

class PasswordShareFeature implements IShareFeature, IShareFeatureModifyProperties, IShareFeatureFilter {
	public function validateProperties(array $properties): bool {
		if (array_keys($properties) === ['hash']) {
			if (count($properties['hash']) !== 1) {
				return false;
			}

			return Server::get(IHasher::class)->validate($properties['hash'][0]);
		}

		if (array_keys($properties) === ['password']) {
			if (count($properties['password']) !== 1) {
				return false;
			}

			try {
				Server::get(IEventDispatcher::class)->dispatchTyped(new ValidatePasswordPolicyEvent($properties['password'][0], PasswordContext::SHARING));
				return true;
			} catch (Exception) {
				return false;
			}
		}

		return false;
	}

	// TODO: Return password placeholder
	public function modifyProperties(array $properties): array {
		if (array_keys($properties) === ['hash']) {
			return $properties;
		}

		return ['hash' => [Server::get(IHasher::class)->hash($properties['password'][0])]];
	}

	/**
	 * @throws Exception
	 */
	public function isFiltered(?IUser $currentUser, mixed $arguments, array $properties): bool {
		if (!is_string($arguments)) {
			return true;
		}

		// TODO: Check if the hash has to be updated and save it.
		return !Server::get(IHasher::class)->verify($arguments, $properties['hash'][0]);
	}
}
