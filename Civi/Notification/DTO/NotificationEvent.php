<?php
declare(strict_types = 1);

namespace Civi\Notification\DTO;

class NotificationEvent {

  public function __construct(
    public string $entity,
    public string $op,
    public int|string|null $id,
    /**
     * @var array<string, mixed> */ public array $before,
    /**
     * @var array<string, mixed> */ public array $after,
    public ChangeSet $changes,
    /**
     * @var array<string, mixed> */ public array $context = []
  ) {}

}
