<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use OCP\Sharing\IShareFeature;
use OCP\Sharing\ISharePermission;
use OCP\Sharing\IShareRecipientType;
use OCP\Sharing\IShareSourceType;

/**
 * @psalm-type SharingSource = array{
 *     type: class-string<IShareSourceType>,
 *     value: non-empty-string,
 *     instance?: non-empty-string,
 *     display_name: non-empty-string,
 * }
 *
 * @psalm-type SharingIconSVG = array{
 *     svg: non-empty-string,
 * }
 *
 * @psalm-type SharingIconURL = array{
 *     // An absolute URL to an image suitable for light theme.
 *     light: non-empty-string,
 *     // An absolute URL to an image suitable for dark theme.
 *     dark: non-empty-string,
 * }
 *
 * @psalm-type SharingIcon = SharingIconSVG|SharingIconURL
 *
 * @psalm-type SharingRecipient = array{
 *     type: class-string<IShareRecipientType>,
 *     value: non-empty-string,
 *     instance?: non-empty-string,
 *     display_name: non-empty-string,
 *     icon?: SharingIcon,
 * }
 *
 * @psalm-type SharingOwner = array{
 *     user_id: non-empty-string,
 *     instance?: non-empty-string,
 *     display_name: non-empty-string,
 *     icon: SharingIconURL,
 * }
 *
 * @psalm-type SharingPartialShare = array{
 *     sources: non-empty-list<SharingSource>,
 *     recipients: non-empty-list<SharingRecipient>,
 *     properties: array<class-string<IShareFeature>, array<string, non-empty-list<string>>>,
 *     permissions: non-empty-list<class-string<ISharePermission>>,
 * }
 *
 * @psalm-type SharingShare = SharingPartialShare&array{
 *     id: non-empty-string,
 *     // Unix time in milliseconds
 *     last_updated: non-negative-int,
 *     owner: SharingOwner,
 * }
 */
class ResponseDefinitions {
}
