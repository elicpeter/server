<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCA\Sharing\ResponseDefinitions;
use OCP\Sharing\Exception\AShareException;
use OCP\Sharing\IManager;
use OCP\Sharing\Model\Share;
use OCP\Sharing\Model\ShareAccessContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 */
class Update extends Command {
	public function __construct(
		private readonly IManager $manager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('share:update')
			->setDescription('update an existing share')
			->addArgument('data', InputArgument::REQUIRED, 'Share data');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var SharingShare $data */
		$data = json_decode((string)$input->getArgument('data'), true, 512, JSON_THROW_ON_ERROR);
		$share = Share::fromAPI($data);

		try {
			$this->manager->update(new ShareAccessContext(force: true), $share);
		} catch (AShareException $aShareException) {
			$output->writeln($aShareException->getMessage());
			return 1;
		}

		$output->writeln(json_encode($share->toAPI(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
		return 0;
	}
}
