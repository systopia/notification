<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

// -----------------------------------------------------------------------------
// Snapshot store
// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.snapshot_store',
  new Definition(\Civi\Notification\Snapshot\SnapshotStore::class)
);

// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.event_factory',
  (new Definition(\Civi\Notification\Event\EventFactory::class))
    ->setArguments([new Reference('notification.snapshot_store')])
);
$container->setAlias(\Civi\Notification\Event\EventFactoryInterface::class, 'notification.event_factory');

// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.queue',
  new Definition(\Civi\Notification\Queue\CrmTaskQueue::class)
);


$container->setDefinition(
  'notification.queue.draining',
  new Definition(\Civi\Notification\Support\InMemoryQueue::class)
);
$container->setAlias(
  \Civi\Notification\Queue\DrainingQueueInterface::class,
  'notification.queue.draining'
);

// -----------------------------------------------------------------------------
// Enqueuer
// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.enqueuer',
  (new Definition(\Civi\Notification\Queue\Enqueuer::class))
    ->setArguments([new Reference('notification.queue')])
);

// -----------------------------------------------------------------------------
// Hook handler (usa snapshots + factory + enqueuer)
// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.hook_handler',
  (new Definition(\Civi\Notification\Hook\HookHandler::class))
    ->setArguments([
      new Reference('notification.snapshot_store'),
      new Reference('notification.event_factory'),
      new Reference('notification.enqueuer'),
      new Reference('notification.pre_enqueue_matcher'),
    ])
    ->setPublic(true)
);
// ============================================================================

// ============================================================================

// ConditionHandler
$container->setDefinition(
  'notification.condition_handler',
  (new Definition(\Civi\Notification\Handler\ConditionHandler::class))
    ->setAutowired(true)
    ->setPublic(true)   // <— importante
);

// FieldMonitoringHandler
$container->setDefinition(
  'notification.field_monitoring_handler',
  (new Definition(\Civi\Notification\Handler\FieldMonitoringHandler::class))
    ->setAutowired(true)
    ->setPublic(true)
);

// RuleMatchChecker
$container->setDefinition(
  'notification.rule_match_checker',
  (new Definition(\Civi\Notification\Handler\RuleMatchChecker::class))
    ->setAutowired(true)
);

// ContactLoader
$container->setDefinition(
  'notification.contact_loader',
  (new Definition(\Civi\Notification\EntityService\ContactLoader::class))
    ->setAutowired(true)
);


$container->setDefinition(
  'notification.entity_manager',
  (new Definition(\Civi\Notification\EntityService\RuleSetManager::class))
    ->setAutowired(true)
    ->setPublic(true)
);

$container->setAlias(
  \Civi\Notification\EntityService\RuleSetManager::class,
  'notification.entity_manager'
);

// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------

$fqcnValueComparator = class_exists(\Civi\Notification\Util\ValueComparator::class)
  ? \Civi\Notification\Util\ValueComparator::class
  : (\Civi\Notification\ValueComparator::class); 

// ValueComparator
$container->setDefinition(
  'notification.value_comparator',
  (new Definition(\Civi\Notification\Util\ValueComparator::class))
    ->setAutowired(true)
    ->setPublic(true)
);

$container->setAlias(\Civi\Notification\Util\ValueComparator::class, 'notification.value_comparator');
$container->setAlias(\Civi\Notification\ValueComparator::class,       'notification.value_comparator');


// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.rule_handler',
  (new Definition(\Civi\Notification\Handler\RuleHandler::class))
    ->setAutowired(true)
    ->setPublic(true)
);
$container->setAlias(
  \Civi\Notification\Handler\RuleHandlerInterface::class,
  'notification.rule_handler'
);

$container->setDefinition(
  'notification.rule_set_handler',
  (new Definition(\Civi\Notification\Handler\RuleSetHandler::class))
    ->setAutowired(true)
    ->setPublic(true)
);

// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.runner',
  (new Definition(\Civi\Notification\Runner\QueueRunner::class))
    ->setArguments([
      new Reference('notification.rule_set_handler'),
      new Reference('psr_log.logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    ])
    ->setPublic(true)
);

// -----------------------------------------------------------------------------
// Event subscriber (Symfony dispatcher)
// -----------------------------------------------------------------------------
//$container->setDefinition(
//  'notification.event_subscriber',
//  (new Definition(\Civi\Notification\EventSubscriber\NotificationSubscriber::class))
//    ->addTag('kernel.event_subscriber')
//);

// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.command.process_queue',
  (new Definition(\Civi\Notification\Command\ProcessQueueCommand::class))
    ->setAutowired(true)
    ->addTag('console.command', ['command' => 'notification:process-queue'])
    ->setPublic(true)
);

// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
$container->setDefinition(
  'notification.notification_sender',
  (new Definition(\Civi\Notification\Mailer\BasicNotificationSender::class))
    ->setAutowired(true)
    ->setPublic(true)
);
$container->setAlias(
  \Civi\Notification\NotificationSenderInterface::class,
  'notification.notification_sender'
);


$container->setDefinition(
  'notification.msg_template_determiner',
  (new Definition(\Civi\Notification\MsgTemplateDeterminer::class))
    ->setAutowired(true)
);
$container->setAlias(
  \Civi\Notification\MsgTemplateDeterminerInterface::class,
  'notification.msg_template_determiner'
);


$container->setDefinition(
  'notification.token_context_generator',
  (new Definition(\Civi\Notification\TokenContextGenerator::class))
    ->setAutowired(true)
);
$container->setAlias(
  \Civi\Notification\TokenContextGeneratorInterface::class,
  'notification.token_context_generator'
);

// Pre-enqueue matcher
$container->setDefinition(
  'notification.pre_enqueue_matcher',
  (new Definition(\Civi\Notification\Precheck\PreEnqueueMatcher::class))
    ->setAutowired(true)
    ->setPublic(true) 
);

$container->setAlias(
  \Civi\Notification\Precheck\PreEnqueueMatcher::class,
  'notification.pre_enqueue_matcher'
);


$container->setAlias(\Civi\Notification\Handler\ConditionHandlerInterface::class, 'notification.condition_handler');
$container->setAlias(\Civi\Notification\Handler\FieldMonitoringHandlerInterface::class, 'notification.field_monitoring_handler');
$container->setAlias(\Civi\Notification\Handler\RuleMatchCheckerInterface::class, 'notification.rule_match_checker');
$container->setAlias(\Civi\Notification\EntityService\ContactLoaderInterface::class, 'notification.contact_loader');
