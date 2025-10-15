<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\FieldMonitoringEntity;
use Civi\Notification\Support\DbLogger;
use Civi\Notification\Util\ValueComparator;

final class FieldMonitoringHandler implements FieldMonitoringHandlerInterface {

  public function __construct(
    private ValueComparator $valueComparator
  ) {}

  public function evaluate(FieldMonitoringEntity $monitoring, NotificationContext $context): bool {
    $field = $monitoring->getFieldName();

    // sin cast redundante
    $beforeOp  = $monitoring->getOperatorBefore();
    $beforeExp = $monitoring->getValueBefore();

    // sin cast redundante
    $afterOp   = $monitoring->getOperatorAfter();
    $afterExp  = $monitoring->getValueAfter();

    $old = $context->getOldValues()[$field] ?? NULL;
    $new = $context->getNewValues()[$field] ?? NULL;

    DbLogger::log('debug', 'fieldmonitoring.compare', 'Comparing field monitoring', [
      'field'      => $field,
      'before_op'  => $beforeOp,
      'before_exp' => $beforeExp,
      'before_act' => $old,
      'after_op'   => $afterOp,
      'after_exp'  => $afterExp,
      'after_act'  => $new,
    ]);

    return $this->valueComparator->compareValues(
      $beforeOp,
      $beforeExp,
      $old,
      $afterOp,
      $afterExp,
      $new
    );
  }

}
