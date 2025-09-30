<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCP\Sharing\Exception\AShareException;
use OCP\Sharing\IManager;
use OCP\Sharing\Model\ShareAccessContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Command {
	public function __construct(
		private readonly IManager $manager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('share:delete')
			->setDescription('delete an existing share')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$id = (string)$input->getArgument('id');

		try {
			$this->manager->delete(new ShareAccessContext(force: true), $id);
		} catch (AShareException $aShareException) {
			$output->writeln($aShareException->getMessage());
			return 1;
		}

		return 0;
	}
}
