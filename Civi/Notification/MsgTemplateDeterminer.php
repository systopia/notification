<?php
declare(strict_types = 1);

namespace Civi\Notification;

use Civi\Notification\Data\NotificationRecipient;

class MsgTemplateDeterminer implements MsgTemplateDeterminerInterface {

  /**
   * @param \Civi\Notification\Data\NotificationRecipient $recipient
   * @param array<int,object> $messageTemplates  Objects con:
   *   - getLanguages(): string[]
   *   - getMsgTemplateId(): int
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function determineMessageTemplateId(NotificationRecipient $recipient, array $messageTemplates): ?int {
    // phpcs:enable
    if ($messageTemplates === []) {
      return NULL;
    }

    $preferred = $this->normalizeLang($recipient->getPreferredLanguage());
    $preferredBase = $this->baseLang($preferred);

    $firstTemplateId = NULL;
    // languages === []
    $wildcardTemplate  = NULL;
    $baseMatchTemplate = NULL;

    foreach ($messageTemplates as $tpl) {
      if (!\is_object($tpl) || !\method_exists($tpl, 'getMsgTemplateId') || !\method_exists($tpl, 'getLanguages')) {
        continue;
      }

      $id = (int) $tpl->getMsgTemplateId();
      /** @var array<int,string> $langs */
      $langs = array_map([$this, 'normalizeLang'], (array) $tpl->getLanguages());

      if ($firstTemplateId === NULL) {
        $firstTemplateId = $id;
      }

      // 1) Exact match (es_es)
      if ($preferred !== NULL && \in_array($preferred, $langs, TRUE)) {
        return $id;
      }

      if ($langs === [] && $wildcardTemplate === NULL) {
        $wildcardTemplate = $id;
      }

      if ($preferredBase !== NULL && $baseMatchTemplate === NULL) {
        foreach ($langs as $l) {
          if ($this->baseLang($l) === $preferredBase) {
            $baseMatchTemplate = $id;
            break;
          }
        }
      }
    }

    if ($baseMatchTemplate !== NULL) {
      return $baseMatchTemplate;
    }
    if ($wildcardTemplate !== NULL) {
      return $wildcardTemplate;
    }
    return $firstTemplateId;
  }

  private function normalizeLang(?string $lang): ?string {
    if ($lang === NULL || $lang === '') {
      return NULL;
    }

    $lang = \str_replace('-', '_', \trim($lang));
    return \strtolower($lang);
  }

  private function baseLang(?string $lang): ?string {
    if ($lang === NULL) {
      return NULL;
    }
    $parts = \explode('_', $lang, 2);
    return $parts[0] !== '' ? $parts[0] : NULL;
  }

}
