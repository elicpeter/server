<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\Sharing\Model\ShareRecipient;

interface IShareRecipientTypeSearch extends IShareRecipientType {
	/**
	 * Search for recipients.
	 *
	 * @param non-empty-string $query
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 */
	public function searchRecipients(string $query, int $limit, int $offset): array;
}
