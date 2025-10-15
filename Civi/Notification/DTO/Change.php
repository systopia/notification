<?php
declare(strict_types = 1);

namespace Civi\Notification\DTO;

class Change {

  public function __construct(
    public string $field,
    public mixed $before,
    public mixed $after
  ) {}

}
