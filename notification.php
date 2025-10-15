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

  $tplDir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
  $smarty = CRM_Core_Smarty::singleton();

  $dirs = [];
  if (is_object($smarty) && method_exists($smarty, 'getTemplateDir')) {
    /** @var mixed $maybeDirs */
    $maybeDirs = $smarty->getTemplateDir();
    $dirs = (array) $maybeDirs;
  }
  if (!in_array($tplDir, $dirs, TRUE)) {
    if (is_object($smarty) && method_exists($smarty, 'addTemplateDir')) {
      $smarty->addTemplateDir($tplDir);
    }
  }
}

/**
 * Register PHP service definitions from services/*.php
 *
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function notification_civicrm_container($container): void {
  $glob = new GlobResource(__DIR__ . '/services', '/*.php', FALSE);
  $container->addResource($glob);
  foreach ($glob->getIterator() as $path => $info) {
    $container->addResource(new FileResource($path));
    require $path;
  }

  if (
    !$container->hasDefinition('notification.hook_handler')
    && class_exists(\Civi\Notification\Hook\HookHandler::class)
  ) {
    $container->register('notification.hook_handler', \Civi\Notification\Hook\HookHandler::class)
      ->setAutowired(TRUE);
  }
  if (
    !$container->hasDefinition('notification.event_subscriber')
    && class_exists(\Civi\Notification\EventSubscriber\NotificationSubscriber::class)
  ) {
    $container->register('notification.event_subscriber',
      \Civi\Notification\EventSubscriber\NotificationSubscriber::class)
      ->addTag('kernel.event_subscriber')
      ->setAutowired(TRUE);
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
  $c = \Civi::container();
  if (!$c->has('notification.hook_handler')) {
    return;
  }
  /** @var \Civi\Notification\Hook\HookHandler $handler */
  $handler = $c->get('notification.hook_handler');
  $handler->onPre((string) $op, (string) $objectName, $id, $params);
}

function notification_civicrm_post(string $op, string $objectName, int|string|null $objectId, mixed &$objectRef): void {
  $c = \Civi::container();
  if (!$c->has('notification.hook_handler')) {
    return;
  }
  /** @var \Civi\Notification\Hook\HookHandler $handler */
  $handler = $c->get('notification.hook_handler');
  $handler->onPost((string) $op, (string) $objectName, $objectId, $objectRef);
}

function notification_civicrm_postCommit(
  string $op,
  string $objectName,
  int|string|null $objectId,
  mixed &$objectRef
): void {
  $c = \Civi::container();
  if (!$c->has('notification.hook_handler')) {
    return;
  }
  /** @var \Civi\Notification\Hook\HookHandler $handler */
  $handler = $c->get('notification.hook_handler');
  $handler->onPostCommit((string) $op, (string) $objectName, $objectId, $objectRef);
}

/**
 * @param array<int,string> $files
 */
function notification_civicrm_xmlMenu(array &$files): void {
  if (function_exists('_notification_civix_civicrm_xmlMenu')) {
    _notification_civix_civicrm_xmlMenu($files);
  }
  $files[] = __DIR__ . '/xml/Menu/Notification.xml';
}

/**
 * @param array<string,mixed> $menu
 */
function notification_civicrm_navigationMenu(array &$menu): void {
  $adminId = (int) CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_navigation WHERE name = 'Administer'");
  if ($adminId === 0) {
    return;
  }
  $max = 1 + (int) CRM_Core_DAO::singleValueQuery(
      'SELECT MAX(weight) FROM civicrm_navigation WHERE parent_id = %1',
      [1 => [$adminId, 'Integer']]
    );

  $menu['notification_entities'] = [
    'attributes' => [
      'label' => ts('Notification Rules'),
      'name' => 'Notification Rules',
      'url' => 'civicrm/notification/entities?reset=1',
      'permission' => 'administer CiviCRM',
      'parentID' => $adminId,
      'navID' => 'notification_entities',
      'active' => 1,
      'weight' => $max,
    ],
    'child' => [
      'notification_entities_list' => [
        'attributes' => [
          'label' => ts('Rules by Entity'),
          'name' => 'notification_entities_list',
          'url' => 'civicrm/notification/entities?reset=1',
          'permission' => 'administer CiviCRM',
          'active' => 1,
          'weight' => 1,
        ],
      ],
      'notification_logs' => [
        'attributes' => [
          'label' => ts('Notification Logs'),
          'name' => 'notification_logs',
          'url' => 'civicrm/notification/logs?reset=1',
          'permission' => 'administer CiviCRM',
          'active' => 1,
          'weight' => 3,
        ],
      ],
      'notification_queue' => [
        'attributes' => [
          'label' => ts('Notification Queue'),
          'name' => 'notification_queue',
          'url' => 'civicrm/notification/queue?reset=1',
          'permission' => 'administer CiviCRM',
          'active' => 1,
          'weight' => 4,
        ],
      ],
      'notification_settings' => [
        'attributes' => [
          'label' => ts('Notification Settings'),
          'name' => 'notification_settings',
          'url' => 'civicrm/notification/settings?reset=1',
          'permission' => 'administer CiviCRM',
          'active' => 1,
          'weight' => 5,
        ],
      ],
    ],
  ];
}

/**
 * @param array<string,mixed> $items
 */
function notification_civicrm_alterMenu(array &$items): void {
  $ensure = function (array &$items, string $path, array $attrs): void {
    if (!isset($items[$path])) {
      $items[$path] = [
        'path' => $path,
        'title' => 'Notification',
        'access_arguments' => ['administer CiviCRM'],
        'is_public' => 0,
        'is_active' => 1,
        'is_exposed' => 1,
      ];
    }
    foreach ($attrs as $k => $v) {
      $items[$path][$k] = $v;
    }
  };

  $ensure($items, 'civicrm/notification/ping', [
    'page_callback' => 'CRM_Notification_Page_Ping',
  ]);
  $ensure($items, 'civicrm/notification/entities', [
    'page_callback' => 'CRM_Notification_Page_EntityConfig',
  ]);
  $ensure($items, 'civicrm/notification/logs', [
    'page_callback' => 'CRM_Notification_Page_Logs',
  ]);
  $ensure($items, 'civicrm/notification/queue', [
    'page_callback' => 'CRM_Notification_Page_Queue',
  ]);

  $ensure($items, 'civicrm/notification/entity-rule', [
    'page_callback' => 'CRM_Notification_Page_EntityRule',
  ]);

  $ensure($items, 'civicrm/notification/settings', [
    'page_callback'  => 'CRM_Notification_Page_Settings',
  ]);

  foreach ([
    'civicrm/notification/ping',
    'civicrm/notification/entities',
    'civicrm/notification/logs',
    'civicrm/notification/queue',
  ] as $path) {
    if (array_key_exists($path, $items)) {
      unset($items[$path]['page_arguments']);
    }
  }
}

/**
 */
function notification_civicrm_init(): void {
  foreach ([
    '/CRM/Notification/Page/Ping.php',
    '/CRM/Notification/Page/EntityConfig.php',
    '/CRM/Notification/Form/EntityRuleWizard.php',
    '/CRM/Notification/Form/EditRule.php',
    '/CRM/Notification/Form/Settings.php',
    '/CRM/Notification/Page/Logs.php',
    '/CRM/Notification/Page/Queue.php',
  ] as $rel) {
    $file = __DIR__ . $rel;
    if (file_exists($file)) {
      require_once $file;
    }
  }
}

/**
 * @param string $formName
 * @param \CRM_Core_Form $form
 */
function notification_civicrm_buildForm(string $formName, \CRM_Core_Form &$form): void {
  if ($formName !== 'CRM_Notification_Form_EntityRuleWizard') {
    return;
  }

  $entityType = 'Activity';
  if (isset($form->_defaults['entity_type']) && is_string($form->_defaults['entity_type'])) {
    $entityType = $form->_defaults['entity_type'];
  }

  Civi::resources()->addVars('notificationErw', [
    'formName'   => $form->getName(),
    'entityType' => $entityType,
  ]);

  Civi::resources()->addScriptFile(
    'notification',
    'js/entity-rule-wizard.js',
    100,
    'page-footer'
  );
}
