<?php

namespace Symfony\Component\EventDispatcher {
  if (!interface_exists(EventSubscriberInterface::class)) {
    interface EventSubscriberInterface {
      /** @return array<mixed> */
      public static function getSubscribedEvents();
    }
  }
}

namespace Symfony\Component\Console\Command {
  if (!class_exists(Command::class)) {
    abstract class Command {
      public const SUCCESS = 0;
      public const FAILURE = 1;
      public const INVALID = 2;

      public function __construct(?string $name = null) {}
    }
  }
}

namespace Symfony\Component\Console\Input {
  if (!interface_exists(InputInterface::class)) {
    interface InputInterface {}
  }
}

namespace Symfony\Component\Console\Output {
  if (!interface_exists(OutputInterface::class)) {
    interface OutputInterface {}
  }
}
