<?php

namespace Symfony\Component\EventDispatcher {
  if (!interface_exists(EventSubscriberInterface::class)) {
    interface EventSubscriberInterface {
      /**
       * @return array<mixed>
       */
      public static function getSubscribedEvents();
    }
  }
}

namespace Symfony\Component\Console\Command {
  if (!class_exists(Command::class)) {
    abstract class Command {
      public const SUCCESS = 0;
      public const FAILURE = 1;

      public function __construct(?string $name = null) {}

      public function setName(string $name): static { return $this; }
      public function setDescription(string $description): static { return $this; }

      public function run(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
      ): int {
        return self::SUCCESS;
      }
    }
  }
}

namespace Symfony\Component\Console\Input {
  if (!interface_exists(InputInterface::class)) {
    interface InputInterface {}
  }

  if (!class_exists(ArrayInput::class)) {
    class ArrayInput implements InputInterface {
      /** @param array<string,mixed> $parameters */
      public function __construct(array $parameters = []) {}
    }
  }
}
namespace Symfony\Component\Console\Formatter {
  if (!interface_exists(OutputFormatterInterface::class)) {
    interface OutputFormatterInterface {
      public function setDecorated(bool $decorated): void;
      public function isDecorated(): bool;
      public function format(string $message): string;
    }
  }
}

namespace Symfony\Component\Console\Output {
  use Symfony\Component\Console\Formatter\OutputFormatterInterface;
  if (!interface_exists(OutputInterface::class)) {
    interface OutputInterface {
      /** @param string|string[] $messages */
      public function write($messages, bool $newline = false, int $options = 0): void;
      /** @param string|string[] $messages */
      public function writeln($messages, int $options = 0): void;
    }
  }

  if (!class_exists(BufferedOutput::class)) {
    class BufferedOutput implements OutputInterface {
      public function __construct(int $verbosity = 0, bool $decorated = false, ?OutputFormatterInterface $formatter = null) {}
      /** @return string */
      public function fetch() { return ''; }
      public function write($messages, bool $newline = false, int $options = 0): void {}
      public function writeln($messages, int $options = 0): void {}
    }
  }
}

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

namespace Symfony\Component\Console\Attribute {
  if (!class_exists(AsCommand::class)) {
    #[\Attribute(\Attribute::TARGET_CLASS)]
    final class AsCommand {
      public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public array $aliases = []
      ) {}
    }
  }
}

