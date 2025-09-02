<?php
declare(strict_types = 1);

namespace Civi\Api4\Action\NotificationQueue;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

final class Run extends AbstractAction {

  protected ?int $limit = NULL;
  protected ?string $queueName = NULL;

  protected ?int $runAsUserId = NULL;

  protected ?int $runAsContactId = NULL;

  public function setLimit(?int $limit): self {
    $this->limit = $limit;
    return $this;
  }

  public function setQueueName(?string $queueName): self {
    $this->queueName = $queueName;
    return $this;
  }

  public function setRunAsUserId(?int $uid): self {
    $this->runAsUserId = $uid;
    return $this;
  }

  public function setRunAsContactId(?int $cid): self {
    $this->runAsContactId = $cid;
    return $this;
  }

  public function _run(Result $result): void {
    $limit     = $this->limit ?? 0;
    $queueName = $this->queueName ?: 'notification.jobs';

    $restore = $this->impersonateIfRequested($this->runAsUserId, $this->runAsContactId);

    try {
      $svc = new \CRM_Queue_Service();
      $q   = $svc->load(['type' => 'Sql', 'name' => $queueName, 'reset' => FALSE]);

      $runner = new \CRM_Queue_Runner([
        'queue'     => $q,
        'errorMode' => \CRM_Queue_Runner::ERROR_ABORT,
      ]);

      $ran = $limit > 0 ? $runner->runAll($limit) : $runner->runAll();
      $result[] = $ran;
    } finally {
      $restore && $restore();
    }
  }

  private function impersonateIfRequested(?int $uid, ?int $cid): ?\Closure {
    $restore = function () {};
    $cms = \CRM_Core_Config::singleton()->userFramework;
    $loader = [$this, 'noop'];

    if (method_exists(\CRM_Utils_System::class, 'loadUser')) {
      $loader = [\CRM_Utils_System::class, 'loadUser'];
    }

    if (!$uid && $cid) {
      try {
        $row = \Civi\Api4\UFMatch::get(FALSE)
          ->addSelect('uf_id')
          ->addWhere('contact_id', '=', $cid)
          ->setLimit(1)
          ->execute()
          ->single();
        if ($row && !empty($row['uf_id'])) {
          $uid = (int) $row['uf_id'];
        }
      }
      catch (\Throwable $e) {

      }
    }

    if ($uid) {

      $prevContact = \CRM_Core_Session::singleton()->getLoggedInContactID();
      call_user_func($loader, $uid);
      return function () use ($prevContact, $loader) {
        if ($prevContact) {

          try {
            $row = \Civi\Api4\UFMatch::get(FALSE)
              ->addSelect('uf_id')
              ->addWhere('contact_id', '=', $prevContact)
              ->setLimit(1)
              ->execute()
              ->single();
            if ($row && !empty($row['uf_id'])) {
              call_user_func($loader, (int) $row['uf_id']);
              return;
            }
          }
          catch (\Throwable $e) {
          }
        }

      };
    }

    return NULL;
  }

  private function noop($uid): void {}

}
