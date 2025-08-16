<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'notification.civix.php';
// phpcs:enable

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function notification_civicrm_config(\CRM_Core_Config $config): void {
  _notification_civix_civicrm_config($config);
}

function notification_civicrm_container(\Symfony\Component\DependencyInjection\ContainerBuilder $container): void {
  $globResource = new GlobResource(__DIR__ . '/services', '/*.php', FALSE);
  // Container will be rebuilt if a *.php file is added to services
  $container->addResource($globResource);
  foreach ($globResource->getIterator() as $path => $info) {
    // Container will be rebuilt if file changes
    $container->addResource(new FileResource($path));
    require $path;
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function notification_civicrm_install(): void {
  _notification_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function notification_civicrm_enable(): void {
  _notification_civix_civicrm_enable();
}

function notification_civicrm_pre(string $op, string $objectName, int|string|null $id, array &$params): void {
  $handler = \Civi::container()->get('notification.hook_handler');
  $handler->onPre((string) $op, (string) $objectName, $id, $params);
}

function notification_civicrm_post(string $op, string $objectName, int|string|null $objectId, mixed &$objectRef): void {
  $handler = \Civi::container()->get('notification.hook_handler');
  $handler->onPost((string) $op, (string) $objectName, $objectId, $objectRef);
}

function notification_civicrm_postCommit(string $op,
  string $objectName,
  int|string|null $objectId,
  mixed &$objectRef): void {
  $handler = \Civi::container()->get('notification.hook_handler');
  $handler->onPostCommit((string) $op, (string) $objectName, $objectId, $objectRef);
}
