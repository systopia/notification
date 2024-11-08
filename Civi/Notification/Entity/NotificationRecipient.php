<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type contactT array{
 *   id: int,
 *   display_name: string,
 *   email: string,
 *   preferred_language: string|null
 * }
 */
class NotificationRecipient {

  /**
   * @phpstan-var contactT
   */
  protected array $contact;

  /**
   * @phpstan-param contactT $contact
   */
  public function __construct(array $contact) {
    $this->contact = $contact;
  }

  public function getId(): int {
    return $this->contact['id'];
  }

  public function getDisplayName(): string {
    return $this->contact['display_name'];
  }

  public function getEmail(): string {
    return $this->contact['email'];
  }

  public function getPreferredLanguage(): ?string {
    return $this->contact['preferred_language'];
  }

}
