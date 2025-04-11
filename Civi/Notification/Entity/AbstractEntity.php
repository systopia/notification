<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @template T of array<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractEntity {

  /**
   * @phpstan-var T
   */
  protected array $entityValues;

  /**
   * @phpstan-param T $entityValues
   */
  public function __construct(array $entityValues) {
    $this->entityValues = $entityValues;
  }

  public function getId(): int {
    // @phpstan-ignore return.type
    return $this->entityValues['id'];
  }

}
