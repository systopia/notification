CREATE TABLE IF NOT EXISTS `civicrm_notification_rule_set` (
                                                             `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                             `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `monitored_entity_type` VARCHAR(255) NOT NULL,
  `source_entity_type` VARCHAR(255) NOT NULL,
  `source_entity_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_execute_only_first_rule` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `index_title` (`title`),
  KEY `index_is_active` (`is_active`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `civicrm_notification_rule` (
                                                         `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                         `rule_set_id` INT UNSIGNED NOT NULL,
                                                         `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `preferred_location_type_id` INT UNSIGNED DEFAULT NULL,
  `email_addresses` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_respect_communication_suspension` TINYINT(1) NOT NULL DEFAULT 0,
  `is_stop_after_this_rule` TINYINT(1) NOT NULL DEFAULT 0,
  `weight` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_rule_set_id` (`rule_set_id`),
  KEY `index_title` (`title`),
  KEY `index_is_active` (`is_active`),
  KEY `index_weight` (`weight`),
  CONSTRAINT `fk_notification_rule_ruleset`
  FOREIGN KEY (`rule_set_id`) REFERENCES `civicrm_notification_rule_set`(`id`)
  ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `civicrm_notification_condition` (
                                                              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                              `rule_id` INT UNSIGNED NOT NULL,
                                                              `field_name` VARCHAR(255) NOT NULL,
  `operator` VARCHAR(255) NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rule_id` (`rule_id`),
  CONSTRAINT `fk_notification_condition_rule`
  FOREIGN KEY (`rule_id`) REFERENCES `civicrm_notification_rule`(`id`)
  ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `civicrm_notification_contact_selection` (
                                                                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                                      `rule_id` INT UNSIGNED NOT NULL,
                                                                      `contact_ids` VARCHAR(255) NOT NULL,
  `group_ids` VARCHAR(255) NOT NULL,
  `contact_type_ids` VARCHAR(255) NOT NULL,
  `custom` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rule_id` (`rule_id`),
  CONSTRAINT `fk_notification_contact_selection_rule`
  FOREIGN KEY (`rule_id`) REFERENCES `civicrm_notification_rule`(`id`)
  ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `civicrm_notification_field_monitoring` (
                                                                     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                                     `rule_id` INT UNSIGNED NOT NULL,
                                                                     `field_name` VARCHAR(255) NOT NULL,
  `operator_before` VARCHAR(255) NOT NULL,
  `value_before` VARCHAR(255) NOT NULL,
  `operator_after` VARCHAR(255) NOT NULL,
  `value_after` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rule_id` (`rule_id`),
  CONSTRAINT `fk_notification_field_monitoring_rule`
  FOREIGN KEY (`rule_id`) REFERENCES `civicrm_notification_rule`(`id`)
  ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `civicrm_notification_rule_msg_template` (
                                                                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                                      `rule_id` INT UNSIGNED NOT NULL,
                                                                      `msg_template_id` INT UNSIGNED NOT NULL,
                                                                      `languages` TEXT NOT NULL,
                                                                      PRIMARY KEY (`id`),
  UNIQUE KEY `UI_rule_msg_template_id` (`rule_id`,`msg_template_id`),
  KEY `idx_rule_id` (`rule_id`),
  KEY `idx_msg_template_id` (`msg_template_id`),
  CONSTRAINT `fk_notification_rule_msg_tpl_rule`
  FOREIGN KEY (`rule_id`) REFERENCES `civicrm_notification_rule`(`id`)
  ON DELETE CASCADE,
  CONSTRAINT `fk_notification_rule_msg_tpl_msgtpl`
  FOREIGN KEY (`msg_template_id`) REFERENCES `civicrm_msg_template`(`id`)
  ON DELETE RESTRICT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `civicrm_notification_log` (
                                                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                        `rule_id` INT UNSIGNED DEFAULT NULL,
                                                        `contact_id` INT UNSIGNED DEFAULT NULL,
                                                        `channel` VARCHAR(64) NOT NULL,
  `payload` MEDIUMTEXT NULL,
  `status` VARCHAR(64) NOT NULL DEFAULT 'queued',
  `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_date` DATETIME NULL,
  `error_message` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_log_rule_id` (`rule_id`),
  CONSTRAINT `fk_notification_log_rule`
  FOREIGN KEY (`rule_id`) REFERENCES `civicrm_notification_rule`(`id`)
  ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
