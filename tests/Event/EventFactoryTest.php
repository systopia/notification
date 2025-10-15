<?php
declare(strict_types = 1);

namespace Civi\Notification\Tests\Event;

use Civi\Notification\Event\EventFactory;
use Civi\Notification\Snapshot\SnapshotStore;
use PHPUnit\Framework\TestCase;

final class EventFactoryTest extends TestCase {

  public function testFromHookDetectsChanges(): void {
    $factory = new EventFactory(new SnapshotStore());
    $before = ['status' => 'Open', 'subject' => 'Foo'];
    $after = ['status' => 'Closed', 'subject' => 'Foo'];
    $e = $factory->fromHook('update', 'civicrm_case', 123, $before, $after, []);
    $this->assertNotNull($e);
    $this->assertSame('civicrm_case', $e->entity);
    $this->assertSame('update', $e->op);
    $this->assertSame(123, $e->id);
    $this->assertSame(1, count($e->changes));
    $this->assertSame('status', $e->changes->get('status')->field);
  }

  public function testReturnsNullOnNoChangeAndNotDelete(): void {
    $factory = new EventFactory(new SnapshotStore());
    $before = ['a' => 1];
    $after = ['a' => 1];
    $e = $factory->fromHook('update', 'civicrm_case', 1, $before, $after, []);
    $this->assertNull($e);
  }

  public function testDeleteReturnsEventEvenWithoutChanges(): void {
    $factory = new EventFactory(new SnapshotStore());
    $before = ['id' => 5];
    $after = [];
    $e = $factory->fromHook('delete', 'civicrm_case', 5, $before, $after, []);
    $this->assertNotNull($e);
    $this->assertSame('delete', $e->op);
  }

}
