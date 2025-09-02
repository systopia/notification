<?php
use CRM_Notification_ExtensionUtil as E;

return [
  'notification_queue_job_enabled' => [
    'name' => 'notification_queue_job_enabled',
    'group' => 'notification',
    'type' => 'Boolean',
    'default' => TRUE,
    'title' => E::ts('Notifications enabled'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('Enable/disable the notification processor.'),
  ],
  'notification_processing_mode' => [
    'name' => 'notification_processing_mode',
    'group' => 'notification',
    'type' => 'String',
    'default' => 'queue',
    'title' => E::ts('Processing mode'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('Choose between "queue" and "event".'),
  ],
];
