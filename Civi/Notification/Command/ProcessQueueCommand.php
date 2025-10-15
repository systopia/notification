<?php
declare(strict_types = 1);

namespace Civi\Notification\Command;

use CRM_Queue_Runner;
use CRM_Queue_Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ProcessQueueCommand extends Command {
  protected static $defaultName = 'notification:process-queue';

  protected function configure(): void {
    $this
      ->setDescription('Process items in the notification queue (notification.jobs)')
      ->addOption('limit', NULL, InputOption::VALUE_REQUIRED, 'Max items to process this run (0 = no limit)', '0')
      ->addOption('until-empty', NULL, InputOption::VALUE_NONE, 'Ignore limit and run until queue is empty');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $limit = (int) ($input->getOption('limit') ?? 0);
    $untilEmpty = (bool) $input->getOption('until-empty');

    $svc = new CRM_Queue_Service();
    $q   = $svc->load(['type' => 'Sql', 'name' => 'notification.jobs', 'reset' => FALSE]);

    $runner = new CRM_Queue_Runner([
      'queue'     => $q,
      'errorMode' => CRM_Queue_Runner::ERROR_CONTINUE,
    ]);

    $processed = 0;
    if ($untilEmpty) {
      while ($runner->runNext()) {
        $processed++;
      }
    }
    else {
      while (($limit <= 0 || $processed < $limit) && $runner->runNext()) {
        $processed++;
      }
    }

    $output->writeln("<info>Processed {$processed} queue item(s).</info>");
    return Command::SUCCESS;
  }

}
