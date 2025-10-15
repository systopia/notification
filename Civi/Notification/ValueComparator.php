<?php
declare(strict_types = 1);

namespace Civi\Notification;

if (!class_exists(__NAMESPACE__ . '\ValueComparator', FALSE)) {
  class_alias(\Civi\Notification\Util\ValueComparator::class, __NAMESPACE__ . '\ValueComparator');
}
