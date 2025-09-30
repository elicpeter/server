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
 * @psalm-import-type SharingPartialShare from ResponseDefinitions
 */
class Create extends Command {
	public function __construct(
		private readonly IManager $manager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('share:create')
			->setDescription('create a new share')
			->addArgument('owner', InputArgument::REQUIRED, 'User ID of the owner')
			->addArgument('data', InputArgument::REQUIRED, 'Share data');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var non-empty-string $owner */
		$owner = (string)$input->getArgument('owner');
		/** @var SharingPartialShare $data */
		$data = json_decode((string)$input->getArgument('data'), true, 512, JSON_THROW_ON_ERROR);
		$share = Share::fromAPI($this->manager->completePartialShareData($data, $owner));

		try {
			$this->manager->insert(new ShareAccessContext(force: true), $share);
			$output->writeln(json_encode($share->toAPI(), JSON_THROW_ON_ERROR));
		} catch (AShareException $aShareException) {
			$output->writeln($aShareException->getMessage());
			return 1;
		}

		return 0;
	}
}
