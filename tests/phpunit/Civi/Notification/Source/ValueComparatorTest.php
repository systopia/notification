<?php
declare(strict_types = 1);

namespace Civi\Notification\Source;

use Civi\Notification\ValueComparator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ValueComparatorTest extends TestCase {

  /**
   * @covers \Civi\Notification\ValueComparator::compare
   */
  public function testCompareEqualValues(): void {
    $cmp = new ValueComparator();
    $this->assertTrue($cmp->compare('==', 'foo', 'foo'));
    $this->assertTrue($cmp->compare('=', 'foo', 'foo'));
  }

  /**
   * @covers \Civi\Notification\ValueComparator::compare
   */
  public function testCompareUnequalValues(): void {
    $cmp = new ValueComparator();
    $this->assertFalse($cmp->compare('==', 'foo', 'bar'));
    $this->assertTrue($cmp->compare('!=', 'foo', 'bar'));
  }

  /**
   * @covers \Civi\Notification\ValueComparator::compare
   */
  public function testCompareInOperatorWithArray(): void {
    $cmp = new ValueComparator();
    $this->assertTrue($cmp->compare('in', 2, [1, 2, 3]));
    $this->assertFalse($cmp->compare('in', 4, [1, 2, 3]));
  }

  /**
   * @covers \Civi\Notification\ValueComparator::compare
   */
  public function testCompareInOperatorWithNonArray(): void {
    $cmp = new ValueComparator();

    $this->expectException(\TypeError::class);
    $cmp->compare('in', 1, 123);
  }

  /**
   * @covers \Civi\Notification\ValueComparator::compare
   */
  public function testInvalidOperatorThrowsException(): void {
    $cmp = new ValueComparator();

    $this->expectException(InvalidArgumentException::class);
    $cmp->compare('^', 'a', 'b');
  }

}
