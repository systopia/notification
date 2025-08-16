<?php declare(strict_types=1);

namespace {
  if (!class_exists('CRM_Extension_Upgrader_Base')) {
    abstract class CRM_Extension_Upgrader_Base {}
  }
}

namespace Civi\Api4\Generic {
  abstract class DAOEntity {}
}

namespace Civi\Api4\Generic\Traits {
  trait EntityBridge {}
}

namespace Civi\Test {
  interface HeadlessInterface {}
  interface TransactionalInterface {}
}
