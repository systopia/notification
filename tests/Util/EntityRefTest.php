<?php
declare(strict_types = 1);

namespace Civi\Notification\Tests\Util;

use Civi\Notification\Util\EntityRef;
use PHPUnit\Framework\TestCase;

final class EntityRefTest extends TestCase {

  public function testMapsKnownEntities(): void {
    $this->assertSame('civicrm_case', EntityRef::table('Case'));
    $this->assertSame('civicrm_activity', EntityRef::table('Activity'));
    $this->assertSame('civicrm_contact', EntityRef::table('Contact'));
    $this->assertSame('civicrm_relationship', EntityRef::table('Relationship'));
    $this->assertSame('civicrm_group', EntityRef::table('Group'));
    $this->assertSame('civicrm_group_contact', EntityRef::table('GroupContact'));
    $this->assertSame('civicrm_note', EntityRef::table('Note'));
    $this->assertSame('civicrm_participant', EntityRef::table('Participant'));
    $this->assertSame('civicrm_event', EntityRef::table('Event'));
  }

  public function testPassThroughCivicrmNames(): void {
    $this->assertSame('civicrm_contact', EntityRef::table('civicrm_contact'));
    $this->assertSame('civicrm_case', EntityRef::table('civicrm_case'));
  }

  public function testUnknownReturnsNull(): void {
    $this->assertNull(EntityRef::table('FooBar'));
  }

}
