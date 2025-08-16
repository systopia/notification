<?php
declare(strict_types = 1);

namespace Civi\Notification\Tests\DTO;

use Civi\Notification\DTO\Change;
use Civi\Notification\DTO\ChangeSet;
use PHPUnit\Framework\TestCase;

final class ChangeSetTest extends TestCase {

  public function testAddGetAndCount(): void {
    $cs = new ChangeSet();
    $cs->add(new Change('status', 'Open', 'Closed'));
    $cs->add(new Change('priority', 'Low', 'High'));
    $this->assertSame(2, count($cs));
    $c = $cs->get('status');
    $this->assertNotNull($c);
    $this->assertSame('status', $c->field);
    $this->assertSame('Open', $c->before);
    $this->assertSame('Closed', $c->after);
    $all = $cs->all();
    $this->assertSame(2, count($all));
  }

}
