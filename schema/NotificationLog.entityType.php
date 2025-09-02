<?php
use CRM_Notification_ExtensionUtil as E;

return [
  'name' => 'NotificationLog',
  'table' => 'civicrm_notification_log',
  'class' => 'CRM_Notification_DAO_NotificationLog',
  'getInfo' => fn() => [
    'title' => E::ts('Notification Log'),
    'title_plural' => E::ts('Notification Logs'),
    'description' => E::ts('Operational log for notifications'),
    'log' => FALSE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'created_at' => [
      'title' => E::ts('Created At'),
      'sql_type' => 'datetime',
      'input_type' => 'Text',
      'required' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'level' => [
      'title' => E::ts('Level'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Text',
      'required' => TRUE,
    ],
    'area' => [
      'title' => E::ts('Area'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
    ],
    'message' => [
      'title' => E::ts('Message'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
    ],
    'context_json' => [
      'title' => E::ts('Context JSON'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'required' => FALSE,
    ],
  ],
  'getIndices' => fn() => [
    'idx_area_time' => [
      'fields' => ['area' => TRUE, 'created_at' => TRUE],
    ],
  ],
  'getPaths' => fn() => [],
];
