<?php
use CRM_Notification_ExtensionUtil as E;
return [
  'name' => 'NotificationContactSelection',
  'table' => 'civicrm_notification_contact_selection',
  'class' => 'CRM_Notification_DAO_NotificationContactSelection',
  'getInfo' => fn() => [
    'title' => E::ts('Notification Contact Selection'),
    'title_plural' => E::ts('Notification Contact Selections'),
    'description' => E::ts('Specifies conditions contacts must match to be notified.'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique NotificationContactSelection ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'rule_id' => [
      'title' => E::ts('Rule ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to NotificationRule'),
      'entity_reference' => [
        'entity' => 'NotificationRule',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_ids' => [
      'title' => E::ts('Contacts'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
      'required' => TRUE,
      'input_attrs' => [
        'multiple' => '1',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_contact',
        'key_column' => 'id',
        'label_column' => 'display_name',
        'condition' => 'is_deleted = 0',
        'prefetch' => 'disabled',
      ],
    ],
    'group_ids' => [
      'title' => E::ts('Groups'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
      'required' => TRUE,
      'input_attrs' => [
        'multiple' => '1',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_group',
        'key_column' => 'id',
        'name_column' => 'name',
        'label_column' => 'title',
        'prefetch' => 'disabled',
      ],
    ],
    'contact_type_ids' => [
      'title' => E::ts('Contact Types'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
      'required' => TRUE,
      'input_attrs' => [
        'multiple' => '1',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_contact_type',
        'key_column' => 'id',
        'name_column' => 'name',
        'label_column' => 'label',
        'prefetch' => 'disabled',
      ],
    ],
    'custom' => [
      'title' => E::ts('Custom'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
      'required' => FALSE,
      'description' => E::ts('Conditions specific to the monitored entity.'),
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];
