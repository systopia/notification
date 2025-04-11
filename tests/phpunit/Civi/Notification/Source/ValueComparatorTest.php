<?php
declare(strict_types = 1);

namespace Civi\Notification\Source;

use Civi\Notification\ValueComparator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Notification\ValueComparator
 */
class ValueComparatorTest extends TestCase {

  private ValueComparator $valueComparator;

  protected function setUp(): void {
    parent::setUp();

    $this->valueComparator = new ValueComparator();
  }

  public function testCompareEqualValues(): void {
    $value1 = 10;
    $value2 = 10;
    $operator = '=';

    $result = $this->valueComparator->compareValues($value2, $operator, $value1);
    $this->assertTrue($result);
  }

  public function testCompareUnequalValues(): void {
    $value1 = 10;
    $value2 = '10';
    $operator = '=';

    $result = $this->valueComparator->compareValues($value2, $operator, $value1);
    $this->assertFalse($result);
  }

  public function testCompareInOperatorWithArray(): void {
    $value1 = [1, 2, 3];
    $value2 = 2;
    $operator = 'IN';

    $result = $this->valueComparator->compareValues($value2, $operator, $value1);
    $this->assertTrue($result);
  }

  public function testCompareInOperatorWithNonArray(): void {
    $value1 = 10;
    $value2 = 10;
    $operator = 'IN';

    $result = $this->valueComparator->compareValues($value2, $operator, $value1);
    $this->assertFalse($result);
  }

  public function testInvalidOperatorThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);

    $value1 = 10;
    $value2 = 20;
    $operator = 'INVALID_OPERATOR';

    $this->valueComparator->compareValues($value2, $operator, $value1);
  }

}
