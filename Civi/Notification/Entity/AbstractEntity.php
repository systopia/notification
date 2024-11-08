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
   * @var T
   */
  protected array $entityValues;

  /**
   * @param T $entityValues
   */
  public function __construct(array $entityValues) {
    $this->entityValues = $entityValues;
  }

  /**
   * @return int
   */
  public function getId(): int {
    /** @phpstan-ignore-next-line  */
    return $this->entityValues['id'];
  }

}
