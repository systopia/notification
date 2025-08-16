<?php
declare(strict_types = 1);

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @var \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */

$container->setDefinition(
  'notification.rule_handler',
  new Definition(\Civi\Notification\Handler\RuleHandler::class)
);

$ruleSetDef = new Definition(\Civi\Notification\Handler\RuleSetHandler::class);
$ruleSetDef->setArguments([new Reference('notification.rule_handler')]);
$container->setDefinition('notification.rule_set_handler', $ruleSetDef);

