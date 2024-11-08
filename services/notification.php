<?php

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

use Civi\Notification\EventSubscriber\NotificationSubscriber;

$container->autowire(NotificationSubscriber::class)
  ->addTag('kernel.event_subscriber');
