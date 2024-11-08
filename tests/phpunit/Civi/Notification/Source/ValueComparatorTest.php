<?php

use PHPUnit\Framework\TestCase;
use Civi\Notification\Source\ValueComparator;

/**
 * @covers \Civi\Notification\Source\ValueComparator
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

    $result = $this->valueComparator->compareValues($value1, $value2, $operator);
    $this->assertTrue($result);
  }

  public function testCompareUnequalValues(): void {
    $value1 = 10;
    $value2 = '10';
    $operator = '=';

    $result = $this->valueComparator->compareValues($value1, $value2, $operator);
    $this->assertFalse($result);
  }

  public function testCompareInOperatorWithArray(): void {
    $value1 = [1, 2, 3];
    $value2 = 2;
    $operator = 'IN';

    $result = $this->valueComparator->compareValues($value1, $value2, $operator);
    $this->assertTrue($result);
  }

  public function testCompareInOperatorWithNonArray(): void {
    $value1 = 10;
    $value2 = 10;
    $operator = 'IN';

    $result = $this->valueComparator->compareValues($value1, $value2, $operator);
    $this->assertFalse($result);
  }

  public function testInvalidOperatorThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);

    $value1 = 10;
    $value2 = 20;
    $operator = 'INVALID_OPERATOR';

    $this->valueComparator->compareValues($value1, $value2, $operator);
  }
}
