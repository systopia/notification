<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

// Snapshot store
$container->setDefinition(
  'notification.snapshot_store',
  new Definition(\Civi\Notification\Snapshot\SnapshotStore::class)
);

// EventFactory (+ alias to interface)
$container->setDefinition(
  'notification.event_factory',
  (new Definition(\Civi\Notification\Event\EventFactory::class))
    ->setArguments([new Reference('notification.snapshot_store')])
);
$container->setAlias(\Civi\Notification\Event\EventFactoryInterface::class, 'notification.event_factory');

// Base queue (CiviQueue) — solo enqueue
$container->setDefinition(
  'notification.queue',
  new Definition(\Civi\Notification\Queue\CiviQueue::class)
);

$container->setDefinition(
  'notification.queue.draining',
  new Definition(\Civi\Notification\Support\InMemoryQueue::class)
);

$container->setAlias(
  \Civi\Notification\Queue\DrainingQueueInterface::class,
  'notification.queue.draining'
);

// Enqueuer
$container->setDefinition(
  'notification.enqueuer',
  (new Definition(\Civi\Notification\Queue\Enqueuer::class))
    ->setArguments([new Reference('notification.queue')])
);

// Hook handler
$container->setDefinition(
  'notification.hook_handler',
  (new Definition(\Civi\Notification\Hook\HookHandler::class))
    ->setArguments([
      new Reference('notification.snapshot_store'),
      new Reference('notification.event_factory'),
      new Reference('notification.enqueuer'),
    ])
    ->setPublic(true)
);

$container->setDefinition(
  'notification.rule_handler',
  new Definition(\Civi\Notification\Handler\RuleHandler::class)
);
$container->setDefinition(
  'notification.rule_set_handler',
  (new Definition(\Civi\Notification\Handler\RuleSetHandler::class))
    ->setArguments([new Reference('notification.rule_handler')])
);

$container->setDefinition(
  'notification.runner',
  (new Definition(\Civi\Notification\Runner\QueueRunner::class))
    ->setArguments([
      new Reference('notification.rule_set_handler'),
      new Reference('psr_log.logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    ])
);

// Event subscriber
$container->setDefinition(
  'notification.event_subscriber',
  (new Definition(\Civi\Notification\EventSubscriber\NotificationSubscriber::class))
    ->addTag('kernel.event_subscriber')
);

$container->setDefinition(
  'notification.command.process_queue',
  (new Definition(\Civi\Notification\Command\ProcessQueueCommand::class))
    ->setAutowired(true)
    ->addTag('console.command', ['command' => 'notification:process-queue'])
    ->setPublic(true)
);
