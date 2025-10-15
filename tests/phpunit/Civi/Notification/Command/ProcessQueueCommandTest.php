<?php
declare(strict_types = 1);

namespace Civi\Notification\Tests\Civi\Notification\Command;

use Civi\Notification\Command\ProcessQueueCommand;
use Civi\Notification\Support\InMemoryQueue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Civi\Notification\Command\ProcessQueueCommand
 */
final class ProcessQueueCommandTest extends TestCase {

  public function testCommandDrainsQueueAndSucceeds(): void {
    $queue = new InMemoryQueue();
    foreach ([
               ['type' => 'notification.event', 'entity' => 'civicrm_case', 'op' => 'update', 'id' => 1],
               ['type' => 'notification.event', 'entity' => 'civicrm_contact', 'op' => 'create', 'id' => 2],
    ] as $payload) {
      $queue->enqueue($payload);
    }

    $cmd    = new ProcessQueueCommand($queue);
    $input  = new ArrayInput([]);
    $output = new BufferedOutput();

    $code = $cmd->run($input, $output);

    self::assertSame(Command::SUCCESS, $code);
    self::assertStringContainsString('Processed 2 message(s).', $output->fetch());
  }

  public function testCommandWorksOnEmptyQueue(): void {
    $queue  = new InMemoryQueue();
    $cmd    = new ProcessQueueCommand($queue);
    $input  = new ArrayInput([]);
    $output = new BufferedOutput();

    $code = $cmd->run($input, $output);

    self::assertSame(Command::SUCCESS, $code);
    self::assertSame('Processed 0 message(s).' . PHP_EOL, $output->fetch());
  }

}
