<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\SourceType;

use OCA\Files\AppInfo\Application;
use OCA\Files\Sharing\Permission\NodeCreateSharePermission;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermission;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermission;
use OCA\Files\Sharing\Permission\NodeReadSharePermission;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermission;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\ISharePermission;
use OCP\Sharing\IShareSourceType;

class NodeShareSourceType implements IShareSourceType {
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('File or folder');
	}

	public function validateSource(IUser $owner, string $source): bool {
		return Server::get(IRootFolder::class)->getUserFolder($owner->getUID())->getFirstNodeById((int)$source) !== null;
	}

	public function getSourceDisplayName(string $source): ?string {
		$displayName = Server::get(IRootFolder::class)->getFirstNodeById((int)$source)?->getName();
		if ($displayName === '') {
			return null;
		}

		return $displayName;
	}

	/**
	 * @return non-empty-list<ISharePermission>
	 */
	public function getPermissions(): array {
		return [
			(new NodeCreateSharePermission()),
			(new NodeReadSharePermission()),
			(new NodeUpdateSharePermission()),
			(new NodeDeleteSharePermission()),
			(new NodeDownloadSharePermission()),
		];
	}
}
