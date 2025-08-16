<?php
declare(strict_types = 1);

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 * @var \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */

// Snapshot store
$container->setDefinition(
  'notification.snapshot_store',
  new Definition(\Civi\Notification\Snapshot\SnapshotStore::class)
);

$eventFactory = new Definition(\Civi\Notification\Event\EventFactory::class);
$eventFactory->setArguments([new Reference('notification.snapshot_store')]);
$container->setDefinition('notification.event_factory', $eventFactory);
$container->setAlias(\Civi\Notification\Event\EventFactoryInterface::class, 'notification.event_factory');

$container->setDefinition(
  'notification.queue',
  new Definition(\Civi\Notification\Queue\CiviQueue::class)
);

$enqueuer = new Definition(\Civi\Notification\Queue\Enqueuer::class);
$enqueuer->setArguments([new Reference('notification.queue')]);
$container->setDefinition('notification.enqueuer', $enqueuer);


$hookHandler = new Definition(\Civi\Notification\Hook\HookHandler::class);
$hookHandler->setArguments([
  new Reference('notification.snapshot_store'),
  new Reference('notification.event_factory'),
  new Reference('notification.enqueuer'),
]);
$hookHandler->setPublic(true);
$container->setDefinition('notification.hook_handler', $hookHandler);


$container->setDefinition(
  'notification.rule_handler',
  new Definition(\Civi\Notification\Handler\RuleHandler::class)
);

$ruleSetHandler = new Definition(\Civi\Notification\Handler\RuleSetHandler::class);
$ruleSetHandler->setArguments([new Reference('notification.rule_handler')]);
$container->setDefinition('notification.rule_set_handler', $ruleSetHandler);

$entityManager = new Definition(\Civi\Notification\EntityService\RuleSetManager::class);
$entityManager->setPublic(true);
$container->setDefinition('notification.entity_manager', $entityManager);
