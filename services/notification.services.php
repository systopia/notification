<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

// Snapshot store
$container->setDefinition(
  'notification.snapshot_store',
  (new Definition(\Civi\Notification\Snapshot\SnapshotStore::class))
);

// EventFactory
$container->setDefinition(
  'notification.event_factory',
  (new Definition(\Civi\Notification\Event\EventFactory::class))
    ->setArguments([new Reference('notification.snapshot_store')])
);
$container->setAlias(\Civi\Notification\Event\EventFactoryInterface::class, 'notification.event_factory');

$container->setDefinition(
  'notification.queue',
  (new Definition(\Civi\Notification\Queue\CiviQueue::class))
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
  (new Definition(\Civi\Notification\Handler\RuleHandler::class))
// ->setArguments([...])
);

$container->setDefinition(
  'notification.rule_set_handler',
  (new Definition(\Civi\Notification\Handler\RuleSetHandler::class))
    ->setArguments([new Reference('notification.rule_handler')])
);

$container->setDefinition(
  'notification.entity_manager',
  (new Definition(\Civi\Notification\EntityService\RuleSetManager::class))
)->setPublic(true);

$container->setDefinition(
  'notification.event_subscriber',
  (new Definition(\Civi\Notification\EventSubscriber\NotificationSubscriber::class))
// ->setArguments([...])
);

$container->findDefinition('dispatcher')
  ->addMethodCall('addSubscriber', [new Reference('notification.event_subscriber')]);

$container->setDefinition(
  'notification.command.process_queue',
  (new Definition(\Civi\Notification\Command\ProcessQueueCommand::class))
    ->setArguments([new Reference('notification.queue')])
    ->setPublic(true)
);
