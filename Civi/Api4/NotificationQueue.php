<?php
declare(strict_types = 1);

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * Notification Queue
 *
 * Process notification queue items
 *
 * @searchable none
 */
class NotificationQueue extends AbstractEntity {

  public static function getInfo() {
    $info = parent::getInfo();
    $info['title'] = ts('Notification Queue');
    $info['description'] = ts('Process notification queue items');
    $info['primary_key'] = [];
    return $info;
  }

  public static function getFields($checkPermissions = TRUE) {
    $getter = function($action) {
      return [];
    };

    return (new BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, $getter))
      ->setCheckPermissions($checkPermissions);
  }
}
