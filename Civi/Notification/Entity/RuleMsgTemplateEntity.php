<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type ruleMsgTemplateEntityT array{
 *   id: int,
 *   msg_template_id: int,
 *   languages: list<string>,
 * }
 *
 * @phpstan-extends AbstractEntity<ruleMsgTemplateEntityT>
 */
class RuleMsgTemplateEntity extends AbstractEntity {

  /**
   * @return int
   */
  public function getMsgTemplateId(): int {
    return $this->entityValues['msg_template_id'];
  }

  /**
   * @return list<string>
   */
  public function getLanguages(): array {
    return $this->entityValues['languages'];
  }

}
