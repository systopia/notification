<?php
declare(strict_types = 1);

namespace Civi\Notification\Tests\Queue;

use Civi\Notification\DTO\Change;
use Civi\Notification\DTO\ChangeSet;
use Civi\Notification\DTO\NotificationEvent;
use Civi\Notification\Queue\Enqueuer;
use Civi\Notification\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;

final class EnqueuerTest extends TestCase {

  public function testEnqueueEventBuildsPayload(): void {
    $queue = new class() implements QueueInterface {
      /** @var array<int, array<string, mixed>> */
      public array $payloads = [];

      /**
       *
       * @param array<mixed> $payload
       */
      public function enqueue(array $payload): void {
        $this->payloads[] = $payload;
      }

    };

    $enq = new Enqueuer($queue);
    $cs = new ChangeSet();
    $cs->add(new Change('status', 'Open', 'Closed'));

    $ev = new NotificationEvent(
      'civicrm_case',
      'update',
      7,
      ['status' => 'Open'],
      ['status' => 'Closed'],
      $cs,
      ['k' => 'v']
    );

    $enq->enqueueEvent($ev);

    $this->assertCount(1, $queue->payloads);

    /** @var array{
     *   type:string,
     *   entity:string,
     *   op:string,
     *   id:int,
     *   changes:list<array{field:string,before:mixed,after:mixed}>,
     *   context:array<string,mixed>
     * } $p
     */
    $p = $queue->payloads[0];

    $this->assertSame('notification.event', $p['type']);
    $this->assertSame('civicrm_case', $p['entity']);
    $this->assertSame('update', $p['op']);
    $this->assertSame(7, $p['id']);

    $this->assertSame('status', $p['changes'][0]['field']);
    $this->assertSame('Open', $p['changes'][0]['before']);
    $this->assertSame('Closed', $p['changes'][0]['after']);

    $this->assertSame(['k' => 'v'], $p['context']);
  }

}
