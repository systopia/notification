<?php

declare(strict_types = 1);

namespace Civi\Notification\Data;

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
  private ?array $contactData;

  private string $email;

  /**
   * @phpstan-param contactT $contactData
   */
  public function __construct(string $email, ?array $contactData = NULL) {
    $this->email = $email;
    $this->contactData = $contactData;
  }

  /**
   * @phpstan-return contactT|NULL
   */
  public function getContactData(): ?array {
    return $this->contactData;
  }

  public function getContactId(): ?int {
    return $this->contactData['id'];
  }

  public function getEmail(): string {
    return $this->email;
  }

  public function getName(): ?string {
    return $this->contactData['display_name'] ?? NULL;
  }

  public function getPreferredLanguage(): ?string {
    return $this->contactData['preferred_language'] ?? NULL;
  }

}
