<?php
declare(strict_types = 1);

namespace Civi\Notification\Command;

use Civi\Notification\Queue\DrainingQueueInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'notification:process-queue',
  description: 'Process pending notification messages'
)]
final class ProcessQueueCommand extends Command {

  public function __construct(private DrainingQueueInterface $queue) {
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $count = $this->queue->drain(function (array $payload): void {
    });
    $output->writeln(sprintf('Processed %d message(s).', $count));

    return Command::SUCCESS;
  }

  public function getQueue(): DrainingQueueInterface {
    return $this->queue;
  }

}
