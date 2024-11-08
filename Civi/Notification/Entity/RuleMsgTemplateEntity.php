<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type ruleMsgTemplateEntityT array{
 *   id: int,
 *   msg_template_id: int,
 *   languages: array<string, string>,
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
   * @return array<string, string>
   */
  public function getLanguages(): array {
    return $this->entityValues['languages'];
  }

}
