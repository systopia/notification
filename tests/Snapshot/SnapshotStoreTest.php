<?php
declare(strict_types = 1);

namespace Civi\Notification\Tests\Snapshot;

use Civi\Notification\Snapshot\SnapshotStore;
use PHPUnit\Framework\TestCase;

final class SnapshotStoreTest extends TestCase {

  public function testPutAndGet(): void {
    $s = new SnapshotStore();
    $s->put('civicrm_case', 'update', 10, ['status' => 'Open']);
    $out = $s->get('civicrm_case', 'update', 10);
    $this->assertSame(['status' => 'Open'], $out);
  }

}
