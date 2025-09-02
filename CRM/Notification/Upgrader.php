<?php
declare(strict_types = 1);

use CRM_Notification_ExtensionUtil as E;

class CRM_Notification_Upgrader extends CRM_Extension_Upgrader_Base {

  public function install() {
    $this->ensureQueue();
    return TRUE;
  }

  public function uninstall() {
    return TRUE;
  }

  public function upgrade_0001() {
    $this->ctx->log->info('notification: ensure queue notification.jobs');
    $this->ensureQueue();
    return TRUE;
  }

  private function ensureQueue(): void {
    $dao = new \CRM_Queue_DAO_Queue();
    $dao->name = 'notification.jobs';
    if ($dao->find(TRUE)) {
      if (empty($dao->type)) {
        $dao->type = 'Sql';
        if (empty($dao->batch_limit)) {
          $dao->batch_limit = 1;
        }
        if (empty($dao->lease_time)) {
          $dao->lease_time = 3600;
        }
        if (empty($dao->retry_limit)) {
          $dao->retry_limit = 0;
        }
        if (empty($dao->status)) {
          $dao->status = 'active';
        }
        if ($dao->is_template === NULL) {
          $dao->is_template = 0;
        }
        $dao->save();
      }
      return;
    }
    $new = new \CRM_Queue_DAO_Queue();
    $new->name = 'notification.jobs';
    $new->type = 'Sql';
    $new->batch_limit = 1;
    $new->lease_time  = 3600;
    $new->retry_limit = 0;
    $new->status      = 'active';
    $new->is_template = 0;
    $new->save();
  }

}
