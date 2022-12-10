ALTER TABLE `ac_addresses` ENGINE=INNODB;
ALTER TABLE `ac_categories` ENGINE=INNODB;
ALTER TABLE `ac_category_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_categories_to_stores` ENGINE=INNODB;
ALTER TABLE `ac_countries` ENGINE=INNODB;
ALTER TABLE `ac_country_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_coupons` ENGINE=INNODB;
ALTER TABLE `ac_coupon_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_coupons_products` ENGINE=INNODB;
ALTER TABLE `ac_currencies` ENGINE=INNODB;
ALTER TABLE `ac_customers` ENGINE=INNODB;
ALTER TABLE `ac_customer_groups` ENGINE=INNODB;
ALTER TABLE `ac_customer_transactions` ENGINE=INNODB;
ALTER TABLE `ac_online_customers` ENGINE=INNODB;
ALTER TABLE `ac_downloads` ENGINE=INNODB;
ALTER TABLE `ac_download_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_download_attribute_values` ENGINE=INNODB;
ALTER TABLE `ac_extensions` ENGINE=INNODB;
ALTER TABLE `ac_banners` ENGINE=INNODB;
ALTER TABLE `ac_banner_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_banner_stat` ENGINE=INNODB;
ALTER TABLE `ac_locations` ENGINE=INNODB;
ALTER TABLE `ac_languages` ENGINE=INNODB;
ALTER TABLE `ac_language_definitions` ENGINE=INNODB;
ALTER TABLE `ac_length_classes` ENGINE=INNODB;
ALTER TABLE `ac_length_class_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_manufacturers` ENGINE=INNODB;
ALTER TABLE `ac_manufacturers_to_stores` ENGINE=INNODB;
ALTER TABLE `ac_orders` ENGINE=INNODB;
ALTER TABLE `ac_order_downloads` ENGINE=INNODB;
ALTER TABLE `ac_order_downloads_history` ENGINE=INNODB;
ALTER TABLE `ac_order_data` ENGINE=INNODB;
ALTER TABLE `ac_order_data_types` ENGINE=INNODB;
ALTER TABLE `ac_order_history` ENGINE=INNODB;
ALTER TABLE `ac_order_options` ENGINE=INNODB;
ALTER TABLE `ac_order_products` ENGINE=INNODB;
ALTER TABLE `ac_order_statuses` ENGINE=INNODB;
ALTER TABLE `ac_order_status_ids` ENGINE=INNODB;
ALTER TABLE `ac_order_totals` ENGINE=INNODB;
ALTER TABLE `ac_products` ENGINE=INNODB;
ALTER TABLE `ac_product_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_product_discounts` ENGINE=INNODB;
ALTER TABLE `ac_product_options` ENGINE=INNODB;
ALTER TABLE `ac_product_option_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_product_option_values` ENGINE=INNODB;
ALTER TABLE `ac_product_option_value_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_products_related` ENGINE=INNODB;
ALTER TABLE `ac_product_specials` ENGINE=INNODB;
ALTER TABLE `ac_product_tags` ENGINE=INNODB;
ALTER TABLE `ac_products_to_categories` ENGINE=INNODB;
ALTER TABLE `ac_products_to_downloads` ENGINE=INNODB;
ALTER TABLE `ac_products_to_stores` ENGINE=INNODB;
ALTER TABLE `ac_reviews` ENGINE=INNODB;
ALTER TABLE `ac_settings` ENGINE=INNODB;
ALTER TABLE `ac_stock_statuses` ENGINE=INNODB;
ALTER TABLE `ac_stores` ENGINE=INNODB;
ALTER TABLE `ac_store_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_tax_classes` ENGINE=INNODB;
ALTER TABLE `ac_tax_class_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_tax_rates` ENGINE=INNODB;
ALTER TABLE `ac_tax_rate_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_url_aliases` ENGINE=INNODB;
ALTER TABLE `ac_users` ENGINE=INNODB;
ALTER TABLE `ac_user_groups` ENGINE=INNODB;
ALTER TABLE `ac_user_notifications` ENGINE=INNODB;
ALTER TABLE `ac_customer_notifications` ENGINE=INNODB;
ALTER TABLE `ac_weight_classes` ENGINE=INNODB;
ALTER TABLE `ac_weight_class_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_zones` ENGINE=INNODB;
ALTER TABLE `ac_zone_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_zones_to_locations` ENGINE=INNODB;
ALTER TABLE `ac_pages` ENGINE=INNODB;
ALTER TABLE `ac_page_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_contents` ENGINE=INNODB;
ALTER TABLE `ac_content_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_contents_to_stores` ENGINE=INNODB;
ALTER TABLE `ac_blocks` ENGINE=INNODB;
ALTER TABLE `ac_custom_blocks` ENGINE=INNODB;
ALTER TABLE `ac_custom_lists` ENGINE=INNODB;
ALTER TABLE `ac_block_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_block_templates` ENGINE=INNODB;
ALTER TABLE `ac_layouts` ENGINE=INNODB;
ALTER TABLE `ac_pages_layouts` ENGINE=INNODB;
ALTER TABLE `ac_block_layouts` ENGINE=INNODB;
ALTER TABLE `ac_pages_forms` ENGINE=INNODB;
ALTER TABLE `ac_forms` ENGINE=INNODB;
ALTER TABLE `ac_form_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_fields` ENGINE=INNODB;
ALTER TABLE `ac_field_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_field_values` ENGINE=INNODB;
ALTER TABLE `ac_form_groups` ENGINE=INNODB;
ALTER TABLE `ac_fields_groups` ENGINE=INNODB;
ALTER TABLE `ac_fields_group_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_messages` ENGINE=INNODB;
ALTER TABLE `ac_ant_messages` ENGINE=INNODB;
ALTER TABLE `ac_datasets` ENGINE=INNODB;
ALTER TABLE `ac_dataset_properties` ENGINE=INNODB;
ALTER TABLE `ac_dataset_definition` ENGINE=INNODB;
ALTER TABLE `ac_dataset_column_properties` ENGINE=INNODB;
ALTER TABLE `ac_dataset_values` ENGINE=INNODB;
ALTER TABLE `ac_resource_library` ENGINE=INNODB;
ALTER TABLE `ac_resource_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_resource_types` ENGINE=INNODB;
ALTER TABLE `ac_resource_map` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes_values` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes_value_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes_groups` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes_groups_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes_types` ENGINE=INNODB;
ALTER TABLE `ac_global_attributes_type_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_extension_dependencies` ENGINE=INNODB;
ALTER TABLE `ac_encryption_keys` ENGINE=INNODB;
ALTER TABLE `ac_tasks` ENGINE=INNODB;
ALTER TABLE `ac_task_details` ENGINE=INNODB;
ALTER TABLE `ac_task_steps` ENGINE=INNODB;

UPDATE `ac_orders` SET `customer_id` = NULL WHERE `customer_id` = 0;



ALTER TABLE `ac_orders` CHANGE COLUMN `coupon_id` `coupon_id` int(11) DEFAULT NULL;
UPDATE `ac_orders` SET `coupon_id` = NULL WHERE `coupon_id` = 0;

ALTER TABLE `ac_tax_rates` CHANGE COLUMN `zone_id` `zone_id` int(11) DEFAULT NULL;
UPDATE `ac_tax_rates` SET `zone_id` = NULL WHERE `zone_id` = 0;


ALTER TABLE `ac_category_descriptions`
  ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN `date_deleted` timestamp NULL,
  ADD COLUMN `stage_id` INT(6) NULL,
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `category_id`, `language_id`),
  ADD INDEX `stage_id` (`stage_id` ASC),
  ADD FOREIGN KEY (`category_id`) REFERENCES `ac_categories`(`category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_categories_to_stores`
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `category_id`, `store_id`),
  ADD FOREIGN KEY (`category_id`) REFERENCES `ac_categories`(`category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_coupon_descriptions`
  ADD FOREIGN KEY (`coupon_id`) REFERENCES `ac_coupons`(`coupon_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_coupon_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_coupons_products`
  ADD FOREIGN KEY (`coupon_id`) REFERENCES `ac_coupons`(`coupon_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_coupons_products`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_download_descriptions`
  ADD FOREIGN KEY (`download_id`) REFERENCES `ac_downloads`(`download_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_download_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_download_attribute_values`
  ADD FOREIGN KEY (`download_id`) REFERENCES `ac_downloads`(`download_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_banner_descriptions`
  ADD FOREIGN KEY (`banner_id`) REFERENCES `ac_banners`(`banner_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_banner_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_banner_stat`
  ADD FOREIGN KEY (`banner_id`) REFERENCES `ac_banners`(`banner_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_length_class_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`)  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_manufacturers_to_stores`
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `manufacturer_id`, `store_id`),

  ADD FOREIGN KEY (`manufacturer_id`) REFERENCES `ac_manufacturers`(`manufacturer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_orders`
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`);
ALTER TABLE `ac_orders`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`);
ALTER TABLE `ac_orders`
  ADD FOREIGN KEY (`currency_id`) REFERENCES `ac_currencies`(`currency_id`);
ALTER TABLE `ac_orders`
  ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE SET NULL;
ALTER TABLE `ac_orders`
  ADD FOREIGN KEY (`coupon_id`) REFERENCES `ac_coupons`(`coupon_id`) ON DELETE SET NULL;
ALTER TABLE `ac_orders`
  ADD FOREIGN KEY (`order_status_id`) REFERENCES `ac_order_status_ids`(`order_status_id`);

ALTER TABLE `ac_customer_transactions`
  ADD FOREIGN KEY  (`customer_id`) REFERENCES `ac_customers`(`customer_id`);

ALTER TABLE `ac_order_products`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_order_downloads`
  ADD FOREIGN KEY (`download_id`) REFERENCES `ac_downloads`(`download_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `ac_order_downloads`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_order_downloads`
  ADD FOREIGN KEY (`order_product_id`) REFERENCES `ac_order_products`(`order_product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`order_download_id`) REFERENCES `ac_order_downloads`(`order_download_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`download_id`) REFERENCES `ac_downloads`(`download_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`order_product_id`) REFERENCES `ac_order_products`(`order_product_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `ac_order_data_types`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`)  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_order_statuses`
  ADD FOREIGN KEY (`order_status_id`) REFERENCES `ac_order_status_ids`(`order_status_id`);
ALTER TABLE `ac_order_statuses`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_order_history`
  ADD FOREIGN KEY (`order_status_id`) REFERENCES `ac_order_status_ids`(`order_status_id`);

ALTER TABLE `ac_order_totals`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_discounts`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `ac_product_options`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_option_values`
  ADD FOREIGN KEY (`product_option_id`) REFERENCES `ac_product_options`(`product_option_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_product_option_values`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_products_to_categories`
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`product_id`,`category_id`),
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`category_id`) REFERENCES `ac_categories`(`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_products_to_downloads`
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`product_id`,`download_id`),
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`download_id`) REFERENCES `ac_downloads`(`download_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_products_to_stores`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_products_to_stores`
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_reviews`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;;

ALTER TABLE `ac_settings`
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_stock_statuses`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_store_descriptions`
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_store_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_tax_class_descriptions`
  ADD FOREIGN KEY (`tax_class_id`) REFERENCES `ac_tax_classes`(`tax_class_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_tax_class_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_tax_rates`
  ADD FOREIGN KEY (`tax_class_id`) REFERENCES `ac_tax_classes`(`tax_class_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_tax_rates`
  ADD FOREIGN KEY (`location_id`) REFERENCES `ac_locations`(`location_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_tax_rates`
  ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`) ON DELETE SET NULL ON UPDATE CASCADE;


ALTER TABLE `ac_url_aliases`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_users`
  ADD FOREIGN KEY (`user_group_id`) REFERENCES `ac_user_groups`(`user_group_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `ac_customer_notifications`
  ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_weight_class_descriptions`
  ADD FOREIGN KEY (`weight_class_id`) REFERENCES `ac_weight_classes`(`weight_class_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_weight_class_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_content_descriptions`
  ADD FOREIGN KEY (`content_id`) REFERENCES `ac_contents`(`content_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_content_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_contents_to_stores`
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `content_id`, `store_id`),
  ADD FOREIGN KEY (`content_id`) REFERENCES `ac_contents`(`content_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_custom_blocks`
  ADD FOREIGN KEY (`block_id`) REFERENCES `ac_blocks`(`block_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_custom_lists`
  ADD FOREIGN KEY (`custom_block_id`) REFERENCES `ac_custom_blocks`(`custom_block_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_block_descriptions`
  ADD FOREIGN KEY (`custom_block_id`) REFERENCES `ac_custom_blocks`(`custom_block_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_block_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_block_templates`
  ADD FOREIGN KEY (`block_id`) REFERENCES `ac_blocks`(`block_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_pages_layouts`
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
   DROP PRIMARY KEY,
   ADD PRIMARY KEY (`id`,`layout_id`,`page_id`),
  ADD FOREIGN KEY (`layout_id`) REFERENCES `ac_layouts`(`layout_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`page_id`) REFERENCES `ac_pages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_pages_forms`
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`form_id`,`page_id`),
  ADD FOREIGN KEY (`form_id`) REFERENCES `ac_forms`(`form_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`page_id`) REFERENCES `ac_pages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE;



ALTER TABLE `ac_fields`
  ADD FOREIGN KEY (`form_id`) REFERENCES `ac_forms`(`form_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_field_descriptions`
  ADD FOREIGN KEY (`field_id`) REFERENCES `ac_fields`(`field_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_field_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_field_values`
  ADD FOREIGN KEY (`field_id`) REFERENCES `ac_fields`(`field_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_form_groups`
  ADD FOREIGN KEY (`form_id`) REFERENCES `ac_forms`(`form_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_fields_groups`
  ADD FOREIGN KEY (`field_id`) REFERENCES `ac_fields`(`field_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_fields_groups`
  ADD FOREIGN KEY (`group_id`) REFERENCES `ac_form_groups`(`group_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_fields_group_descriptions`
  ADD FOREIGN KEY (`group_id`) REFERENCES `ac_form_groups`(`group_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_fields_group_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_dataset_properties`
  ADD FOREIGN KEY (`dataset_id`) REFERENCES `ac_datasets`(`dataset_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_dataset_definition`
  ADD FOREIGN KEY (`dataset_id`) REFERENCES `ac_datasets`(`dataset_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_dataset_column_properties`
  ADD FOREIGN KEY (`dataset_column_id`) REFERENCES `ac_dataset_definition`(`dataset_column_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_dataset_values`
  ADD FOREIGN KEY (`dataset_column_id`) REFERENCES `ac_dataset_definition`(`dataset_column_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_resource_library`
  ADD FOREIGN KEY (`type_id`) REFERENCES `ac_resource_types`(`type_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `ac_global_attributes_descriptions`
  ADD FOREIGN KEY (`attribute_id`) REFERENCES `ac_global_attributes`(`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_global_attributes_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_global_attributes_values`
  ADD FOREIGN KEY (`attribute_id`) REFERENCES `ac_global_attributes`(`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_global_attributes_value_descriptions`
  ADD FOREIGN KEY (`attribute_id`) REFERENCES `ac_global_attributes`(`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_global_attributes_value_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;


DROP TABLE IF EXISTS `ac_jobs`;
CREATE TABLE `ac_jobs` (
                           `job_id`        int(11) NOT NULL AUTO_INCREMENT,
                           `job_name`      varchar(255) NOT NULL,
                           `status`        int(11)          DEFAULT '0' COMMENT '0 - disabled, 1 - ready, 2 - running, 3 - failed, 4 - scheduled, 5 - completed',
                           `configuration` longtext COMMENT 'configuration for job-class',
                           `start_time`    datetime         DEFAULT NULL,
                           `last_time_run` timestamp NULL   DEFAULT NULL,
                           `last_result`   int(11) NOT NULL DEFAULT '0' COMMENT '1 - success, 0 - failed',
                           `actor_type`    int(11)          DEFAULT NULL COMMENT '0 - System user, 1 - Admin user, 2 - Customer',
                           `actor_id`      int(11)          DEFAULT 0,
                           `actor_name`    varchar(128)     DEFAULT '',
                           `date_added`    timestamp NULL   DEFAULT CURRENT_TIMESTAMP,
                           `date_modified` timestamp NULL   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                           PRIMARY KEY (`job_id`, `job_name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

ALTER TABLE `ac_customers`
    CHANGE COLUMN `last_login` `last_login` timestamp NULL DEFAULT NULL,
    CHANGE COLUMN `customer_group_id` `customer_group_id` INT(11) NULL,
    ADD COLUMN `settings` MEDIUMTEXT COMMENT 'php-serialized stretch data' AFTER `last_login`;

CREATE INDEX `ac_customers_email_idx` ON `ac_customers` (`email`);

ALTER TABLE `ac_users`
    CHANGE COLUMN `last_login` `last_login` timestamp NULL DEFAULT NULL;
ALTER TABLE `ac_ant_messages`
    CHANGE COLUMN `start_date` `start_date` timestamp NULL default NULL,
    CHANGE COLUMN `end_date` `end_date` timestamp NULL default NULL;
ALTER TABLE `ac_extensions`
    CHANGE COLUMN `date_installed` `date_installed` timestamp NULL default NULL;
ALTER TABLE `ac_tasks`
  CHANGE COLUMN `last_time_run` `date_installed` timestamp NULL default NULL;
ALTER TABLE `ac_task_steps`
  CHANGE COLUMN `last_time_run` `date_installed` timestamp NULL default NULL;

ALTER TABLE `ac_products` CHANGE COLUMN `date_available` `date_available` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

DROP TABLE IF EXISTS `ac_customer_notes`;
CREATE TABLE `ac_customer_notes` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ac_customer_notes`
  ADD FOREIGN KEY (`user_id`) REFERENCES `ac_users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_customer_notes`
  ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS `ac_customer_communications`;
CREATE TABLE `ac_customer_communications` (
  `communication_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `sent_to_address` text COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`communication_id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ac_customer_communications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `ac_customers` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_object_attribute_values`;
CREATE TABLE `ac_object_attribute_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL,
  `object_type` varchar(255) NOT NULL,
  `object_type_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `attribute_value` varchar(255) NOT NULL,
  `attribute_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_inx` (`object_id`,`object_type`,`attribute_id`,`object_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ac_ant_messages`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_banner_descriptions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_banner_descriptions`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_banners`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_banners`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_descriptions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_descriptions`
  MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP
  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_layouts`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_layouts`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_templates`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_templates`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_blocks`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_blocks`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_categories`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_categories`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_content_descriptions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_content_descriptions`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_coupons`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
CHANGE COLUMN `uses_total` `uses_total` INT(11) NOT NULL DEFAULT 0 ,
CHANGE COLUMN `uses_customer` `uses_customer` INT(11) NOT NULL DEFAULT 0 ;

ALTER TABLE `ac_currencies`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_custom_blocks`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_custom_blocks`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_custom_lists`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_communications`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_communications`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_notes`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_notes`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_notifications`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_notifications`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_transactions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_transactions`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_customers`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_customers`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_downloads`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
CHANGE COLUMN `download_id` `download_id` INT(11) NULL;

ALTER TABLE `ac_downloads`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_extensions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_extensions`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_global_attributes_type_descriptions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_global_attributes_type_descriptions`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_jobs`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_jobs`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_language_definitions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_language_definitions`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_layouts`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_layouts`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_length_classes`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_length_classes`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_locations`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_locations`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_messages`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_messages`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_online_customers`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_data_types`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_data_types`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_downloads`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_downloads`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_history`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_history`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_orders`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_orders`
  MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP
  ON UPDATE CURRENT_TIMESTAMP,
  CHANGE COLUMN `ip` `ip` VARCHAR(50) NOT NULL DEFAULT ''
  AFTER `coupon_id`,
  CHANGE COLUMN `payment_method_data` `payment_method_data` TEXT NOT NULL DEFAULT ''
  AFTER `ip`,
  CHANGE COLUMN `shipping_zone_id` `shipping_zone_id` INT(11) NULL DEFAULT NULL,
  CHANGE COLUMN `payment_zone_id` `payment_zone_id` INT(11) NULL DEFAULT NULL,
  CHANGE COLUMN `language_id` `language_id` INT(11) NOT NULL;

ALTER TABLE `ac_page_descriptions`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_pages`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_pages`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_product_discounts`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_product_discounts`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_product_specials`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_product_specials`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_products`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_products`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_resource_library`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_resource_library`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_reviews`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_reviews`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_settings`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_settings`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_task_details`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_task_details`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_task_steps`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_task_steps`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_tasks`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_tasks`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_tax_classes`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_tax_classes`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_tax_rates`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_tax_rates`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_user_groups`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_user_groups`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_users`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_users`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_weight_classes`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_weight_classes`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_zones_to_locations`
MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_zones_to_locations`
MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `ac_ant_messages` MODIFY COLUMN `viewed_date` timestamp NULL;

ALTER TABLE `ac_product_discounts` MODIFY COLUMN `date_start` date NULL;
ALTER TABLE `ac_product_discounts` MODIFY COLUMN `date_end` date NULL;
ALTER TABLE `ac_product_specials` MODIFY COLUMN `date_start` date NULL;
ALTER TABLE `ac_product_specials` MODIFY COLUMN `date_end` date NULL;

ALTER TABLE `ac_customers`
ADD FOREIGN KEY (`customer_group_id`) REFERENCES `ac_customer_groups`(`customer_group_id_id`)
ON DELETE SET NULL ON UPDATE CASCADE;


ALTER TABLE `ac_pages`
MODIFY COLUMN `parent_page_id` INT(10) NULL DEFAULT NULL;
UPDATE `ac_pages` SET `parent_page_id` = NULL WHERE `parent_page_id` = '0';
ALTER TABLE `ac_pages` ADD CONSTRAINT `ac_pages_parent_fk` FOREIGN KEY (`parent_page_id`) REFERENCES `ac_pages` (`page_id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `ac_block_layouts`
CHANGE COLUMN `parent_instance_id` `parent_instance_id` INT(10) NULL DEFAULT NULL,
CHANGE COLUMN `custom_block_id` `custom_block_id` INT(10) NULL DEFAULT NULL;

UPDATE `ac_block_layouts` SET `parent_instance_id` = NULL WHERE `parent_instance_id` = '0';
UPDATE `ac_block_layouts` SET `custom_block_id` = NULL WHERE `custom_block_id` = '0';

#TODO: need to remove orphan block before FK creation!!!

ALTER TABLE `ac_block_layouts`
ADD CONSTRAINT `ac_block_layouts_parent_fk`
	FOREIGN KEY (`parent_instance_id`)
	REFERENCES `ac_block_layouts` (`instance_id`)
	ON DELETE CASCADE
	ON UPDATE CASCADE,
ADD CONSTRAINT `ac_block_layouts_cb_fk`
	FOREIGN KEY (`custom_block_id`)
	REFERENCES `ac_custom_blocks` (`custom_block_id`)
	ON DELETE CASCADE
	ON UPDATE CASCADE;


ALTER TABLE `ac_downloads`
CHANGE COLUMN `activate_order_status_id` `activate_order_status_id` INT(11) NULL DEFAULT NULL;

ALTER TABLE `ac_downloads`
ADD CONSTRAINT `ac_downloads_order_status_fk`
  FOREIGN KEY (`activate_order_status_id`)
  REFERENCES `ac_order_statuses` (`order_status_id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE `ac_customer_transactions`
ADD INDEX `ac_customer_transactions_ibfk_2_idx` (`order_id` ASC);
ALTER TABLE `ac_customer_transactions`
ADD CONSTRAINT `ac_customer_transactions_ibfk_2`
  FOREIGN KEY (`order_id`)
  REFERENCES `ac_orders` (`order_id`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ac_global_attributes_value_descriptions`
ADD CONSTRAINT `ac_global_attributes_value_descriptions_ibfk_3`
  FOREIGN KEY (`attribute_value_id`)
  REFERENCES `ac_global_attributes_values` (`attribute_value_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_field_values`
ADD INDEX `ac_field_values_ibfk_2_idx` (`language_id` ASC);
ALTER TABLE `ac_field_values`
ADD CONSTRAINT `ac_field_values_ibfk_2`
  FOREIGN KEY (`language_id`)
  REFERENCES `ac_languages` (`language_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_customer_communications`
CHANGE COLUMN `user_id` `user_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `ac_customer_communications`
ADD CONSTRAINT `ac_customer_communications_ibfk_2`
 FOREIGN KEY (`user_id`)
 REFERENCES `ac_users` (`user_id`)
 ON DELETE NO ACTION
 ON UPDATE CASCADE;

ALTER TABLE `ac_task_details`
ADD CONSTRAINT `ac_task_details_ibfk_1`
  FOREIGN KEY (`task_id`)
  REFERENCES `ac_tasks` (`task_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;



UPDATE `ac_global_attributes` SET `attribute_group_id` = NULL WHERE `attribute_group_id` = '0';

ALTER TABLE `ac_global_attributes`
ADD INDEX `ac_global_attributes_ibfk_1_idx` (`attribute_group_id` ASC);
ALTER TABLE `ac_global_attributes`
ADD CONSTRAINT `ac_global_attributes_ibfk_1`
  FOREIGN KEY (`attribute_group_id`)
  REFERENCES `ac_global_attributes_groups` (`attribute_group_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE `ac_online_customers`
CHANGE COLUMN `customer_id` `customer_id` INT(11) NULL DEFAULT NULL ,
ADD INDEX `ac_online_customers_fk_1_idx` (`customer_id` ASC);
ALTER TABLE `ac_online_customers`
ADD CONSTRAINT `ac_online_customers_fk_1`
  FOREIGN KEY (`customer_id`)
  REFERENCES `ac_customers` (`customer_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_global_attributes`
CHANGE COLUMN `attribute_parent_id` `attribute_parent_id` INT(11) NULL DEFAULT NULL ;

UPDATE `ac_global_attributes` SET `attribute_parent_id` = NULL WHERE `attribute_parent_id` = '0';

ALTER TABLE `ac_global_attributes`
ADD CONSTRAINT `ac_global_attributes_ibfk_2`
  FOREIGN KEY (`attribute_parent_id`)
  REFERENCES `ac_global_attributes` (`attribute_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_task_steps`
ADD CONSTRAINT `ac_task_steps_fk`
  FOREIGN KEY (`task_id`)
  REFERENCES `ac_tasks` (`task_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_global_attributes_type_descriptions`
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `attribute_type_id`, `language_id`),
ADD INDEX `ac_global_attributes_type_descriptions_fk_2_idx` (`language_id` ASC);
ALTER TABLE `ac_global_attributes_type_descriptions`
ADD CONSTRAINT `ac_global_attributes_type_descriptions_fk_1`
  FOREIGN KEY (`attribute_type_id`)
  REFERENCES `ac_global_attributes_types` (`attribute_type_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `ac_global_attributes_type_descriptions_fk_2`
  FOREIGN KEY (`language_id`)
  REFERENCES `ac_languages` (`language_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_contents`
CHANGE COLUMN `parent_content_id` `parent_content_id` INT(11) NULL DEFAULT NULL ,
ADD COLUMN `hide_title` INT(1) NULL DEFAULT '0',
DROP PRIMARY KEY,
ADD INDEX `ac_contents_fk_1_idx` (`parent_content_id` ASC);

UPDATE `ac_contents` SET `parent_content_id` = NULL WHERE `parent_content_id` = '0';

ALTER TABLE `ac_contents`
ADD CONSTRAINT `ac_contents_fk_1`
  FOREIGN KEY (`parent_content_id`)
  REFERENCES `ac_contents` (`content_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;


ALTER TABLE `ac_global_attributes_groups_descriptions`
ADD INDEX `ac_global_attributes_groups_descriptions_fk_2_idx` (`language_id` ASC);
ALTER TABLE `ac_global_attributes_groups_descriptions`
ADD CONSTRAINT `ac_global_attributes_groups_descriptions_fk_1`
  FOREIGN KEY (`attribute_group_id`)
  REFERENCES `ac_global_attributes_groups` (`attribute_group_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `ac_global_attributes_groups_descriptions_fk_2`
  FOREIGN KEY (`language_id`)
  REFERENCES `ac_languages` (`language_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

  ALTER TABLE `ac_product_discounts`
  ADD INDEX `ac_product_discounts_ibfk_2_idx` (`customer_group_id` ASC);
  ALTER TABLE `ac_product_discounts`
  ADD CONSTRAINT `ac_product_discounts_ibfk_2`
    FOREIGN KEY (`customer_group_id`)
    REFERENCES `ac_customer_groups` (`customer_group_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE `ac_product_specials`
ADD INDEX `ac_product_specials_ibfk_2_idx` (`customer_group_id` ASC);
ALTER TABLE `ac_product_specials`
ADD CONSTRAINT `ac_product_specials_ibfk_2`
  FOREIGN KEY (`customer_group_id`)
  REFERENCES `ac_customer_groups` (`customer_group_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_order_downloads`
ADD INDEX `ac_order_downloads_ibfk_3_idx` (`download_id` ASC);
ALTER TABLE `ac_order_downloads`
ADD CONSTRAINT `ac_order_downloads_ibfk_3`
  FOREIGN KEY (`download_id`)
  REFERENCES `ac_downloads` (`download_id`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ac_categories`
CHANGE COLUMN `parent_id` `parent_id` INT(11) NULL DEFAULT NULL ,
ADD INDEX `ac_categories_fk_1_idx` (`parent_id` ASC);
UPDATE `ac_categories` SET `parent_id` = NULL WHERE `parent_id` = '0';

ALTER TABLE `ac_categories`
ADD CONSTRAINT `ac_categories_fk_1`
FOREIGN KEY (`parent_id`)
REFERENCES `ac_categories` (`category_id`)
ON DELETE CASCADE
ON UPDATE CASCADE;

ALTER TABLE `ac_order_history`
ADD CONSTRAINT `ac_order_history_ibfk_2`
  FOREIGN KEY (`order_id`)
  REFERENCES `ac_orders` (`order_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_order_products`
CHANGE COLUMN `model` `model` VARCHAR(64) NOT NULL DEFAULT '',
ADD INDEX `ac_order_products_ibfk_2_idx` (`product_id` ASC),
ADD CONSTRAINT `ac_order_products_ibfk_2`
  FOREIGN KEY (`product_id`)
  REFERENCES `ac_products` (`product_id`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ac_extension_dependencies`
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `extension_id`, `extension_parent_id`),

ADD CONSTRAINT `ac_extension_dependencies_fk_1`
  FOREIGN KEY (`extension_id`)
  REFERENCES `ac_extensions` (`extension_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_products_related`
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `product_id`, `related_id`),
ADD INDEX `ac_products_related_ibfk_2_idx` (`related_id` ASC),
ADD FOREIGN KEY (`related_id`) REFERENCES `ac_products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_order_downloads_history`
ADD CONSTRAINT `ac_order_downloads_history_ibfk_4`
  FOREIGN KEY (`download_id`)
  REFERENCES `ac_downloads` (`download_id`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ac_length_class_descriptions`
ADD CONSTRAINT `ac_length_class_descriptions_ibfk_2`
  FOREIGN KEY (`length_class_id`)
  REFERENCES `ac_length_classes` (`length_class_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_banner_stat`
ADD INDEX `ac_banner_stat_ibfk_2_idx` (`store_id` ASC);
ALTER TABLE `ac_banner_stat`
ADD CONSTRAINT `ac_banner_stat_ibfk_2`
  FOREIGN KEY (`store_id`)
  REFERENCES `ac_stores` (`store_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_order_options`
ADD INDEX `ac_order_options_fk_2_idx` (`order_product_id` ASC);
ALTER TABLE `ac_order_options`
ADD CONSTRAINT `ac_order_options_fk_1`
  FOREIGN KEY (`order_id`)
  REFERENCES `ac_orders` (`order_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `ac_order_options_fk_2`
  FOREIGN KEY (`order_product_id`)
  REFERENCES `ac_order_products` (`order_product_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_global_attributes`
ADD INDEX `ac_global_attributes_ibfk_3_idx` (`attribute_type_id` ASC);
ALTER TABLE `ac_global_attributes`
ADD CONSTRAINT `ac_global_attributes_ibfk_3`
  FOREIGN KEY (`attribute_type_id`)
  REFERENCES `ac_global_attributes_types` (`attribute_type_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_block_layouts`
ADD INDEX `ac_block_layouts_ibfk_3_idx` (`layout_id` ASC);
ALTER TABLE `ac_block_layouts`
ADD CONSTRAINT `ac_block_layouts_ibfk_3`
  FOREIGN KEY (`layout_id`)
  REFERENCES `ac_layouts` (`layout_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_customers`
CHANGE COLUMN `address_id` `address_id` INT(11) NULL DEFAULT NULL ,
ADD INDEX `ac_customers_ibfk_3_idx` (`address_id` ASC);
ALTER TABLE `ac_customers`
ADD CONSTRAINT `ac_customers_ibfk_3`
  FOREIGN KEY (`address_id`)
  REFERENCES `ac_addresses` (`address_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

  ALTER TABLE `ac_block_layouts`
  ADD INDEX `ac_block_layouts_ibfk_4_idx` (`block_id` ASC);
  ALTER TABLE `ac_block_layouts`
  ADD CONSTRAINT `ac_block_layouts_ibfk_4`
    FOREIGN KEY (`block_id`)
    REFERENCES `ac_blocks` (`block_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;



CREATE TABLE `ac_audits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  `alias_id` int(11) DEFAULT NULL,
  `alias_name` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8_general_ci NOT NULL,
  `request_id` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  `main_auditable_model` varchar(255) COLLATE utf8_general_ci,
  `main_auditable_id` int(11) DEFAULT NULL,
  `auditable_model` varchar(255) COLLATE utf8_general_ci NOT NULL,
  `auditable_id` int(11) DEFAULT NULL,
  `attribute_name` varchar(255) COLLATE utf8_general_ci NOT NULL,
  `old_value` text COLLATE utf8_general_ci,
  `new_value` text COLLATE utf8_general_ci,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_deleted` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`user_type`,`user_name`),
  KEY `request_id` (`request_id`,`session_id`),
  KEY `main_auditable_model` (`main_auditable_model`,`main_auditable_id`),
  KEY `auditable_model` (`auditable_model`,`auditable_id`),
  KEY `attribute_name` (`attribute_name`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--soft delete sign
ALTER TABLE `ac_banner_descriptions`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_banner_descriptions`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC),
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`, `banner_id`, `language_id`);


ALTER TABLE `ac_banners`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_banners`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_block_descriptions`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_block_descriptions`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_block_layouts`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_block_layouts`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_block_templates`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_block_templates`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_blocks`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_blocks`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_categories`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_categories`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_content_descriptions`
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`, `content_id`, `language_id`),
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`,
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_coupons`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_coupons`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_currencies`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_currencies`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_custom_blocks`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_custom_blocks`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_custom_lists`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_custom_lists`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_customer_communications`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_customer_communications`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_customer_notes`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`,
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_customer_notifications`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`,`customer_id`,`sendpoint`,`protocol`),
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_customer_transactions`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_customer_transactions`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_customers`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_customers`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_downloads`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_downloads`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_extensions`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_extensions`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_global_attributes_type_descriptions`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_global_attributes_type_descriptions`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_jobs`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_jobs`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_language_definitions`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_language_definitions`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_layouts`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_layouts`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_length_classes`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_length_classes`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_locations`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_locations`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_messages`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_messages`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_order_data`
    MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`,
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`,`order_id`,`type_id`),
    ADD INDEX `stage_id` (`stage_id` ASC),
    ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD FOREIGN KEY (`type_id`) REFERENCES `ac_order_data_types`(`type_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `ac_order_data_types`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_order_data_types`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_order_downloads`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_order_downloads`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_order_history`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;

ALTER TABLE `ac_orders`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;


ALTER TABLE `ac_page_descriptions`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`,
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC),
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (`id`,`page_id`,`language_id`),
    ADD FOREIGN KEY (`page_id`) REFERENCES `ac_pages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_pages`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_pages`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_product_discounts`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_product_discounts`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_product_specials`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_product_specials`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_products`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_products`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_resource_descriptions`
    MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`,
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC),
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`,`resource_id`,`language_id`),

    ADD FOREIGN KEY (`resource_id`) REFERENCES `ac_resource_library`(`resource_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_resource_library`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_resource_library`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_resource_map`
    MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`,
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (`id`,`resource_id`, `object_name`, `object_id`),
    ADD INDEX `stage_id` (`stage_id` ASC),
    ADD FOREIGN KEY (`resource_id`) REFERENCES `ac_resource_library`(`resource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_reviews`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_reviews`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_settings`
    ADD COLUMN `group_id` int(11) NOT NULL DEFAULT 0 AFTER `group`,
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_settings`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_task_details`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_task_details`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_task_steps`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_task_steps`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_tasks`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_tasks`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_tax_classes`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_tax_classes`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_tax_rates`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_tax_rates`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_user_groups`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_user_groups`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_user_notifications`
    MODIFY COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`,
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC),
    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`,`user_id`,`store_id`,`section`,`sendpoint`,`protocol`),
    ADD FOREIGN KEY (`user_id`) REFERENCES `ac_users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_users`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_users`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_weight_classes`
    ADD COLUMN `date_deleted` TIMESTAMP NULL AFTER `date_modified`;
ALTER TABLE `ac_weight_classes`
    ADD COLUMN `stage_id` INT(6) NULL AFTER `date_deleted`,
    ADD INDEX `stage_id` (`stage_id` ASC);


ALTER TABLE `ac_addresses`
    CHANGE COLUMN `company` `company` VARCHAR(64) NOT NULL DEFAULT '',
    CHANGE COLUMN `zone_id` `zone_id` INT(11)     NULL     DEFAULT NULL,
    ADD COLUMN `date_added`           TIMESTAMP   NULL     DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `date_modified`        TIMESTAMP   NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN `date_deleted`         TIMESTAMP   NULL;



ALTER TABLE `ac_contents`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_countries`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_country_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`, `country_id`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_coupon_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`, `coupon_id`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_customer_groups`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_download_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`, `download_id`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_encryption_keys`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_field_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `field_id`, `language_id`),
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_field_values`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_fields`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_fields_group_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `group_id`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_fields_groups`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `group_id`, `field_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_form_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `form_id`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC),
ADD FOREIGN KEY (`form_id`) REFERENCES `ac_forms`(`form_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_form_groups`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_forms`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_global_attributes`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_global_attributes_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `attribute_id`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_global_attributes_groups`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_global_attributes_groups_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC),
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
DROP PRIMARY KEY,
PRIMARY KEY (`id`,`attribute_group_id`,`language_id`);

ALTER TABLE `ac_global_attributes_types`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_global_attributes_value_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`attribute_value_id`, `attribute_id`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_global_attributes_values`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_languages`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_length_class_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`length_class_id`,`language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_weight_class_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`,`weight_class_id`,`language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_manufacturers`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_order_status_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`order_status_id`,`language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_order_statuses`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
  ADD COLUMN `display_status` int(1) NOT NULL DEFAULT '1'
  AFTER `status_text_id`,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_order_status_descriptions`
  ADD FOREIGN KEY (`order_status_id`)
REFERENCES `ac_order_statuses` (`order_status_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_order_status_descriptions`
  ADD FOREIGN KEY (`language_id`)
REFERENCES `ac_languages` (`language_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ac_order_totals`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL;

ALTER TABLE `ac_order_downloads_history`
CHANGE COLUMN `time` `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL;

ALTER TABLE `ac_order_options`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL;

ALTER TABLE `ac_order_products`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL;

ALTER TABLE `ac_product_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`product_id`,`language_id`),
ADD INDEX `stage_id` (`stage_id` ASC),
ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `ac_product_option_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC),
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`product_option_id`,`language_id`),
ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`product_option_id`) REFERENCES `ac_product_options`(`product_option_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `ac_product_options`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_product_option_value_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`product_option_value_id`,`language_id`),
ADD INDEX `stage_id` (`stage_id` ASC),
ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`product_option_value_id`) REFERENCES `ac_product_option_values` (`product_option_value_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_option_values`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_product_tags`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP
ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`, `product_id`, `tag`, `language_id`),
ADD INDEX `stage_id` (`stage_id` ASC),
ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `ac_resource_types`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_stock_statuses`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_store_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`,`store_id`,`language_id`),
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_stores`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_tax_class_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC),
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
PRIMARY KEY (`id`,`tax_class_id`,`language_id`);

ALTER TABLE `ac_tax_rate_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC),
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
DROP PRIMARY KEY,
PRIMARY KEY (`id`,`tax_rate_id`,`language_id`),
ADD FOREIGN KEY (`tax_rate_id`) REFERENCES `ac_tax_rates`(`tax_rate_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `ac_url_aliases`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_zone_descriptions`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC),
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
DROP PRIMARY KEY,
PRIMARY KEY (`id`,`zone_id`,`language_id`),
ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_zones`
ADD COLUMN `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `date_deleted` timestamp NULL,
ADD COLUMN `stage_id` INT(6) NULL,
ADD INDEX `stage_id` (`stage_id` ASC);

ALTER TABLE `ac_products`
ADD COLUMN `product_type_id` INT(1) NULL,
ADD COLUMN `uuid` varchar(255) DEFAULT NULL AFTER `product_id`,
ADD UNIQUE INDEX `uuid_UNIQUE` (`uuid` ASC);

ALTER TABLE `ac_categories`
ADD COLUMN `uuid` varchar(255) DEFAULT NULL AFTER `category_id`,
ADD UNIQUE INDEX `uuid_UNIQUE` (`uuid` ASC);

ALTER TABLE `ac_manufacturers`
ADD COLUMN `uuid` varchar(255) DEFAULT NULL AFTER `manufacturer_id`,
ADD UNIQUE INDEX `uuid_UNIQUE` (`uuid` ASC);

DROP TABLE IF EXISTS `ac_object_types`;
CREATE TABLE `ac_object_types` (
  `object_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `object_type` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  `sort_order` int(11) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_deleted` timestamp NULL,
  `stage_id` int(6) DEFAULT NULL,
  PRIMARY KEY (`object_type_id`),
  INDEX `stage_idx` (`stage_id` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_object_type_descriptions`;
CREATE TABLE `ac_object_type_descriptions` (
  `object_type_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_deleted` timestamp NULL,
  `stage_id` int(6) DEFAULT NULL,
  KEY `object_type_id_language_idx` (`object_type_id`,`language_id`),
  INDEX `stage_idx` (`stage_id` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ac_object_type_descriptions`
ADD CONSTRAINT `ac_object_type_descriptions_object_types_fk`
  FOREIGN KEY (`object_type_id`)
  REFERENCES `ac_object_types` (`object_type_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;


DROP TABLE IF EXISTS `ac_object_type_aliases`;
CREATE TABLE `ac_object_type_aliases` (
  `object_type` varchar(255) NOT NULL,
  PRIMARY KEY (`object_type`),
  UNIQUE KEY `object_type_UNIQUE` (`object_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ac_object_type_aliases` (`object_type`) VALUES ('Product');
INSERT INTO `ac_object_type_aliases` (`object_type`) VALUES ('Category');

DROP TABLE IF EXISTS `ac_object_field_settings`;
CREATE TABLE `ac_object_field_settings` (
  `object_type` varchar(255) NOT NULL,
  `object_type_id` int(11) DEFAULT NULL,
  `object_field_name` varchar(255) NOT NULL,
  `field_setting` varchar(255) NOT NULL,
  `field_setting_value` varchar(255) NOT NULL,
  UNIQUE KEY `index1` (`object_type`,`object_type_id`,`object_field_name`,`field_setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_global_attribute_group_to_object_type`;
CREATE TABLE `ac_global_attribute_group_to_object_type` (
  `attribute_group_id` int(11) NOT NULL,
  `object_type_id` int(11) NOT NULL,
  KEY `attribute_group_id_object_type_idx` (`attribute_group_id`,`object_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_audit_event_descriptions`;
CREATE TABLE `ac_audit_event_descriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_event_id` int(11) NOT NULL,
  `auditable_model_id` int(11) NOT NULL,
  `auditable_id` int(11) NOT NULL,
  `field_name` varchar(128) NOT NULL,
  `old_value` text,
  `new_value` text,
  PRIMARY KEY (`id`),
  KEY `ac_audit_event_id_idx` (`audit_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_audit_events`;
CREATE TABLE `ac_audit_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(128) NOT NULL,
  `audit_session_id` int(11) NOT NULL,
  `audit_user_id` int(11) NOT NULL,
  `audit_alias_id` int(11) DEFAULT NULL,
  `event_type_id` int(11) NOT NULL,
  `main_auditable_model_id` int(11) NOT NULL,
  `main_auditable_id` int(11) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id_UNIQUE` (`request_id`,`audit_user_id`,`event_type_id`,`main_auditable_model_id`,`main_auditable_id`),
  KEY `ac_audit_events_main_auditable_id_idx` (`main_auditable_id`),
  KEY `ac_audit_events_date_added_event_idx` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_audit_models`;
CREATE TABLE `ac_audit_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_audit_sessions`;
CREATE TABLE `ac_audit_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ac_audit_users`;
CREATE TABLE `ac_audit_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_userid_indx` (`id`,`name`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ac_global_attributes`
ADD COLUMN `name` VARCHAR(255) NULL AFTER `attribute_type_id`;

ALTER TABLE `ac_orders`
ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE SET NULL;

ALTER TABLE `ac_language_definitions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_customers`
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `ac_country_descriptions`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_country_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zones`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_zones_to_locations`
  ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zones_to_locations`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zones_to_locations`
  ADD FOREIGN KEY (`location_id`) REFERENCES `ac_locations`(`location_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_addresses`
  ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_addresses`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `ac_addresses`
  ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO `ac_global_attributes_types` (`attribute_type_id`, `type_key`, `controller`, `sort_order`, `status`) VALUES
(3, 'object_attribute', 'responses/catalog/attribute/getObjectAttributeSubform', 3, 1);

INSERT INTO `ac_global_attributes_type_descriptions` (`attribute_type_id`, `language_id`, `type_name`, `date_added`)
VALUES
(3, 1, 'Object Attribute', NOW());

#set default order_status_id to complete
ALTER TABLE `ac_order_products`
ADD COLUMN  `order_status_id` INT NOT NULL DEFAULT '5' AFTER `subtract`,
ADD COLUMN  `tax_class_id` int(11) AFTER `order_status_id`,
ADD COLUMN  `weight` decimal(5,2) AFTER `tax_class_id`,
ADD COLUMN  `weight_class_id` int(11) AFTER `weight`,
ADD COLUMN  `length` decimal(5,2) AFTER `weight_class_id`,
ADD COLUMN  `width` decimal(5,2) AFTER  `length`,
ADD COLUMN  `height` decimal(5,2) AFTER `width`,
ADD COLUMN  `length_class_id` int(11) AFTER `height`,
ADD COLUMN  `shipping` int(1) NOT NULL DEFAULT '1' AFTER `length_class_id`,
ADD COLUMN  `ship_individually` int(1) NOT NULL DEFAULT '0' AFTER `shipping`,
ADD COLUMN  `free_shipping` int(1) NOT NULL DEFAULT '0' AFTER `ship_individually`,
ADD COLUMN  `shipping_price` decimal(15,4) NOT NULL DEFAULT '0.0000' AFTER `free_shipping`,

ADD INDEX `ac_order_products_ibfk_2_idx1` (`order_status_id` ASC);

#remove default order_status_id and add foreign key

ALTER TABLE `ac_order_products`
CHANGE COLUMN `order_status_id` `order_status_id` INT(11) NOT NULL,
ADD CONSTRAINT `ac_order_products_ibfk_2`
  FOREIGN KEY (`order_status_id`)
  REFERENCES `ac_order_statuses` (`order_status_id`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ac_order_options`
  ADD COLUMN `product_option_id` INT NOT NULL DEFAULT '0' AFTER `order_product_id`,
  ADD COLUMN `product_option_name` VARCHAR(255) NOT NULL AFTER `product_option_id`,
  ADD COLUMN `weight` decimal(5,2) AFTER `settings`,
  ADD COLUMN `weight_type` char(3) AFTER `weight`,
  ADD INDEX `ac_order_options_idx_2` (`product_option_id` ASC);

ALTER TABLE `ac_order_totals`
  ADD COLUMN `data` LONGTEXT NULL
  AFTER `value`;

ALTER TABLE `ac_categories`
ADD COLUMN `path` VARCHAR(255) NOT NULL DEFAULT '' AFTER `parent_id`;

ALTER TABLE `ac_categories`
ADD INDEX `ac_categories_trees_idx` (`path`),
ADD COLUMN `total_products_count` int(11) NOT NULL DEFAULT '0' AFTER `path`,
ADD COLUMN `active_products_count` int(11) NOT NULL DEFAULT '0' AFTER `total_products_count`,
ADD COLUMN `children_count` int(11) NOT NULL DEFAULT '0' AFTER `active_products_count`;


INSERT INTO `ac_settings` (`group`, `key`, `value`) VALUES
('general','config_google_tag_manager_id','');


ALTER TABLE `ac_products`
CHANGE COLUMN `manufacturer_id` `manufacturer_id` INT(11) NULL DEFAULT NULL ,
CHANGE COLUMN `tax_class_id` `tax_class_id` INT(11) NULL DEFAULT NULL ,
CHANGE COLUMN `weight_class_id` `weight_class_id` INT(11) NULL DEFAULT NULL ,
CHANGE COLUMN `length_class_id` `length_class_id` INT(11) NULL DEFAULT NULL ,
ADD INDEX `ac_products_idx1` (`manufacturer_id` ASC),
ADD INDEX `ac_products_idx2` (`tax_class_id` ASC),
ADD INDEX `ac_products_idx3` (`weight_class_id` ASC),
ADD INDEX `ac_products_idx4` (`length_class_id` ASC);
ALTER TABLE `ac_products`
ADD CONSTRAINT `ac_products_fk1`
  FOREIGN KEY (`manufacturer_id`)
  REFERENCES `ac_manufacturers` (`manufacturer_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE,
ADD CONSTRAINT `ac_products_fk2`
  FOREIGN KEY (`tax_class_id`)
  REFERENCES `ac_tax_classes` (`tax_class_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE,
ADD CONSTRAINT `ac_products_fk3`
  FOREIGN KEY (`weight_class_id`)
  REFERENCES `ac_weight_classes` (`weight_class_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE,
ADD CONSTRAINT `ac_products_fk4`
  FOREIGN KEY (`length_class_id`)
  REFERENCES `ac_length_classes` (`length_class_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;


ALTER TABLE `ac_product_options`
CHANGE COLUMN `product_option_id` `product_option_id` INT(11) NOT NULL ,
CHANGE COLUMN `attribute_id` `attribute_id` INT(11) NULL DEFAULT NULL ,
CHANGE COLUMN `group_id` `group_id` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `regexp_pattern` `regexp_pattern` VARCHAR(255) NULL DEFAULT '',
ADD INDEX `ac_product_options_ibfk_3_idx` (`group_id` ASC);
ALTER TABLE `ac_product_options`
ADD CONSTRAINT `ac_product_options_ibfk_2`
  FOREIGN KEY (`attribute_id`)
  REFERENCES `ac_global_attributes` (`attribute_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE,
ADD CONSTRAINT `ac_product_options_ibfk_3`
  FOREIGN KEY (`group_id`)
  REFERENCES `ac_global_attributes_groups` (`attribute_group_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE `ac_product_option_descriptions`
  CHANGE COLUMN `error_text` `error_text` VARCHAR(255) NULL
COMMENT 'translatable';



ALTER TABLE `ac_product_option_values`
  ADD INDEX `ac_product_option_values_ibfk_3_idx` (`attribute_value_id` ASC);
ALTER TABLE `ac_product_option_values`
  ADD CONSTRAINT `ac_product_option_values_ibfk_3`
FOREIGN KEY (`attribute_value_id`)
REFERENCES `ac_global_attributes_values` (`attribute_value_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE `ac_products_related`
  ADD UNIQUE INDEX `ac_product_related_unique_idx` (`product_id` ASC, `related_id` ASC),
  ADD CONSTRAINT `ac_products_related_chk` CHECK (`product_id` <> `related_id`);


DROP TABLE IF EXISTS `ac_email_templates`;
CREATE TABLE `ac_email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL,
  `text_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `language_id` int(11) NOT NULL,
  `headers` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `html_body` text COLLATE utf8_unicode_ci NOT NULL,
  `text_body` text COLLATE utf8_unicode_ci NOT NULL,
  `allowed_placeholders` text COLLATE utf8_unicode_ci NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_deleted` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_text_id_idx` (`text_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `ac_email_templates`
--
INSERT INTO `ac_email_templates` VALUES (1,1,'storefront_reset_password_link',1,'','{{store_name}} - Password reset','A password reset was requested from {{store_name}}&lt;br /&gt;\r\nTo reset your password click link below:&lt;br /&gt;\r\n{{ reset_link }}\r\n\r\n','A password reset was requested from {{store_name}} \r\nTo reset your password click link below:\r\n{{ reset_link }}','store_name, reset_link','2019-12-18 13:26:38','2019-12-19 13:28:01',NULL),(2,1,'storefront_welcome_email_activated',1,'','Welcome, {{store_name}}','&lt;html&gt;\r\n	&lt;head&gt;\r\n		&lt;meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"&gt;\r\n	&lt;/head&gt;\r\n	&lt;body&gt;\r\n		&lt;table style=\"font-family: Verdana,sans-serif; font-size: 11px; color: #374953; width: 600px;\"&gt;\r\n			&lt;tr&gt;\r\n				&lt;td class=\"align_left\"&gt;\r\n					&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n						{{#logo_uri}}\r\n							&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/logo_uri}}\r\n                                                 {{^logo_uri}}\r\n                                                       {{#logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/logo_html}}\r\n                                                 {{/logo_uri}}\r\n					&lt;/a&gt;\r\n				&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;Welcome and thank you for registering at {{ store_name }}&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;\r\n                          Your account has now been created and you can log in by using your email address and password by visiting our website or at the following URL:&lt;br/&gt;\r\n&lt;a href=\"{{ login_url }}\"&gt;{{ login_url }}&lt;/a&gt;&lt;br/&gt;\r\nOnce you logging in, you will be able to access, your wishlist, order history, printing invoices and editing your account information.\r\n	&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;\r\n					Thank you.&lt;br/&gt;\r\n                                        {{ store_name }}\r\n			 	&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n		&lt;/table&gt;\r\n	&lt;/body&gt;\r\n&lt;/html&gt;','Welcome and thank you for registering at {{ store_name }}\r\n\r\nYour account has now been created and you can log in by using your email address and password by visiting our website or at the following URL:\r\n{{ login_url }}\r\n\r\nOnce you logging in, you will be able to access, your wishlist, order history, printing invoices and editing your account information.\r\n\r\nThank you.\r\n{{ store_name }}\r\n','store_name, login_url, store_url, logo_html, logo_uri','2019-12-19 13:59:34','2019-12-20 08:58:07',NULL),(3,1,'storefront_welcome_email_approval',1,'','Welcome, {{store_name}}','&lt;html&gt;\r\n	&lt;head&gt;\r\n		&lt;meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"&gt;\r\n	&lt;/head&gt;\r\n	&lt;body&gt;\r\n		&lt;table style=\"font-family: Verdana,sans-serif; font-size: 11px; color: #374953; width: 600px;\"&gt;\r\n			&lt;tr&gt;\r\n				&lt;td class=\"align_left\"&gt;\r\n					&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n				{{#logo_uri}}\r\n							&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/logo_uri}}\r\n                                                 {{^logo_uri}}\r\n                                                       {{#logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/logo_html}}\r\n                                                 {{/logo_uri}}\r\n					&lt;/a&gt;\r\n				&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;Welcome and thank you for registering at {{ store_name }}&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;\r\n                          Your account must be approved before you can login. Once approved you can log in by using your email address and password by visiting our website or at the following URL:&lt;br/&gt;\r\n&lt;a href=\"{{ login_url }}\"&gt;{{ login_url }}&lt;/a&gt;&lt;br/&gt;\r\nOnce you logging in, you will be able to access, your wishlist, order history, printing invoices and editing your account information.\r\n		&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;\r\n					Thank you.&lt;br/&gt;\r\n                                        {{ store_name }}\r\n			 	&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n		&lt;/table&gt;\r\n	&lt;/body&gt;\r\n&lt;/html&gt;','Welcome and thank you for registering at {{ store_name }}\r\n\r\nYour account must be approved before you can login. Once approved you can log in by using your email address and password by visiting our website or at the following URL:\r\n{{ login_url }}\r\n\r\nOnce you logging in, you will be able to access, your wishlist, order history, printing invoices and editing your account information.\r\n\r\nThank you.\r\n{{ store_name }}\r\n','store_name, login_url, store_url, logo_html, logo_uri','2019-12-20 09:02:19','2019-12-20 09:08:29',NULL),(4,1,'storefront_send_activate_link',1,'','{{store_name}} - Thank you for registering','&lt;html&gt;\r\n	&lt;head&gt;\r\n		&lt;meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"&gt;\r\n	&lt;/head&gt;\r\n	&lt;body&gt;\r\n		&lt;table style=\"font-family: Verdana,sans-serif; font-size: 11px; color: #374953; width: 600px;\"&gt;\r\n			&lt;tr&gt;\r\n				&lt;td class=\"align_left\"&gt;\r\n					&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n						{{#logo_uri}}\r\n							&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/logo_uri}}\r\n                                                 {{^logo_uri}}\r\n                                                       {{#logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/logo_html}}\r\n                                                 {{/logo_uri}}\r\n					&lt;/a&gt;\r\n				&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;Welcome and thank you for registering at {{ store_name }}&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;\r\n                          Before we can activate your account one last step must be taken to complete your registration.&lt;br/&gt;\r\nYou must complete this last step to become a registered member. Please click the following link to activate your account:&lt;br/&gt;\r\n&lt;a href=\"{{ activate_url }}\"&gt;{{ activate_url  }}&lt;/a&gt;&lt;br/&gt;\r\n\r\n				&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n				&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n			&lt;tr&gt;\r\n		&lt;td&gt;\r\n					Thank you.&lt;br/&gt;\r\n                                        {{ store_name }}\r\n			 	&lt;/td&gt;\r\n			&lt;/tr&gt;\r\n		&lt;/table&gt;\r\n	&lt;/body&gt;\r\n&lt;/html&gt;','Welcome and thank you for registering at {{ store_name }}!\r\n\r\nBefore we can activate your account one last step must be taken to complete your registration.\r\nYou must complete this last step to become a registered member. Please click the following link to activate your account\r\n{{ activate_url }}\r\n\r\nThank you,\r\n{{ store_name }}','store_name, activate_url, logo_uri, logo_html, store_url','2019-12-20 10:05:55','2019-12-20 10:14:56',NULL),(5,1,'storefront_reset_password_notify',1,'','{{store_name}} - Password reset','Your password was successfully reset on {{store_name}}','Your password was successfully reset on {{store_name}}','store_name','2019-12-20 10:30:10','2019-12-20 10:30:10',NULL),(6,1,'storefront_send_login_name',1,'','{{store_name}} - Login name reminder','Login name reminder was requested from {{store_name}} &lt;br/&gt;\r\n&lt;br/&gt;\r\nYour login name is: {{ login_name }}','Login name reminder was requested from {{store_name}} \r\n\r\nYour login name is: {{ login_name }}','store_name, login_name','2019-12-20 10:37:18','2019-12-20 10:37:18',NULL),(7,1,'storefront_order_confirm',1,'','{{store_name}} - Order {{ order_number }}','&lt;html&gt;\r\n&lt;head&gt;\r\n	&lt;meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"&gt;\r\n	&lt;title&gt; {{store_name}} - Order {{ order_number }} &lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\n&lt;table style=\"font-family: Verdana,sans-serif; font-size: 11px; color: #374953; width: 600px;\"&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n					&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n						{{# logo_uri}}\r\n				&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/ logo_uri}}\r\n                                                 {{^ logo_uri}}\r\n                                                       {{# logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/ logo_html}}\r\n                                                 {{/ logo_uri}}\r\n					&lt;/a&gt;\r\n				&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;Thank you for your interest in {{store_name}} products. Your order has been received and will be processed after payment is confirmed.&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color:#FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;Order Details&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;Order ID: &lt;span\r\n					style=\"color: #069; font-weight: bold;\"&gt;{{ order_number }}&lt;/span&gt;&lt;br/&gt;\r\n			Date Ordered: {{ date_added }}&lt;br&gt;\r\n			Payment Method: &lt;strong&gt;{{ payment_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			Shipping Method: &lt;strong&gt;{{ shipping_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			&lt;br/&gt;\r\n			Email: &lt;strong&gt;{{ customer_email }}&lt;/strong&gt;&lt;br/&gt;\r\n			Telephone: &lt;strong&gt;{{ customer_telephone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{# customer_mobile_phone }}\r\n                            Telephone: &lt;strong&gt;{{ customer_mobile_phone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_mobile_phone }}\r\n		         {{# customer_fax }}\r\n                            Fax: &lt;strong&gt;{{ customer_fax }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_fax }}\r\n                         IP Address:&lt;strong&gt;{{ customer_ip }}&lt;/strong&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #FFFFFF;\"&gt;\r\n				&lt;tr style=\"background-color: #CCCCCC; text-transform: uppercase;\"&gt;\r\n					&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Shipping Address&lt;/th&gt;\r\n					&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Payment Address&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				&lt;tr&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# shipping_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n&lt;/td&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# payment_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n&lt;/td&gt;\r\n				&lt;/tr&gt;\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #000000;\"&gt;\r\n				&lt;tr style=\"background-color: #CCCCCC;\"&gt;\r\n					&lt;th style=\"width: 40%; padding: 0.3em; color: #FFFFFF;\"&gt;Product&lt;/th&gt;\r\n					&lt;th class=\"align_left\" style=\"color: #FFFFFF;\"&gt;Model&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n		    style=\"width: 10%; padding: 0.3em; color: #FFFFFF;\"&gt;Price&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 15%; padding: 0.3em; color: #FFFFFF;\"&gt;Quantity&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 20%; padding: 0.3em; color: #FFFFFF;\"&gt;Total&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				{{# products }}\r\n					&lt;tr style=\"background-color: #EEEEEE; text-align: center;\"&gt;\r\n						&lt;td class=\"align_left\" style=\"text-align: left;\"&gt;{{ name }}\r\n					{{# option }}\r\n								&lt;br/&gt;\r\n								&amp;nbsp;&amp;nbsp;- {{ name}} {{ value }} \r\n	{{/ option }}\r\n						&lt;td class=\"align_left\"&gt; {{ model }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ price }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ quantity }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ total }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                {{/ products }}\r\n                                         {{# totals }}\r\n			&lt;tr style=\"text-align: right;\"&gt;\r\n						&lt;td colspan=\"3\"&gt;&amp;nbsp;&lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; font-weight: bold; padding: 0.3em;\"&gt;{{ title }} &lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; padding: 0.3em;\"&gt; {{ text }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                         {{/ totals }}\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n {{# comment }}\r\n	&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;The comments for your order are:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt; {{ comment }} &lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n {{/ comment }}\r\n {{# invoice }}\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;To view your order click on the link below:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt;&lt;a href=\"{{ invoice }}\"&gt; {{ invoice }}&lt;/a&gt;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n{{/ invoice }}\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_center\"\r\n		    style=\"font-size: 10px; border-top: 1px solid #069; text-decoration: none; color: #374953;\"&gt;\r\n			&lt;a href=\"{{ store_url }}\"\r\n			   style=\"color: #069; font-weight: bold; text-decoration: none;\"&gt;{{ store_name }}&lt;/a&gt;\r\n{{ text_project_label }}\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n&lt;/table&gt;\r\n&lt;/body&gt;\r\n&lt;/html&gt;\r\n','Thank you for your interest in {{store_name}} products. Your order has been received and will be processed after payment is confirmed.\r\n\r\nOrder Details\r\n\r\nOrder ID: {{ order_number }}\r\nDate Ordered: {{ date_added }}\r\nPayment Method: {{ payment_method }}\r\nShipping Method: {{ shipping_method }}\r\n\r\nEmail: {{ customer_email }}\r\nTelephone: {{ customer_telephone }}\r\n{{# customer_mobile_phone }}\r\nTelephone: {{ customer_mobile_phone }}\r\n{{/ customer_mobile_phone }}\r\n{{# customer_fax }}\r\nFax: {{ customer_fax }}\r\n{{/ customer_fax }}\r\nIP Address: {{ customer_ip }}\r\n\r\n{{# shipping_data }}\r\nShipping Address:\r\n{{ firstname }} {{lastname}}\r\n{{ company }} \r\n{{ address_1 }} \r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n\r\n{{# payment_data }}\r\nPayment Address:\r\n{{ firstname }} {{lastname}} \r\n{{ company }}\r\n{{ address_1 }}\r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n\r\n\r\nProduct                                                 Model                             Price                  Quantity              Total\r\n{{# products }}\r\n					\r\n{{ name }}                                               {{ model }}                       {{ price }}            {{ quantity }}        {{ total }}\r\n   {{# option }}\r\n    - {{ name}} {{ value }} \r\n   {{/ option }}\r\n\r\n\r\n{{/ products }}\r\n{{# totals }}\r\n  {{ title }}  {{ text }}\r\n{{/ totals }}\r\n\r\n\r\n{{# comment }}\r\n	The comments for your order are:\r\n {{ comment }} \r\n{{/ comment }}\r\n\r\n{{# invoice }}\r\nTo view your order click on the link below:\r\n{{ invoice }}\r\n{{/ invoice }}\r\n\r\n{{ store_name }}\r\n{{ text_project_label }}','store_name, order_number, store_url, logo_uri, logo_html, date_added, payment_method, shipping_method, customer_email, customer_telephone, customer_mobile_phone, customer_fax, customer_ip, shipping_data, payment_data, products, totals, comment, invoice,  text_project_label, ','2019-12-23 07:23:50','2019-12-23 10:02:38',NULL),(8,1,'storefront_order_confirm_alert',1,'','{{store_name}} - Order {{ order_number }} ({{ order_total }})','&lt;html&gt;\r\n&lt;head&gt;\r\n	&lt;meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"&gt;\r\n	&lt;title&gt; {{store_name}} - Order {{ order_number }} &lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\n&lt;table style=\"font-family: Verdana,sans-serif; font-size: 11px; color: #374953; width: 600px;\"&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n				&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n						{{# logo_uri}}\r\n							&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/ logo_uri}}\r\n                                                 {{^ logo_uri}}\r\n                                                       {{# logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/ logo_html}}\r\n                                                 {{/ logo_uri}}\r\n					&lt;/a&gt;\r\n				&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;You have received an order.&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color:#FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;Order Details&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;Order ID: &lt;span\r\n					style=\"color: #069; font-weight: bold;\"&gt;{{ order_number }}&lt;/span&gt;&lt;br/&gt;\r\n			Date Ordered: {{ date_added }}&lt;br&gt;\r\n			Payment Method: &lt;strong&gt;{{ payment_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			Shipping Method: &lt;strong&gt;{{ shipping_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			&lt;br/&gt;\r\n			Email: &lt;strong&gt;{{ customer_email }}&lt;/strong&gt;&lt;br/&gt;\r\n			Telephone: &lt;strong&gt;{{ customer_telephone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{# customer_mobile_phone }}\r\n                            Telephone: &lt;strong&gt;{{ customer_mobile_phone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_mobile_phone }}\r\n		         {{# customer_fax }}\r\n                            Fax: &lt;strong&gt;{{ customer_fax }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_fax }}\r\n                         IP Address:&lt;strong&gt;{{ customer_ip }}&lt;/strong&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #FFFFFF;\"&gt;\r\n		&lt;tr style=\"background-color: #CCCCCC; text-transform: uppercase;\"&gt;\r\n					&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Shipping Address&lt;/th&gt;\r\n		&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Payment Address&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				&lt;tr&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# shipping_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n&lt;/td&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# payment_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n&lt;/td&gt;\r\n				&lt;/tr&gt;\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #000000;\"&gt;\r\n				&lt;tr style=\"background-color: #CCCCCC;\"&gt;\r\n					&lt;th style=\"width: 40%; padding: 0.3em; color: #FFFFFF;\"&gt;Product&lt;/th&gt;\r\n					&lt;th class=\"align_left\" style=\"color: #FFFFFF;\"&gt;Model&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n		    style=\"width: 10%; padding: 0.3em; color: #FFFFFF;\"&gt;Price&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 15%; padding: 0.3em; color: #FFFFFF;\"&gt;Quantity&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 20%; padding: 0.3em; color: #FFFFFF;\"&gt;Total&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				{{# products }}\r\n					&lt;tr style=\"background-color: #EEEEEE; text-align: center;\"&gt;\r\n						&lt;td class=\"align_left\" style=\"text-align: left;\"&gt;{{ name }}\r\n					{{# option }}\r\n								&lt;br/&gt;\r\n								&amp;nbsp;&amp;nbsp;- {{ name}} {{ value }} \r\n	{{/ option }}\r\n						&lt;td class=\"align_left\"&gt; {{ model }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ price }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ quantity }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ total }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                {{/ products }}\r\n                                         {{# totals }}\r\n			&lt;tr style=\"text-align: right;\"&gt;\r\n						&lt;td colspan=\"3\"&gt;&amp;nbsp;&lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; font-weight: bold; padding: 0.3em;\"&gt;{{ title }} &lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; padding: 0.3em;\"&gt; {{ text }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                         {{/ totals }}\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n {{# comment }}\r\n	&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;The comments for your order are:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt; {{ comment }} &lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n {{/ comment }}\r\n {{# invoice }}\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;To view your order click on the link below:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt;&lt;a href=\"{{ invoice }}\"&gt; {{ invoice }}&lt;/a&gt;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n{{/ invoice }}\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_center\"\r\n		    style=\"font-size: 10px; border-top: 1px solid #069; text-decoration: none; color: #374953;\"&gt;\r\n			&lt;a href=\"{{ store_url }}\"\r\n			   style=\"color: #069; font-weight: bold; text-decoration: none;\"&gt;{{ store_name }}&lt;/a&gt;\r\n{{ text_project_label }}\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n&lt;/table&gt;\r\n&lt;/body&gt;\r\n&lt;/html&gt;\r\n','You have received an order.\r\n\r\nOrder Details\r\n\r\nOrder ID: {{ order_number }}\r\nDate Ordered: {{ date_added }}\r\nPayment Method: {{ payment_method }}\r\nShipping Method: {{ shipping_method }}\r\n\r\nEmail: {{ customer_email }}\r\nTelephone: {{ customer_telephone }}\r\n{{# customer_mobile_phone }}\r\nTelephone: {{ customer_mobile_phone }}\r\n{{/ customer_mobile_phone }}\r\n{{# customer_fax }}\r\nFax: {{ customer_fax }}\r\n{{/ customer_fax }}\r\nIP Address: {{ customer_ip }}\r\n\r\n{{# shipping_data }}\r\nShipping Address:\r\n{{ firstname }} {{lastname}}\r\n{{ company }} \r\n{{ address_1 }} \r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n\r\n{{# payment_data }}\r\nPayment Address:\r\n{{ firstname }} {{lastname}} \r\n{{ company }}\r\n{{ address_1 }}\r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n\r\n\r\nProduct                                                 Model                             Price                  Quantity              Total\r\n{{# products }}\r\n					\r\n{{ name }}                                               {{ model }}                       {{ price }}            {{ quantity }}        {{ total }}\r\n   {{# option }}\r\n    - {{ name}} {{ value }} \r\n   {{/ option }}\r\n\r\n\r\n{{/ products }}\r\n{{# totals }}\r\n  {{ title }}  {{ text }}\r\n{{/ totals }}\r\n\r\n\r\n{{# comment }}\r\n	The comments for your order are:\r\n {{ comment }} \r\n{{/ comment }}\r\n\r\n{{# invoice }}\r\nTo view your order click on the link below:\r\n{{ invoice }}\r\n{{/ invoice }}\r\n\r\n{{ store_name }}\r\n{{ text_project_label }}','store_name, order_number, store_url, logo_uri, logo_html, date_added, payment_method, shipping_method, customer_email, customer_telephone, customer_mobile_phone, customer_fax, customer_ip, shipping_data, payment_data, products, totals, comment, invoice,  text_project_label, order_total','2019-12-23 10:33:00','2019-12-23 10:38:37',NULL),(9,1,'admin_order_update_product_statuses',1,'','{{ store_name }} - Order Update {{ order_id }}','&lt;html&gt;\r\n&lt;head&gt;\r\n	&lt;meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"&gt;\r\n	&lt;title&gt; {{store_name}} - Order {{ order_number }} &lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\n&lt;table style=\"font-family: Verdana,sans-serif; font-size: 11px; color: #374953; width: 600px;\"&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n					&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n				{{# logo_uri}}\r\n							&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/ logo_uri}}\r\n                                                 {{^ logo_uri}}\r\n                                                       {{# logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/ logo_html}}\r\n                                                 {{/ logo_uri}}\r\n					&lt;/a&gt;\r\n				&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;Thank you for your interest in {{store_name}} products. Your order has been received and will be processed after payment is confirmed.&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color:#FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;Order Details&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;Order ID: &lt;span\r\n					style=\"color: #069; font-weight: bold;\"&gt;{{ order_number }}&lt;/span&gt;&lt;br/&gt;\r\n			Date Ordered: {{ date_added }}&lt;br&gt;\r\n			Payment Method: &lt;strong&gt;{{ payment_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			Shipping Method: &lt;strong&gt;{{ shipping_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			&lt;br/&gt;\r\n			Email: &lt;strong&gt;{{ customer_email }}&lt;/strong&gt;&lt;br/&gt;\r\n			Telephone: &lt;strong&gt;{{ customer_telephone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{# customer_mobile_phone }}\r\n                            Telephone: &lt;strong&gt;{{ customer_mobile_phone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_mobile_phone }}\r\n		         {{# customer_fax }}\r\n                            Fax: &lt;strong&gt;{{ customer_fax }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_fax }}\r\n                         IP Address:&lt;strong&gt;{{ customer_ip }}&lt;/strong&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #FFFFFF;\"&gt;\r\n		&lt;tr style=\"background-color: #CCCCCC; text-transform: uppercase;\"&gt;\r\n					&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Shipping Address&lt;/th&gt;\r\n		&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Payment Address&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				&lt;tr&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# shipping_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n&lt;/td&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# payment_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n&lt;/td&gt;\r\n				&lt;/tr&gt;\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #000000;\"&gt;\r\n				&lt;tr style=\"background-color: #CCCCCC;\"&gt;\r\n					&lt;th style=\"width: 40%; padding: 0.3em; color: #FFFFFF;\"&gt;Product&lt;/th&gt;\r\n					&lt;th class=\"align_left\" style=\"color: #FFFFFF;\"&gt;Model&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n		    style=\"width: 10%; padding: 0.3em; color: #FFFFFF;\"&gt;Price&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 15%; padding: 0.3em; color: #FFFFFF;\"&gt;Quantity&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 20%; padding: 0.3em; color: #FFFFFF;\"&gt;Total&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				{{# products }}\r\n					&lt;tr style=\"background-color: #EEEEEE; text-align: center;\"&gt;\r\n						&lt;td class=\"align_left\" style=\"text-align: left;\"&gt;{{ name }}\r\n					{{# option }}\r\n								&lt;br/&gt;\r\n								&amp;nbsp;&amp;nbsp;- {{ name}} {{ value }} \r\n	{{/ option }}\r\n						&lt;td class=\"align_left\"&gt; {{ model }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ price }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ quantity }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ total }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                {{/ products }}\r\n                                         {{# totals }}\r\n			&lt;tr style=\"text-align: right;\"&gt;\r\n						&lt;td colspan=\"3\"&gt;&amp;nbsp;&lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; font-weight: bold; padding: 0.3em;\"&gt;{{ title }} &lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; padding: 0.3em;\"&gt; {{ text }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                         {{/ totals }}\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n {{# comment }}\r\n	&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;The comments for your order are:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt; {{ comment }} &lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n {{/ comment }}\r\n {{# invoice }}\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;To view your order click on the link below:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt;&lt;a href=\"{{ invoice }}\"&gt; {{ invoice }}&lt;/a&gt;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n{{/ invoice }}\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_center\"\r\n		    style=\"font-size: 10px; border-top: 1px solid #069; text-decoration: none; color: #374953;\"&gt;\r\n			&lt;a href=\"{{ store_url }}\"\r\n			   style=\"color: #069; font-weight: bold; text-decoration: none;\"&gt;{{ store_name }}&lt;/a&gt;\r\n{{ text_project_label }}\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n&lt;/table&gt;\r\n&lt;/body&gt;\r\n&lt;/html&gt;\r\n','Thank you for your interest in {{store_name}} products. Your order has been received and will be processed after payment is confirmed.\r\n\r\nOrder Details\r\n\r\nOrder ID: {{ order_number }}\r\nDate Ordered: {{ date_added }}\r\nPayment Method: {{ payment_method }}\r\nShipping Method: {{ shipping_method }}\r\n\r\nEmail: {{ customer_email }}\r\nTelephone: {{ customer_telephone }}\r\n{{# customer_mobile_phone }}\r\nTelephone: {{ customer_mobile_phone }}\r\n{{/ customer_mobile_phone }}\r\n{{# customer_fax }}\r\nFax: {{ customer_fax }}\r\n{{/ customer_fax }}\r\nIP Address: {{ customer_ip }}\r\n\r\n{{# shipping_data }}\r\nShipping Address:\r\n{{ firstname }} {{lastname}}\r\n{{ company }} \r\n{{ address_1 }} \r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n\r\n{{# payment_data }}\r\nPayment Address:\r\n{{ firstname }} {{lastname}} \r\n{{ company }}\r\n{{ address_1 }}\r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n\r\n\r\nProduct                                                 Model                             Price                  Quantity              Total\r\n{{# products }}\r\n					\r\n{{ name }}                                               {{ model }}                       {{ price }}            {{ quantity }}        {{ total }}\r\n   {{# option }}\r\n    - {{ name}} {{ value }} \r\n   {{/ option }}\r\n\r\n\r\n{{/ products }}\r\n{{# totals }}\r\n  {{ title }}  {{ text }}\r\n{{/ totals }}\r\n\r\n\r\n{{# comment }}\r\n	The comments for your order are:\r\n {{ comment }} \r\n{{/ comment }}\r\n\r\n{{# invoice }}\r\nTo view your order click on the link below:\r\n{{ invoice }}\r\n{{/ invoice }}\r\n\r\n{{ store_name }}\r\n{{ text_project_label }}','store_name, order_number, store_url, logo_uri, logo_html, date_added, payment_method, shipping_method, customer_email, customer_telephone, customer_mobile_phone, customer_fax, customer_ip, shipping_data, payment_data, products, totals, comment, invoice,  text_project_label, ','2019-12-23 13:11:55','2019-12-23 13:12:48',NULL),(10,1,'admin_order_update_product_statuses_alert',1,'','{{ store_name }} - Order Update {{ order_id }}','&lt;html&gt;\r\n&lt;head&gt;\r\n	&lt;meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"&gt;\r\n	&lt;title&gt; {{store_name}} - Order {{ order_number }} &lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\n&lt;table style=\"font-family: Verdana,sans-serif; font-size: 11px; color: #374953; width: 600px;\"&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n				&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n						{{# logo_uri}}\r\n							&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/ logo_uri}}\r\n                                                 {{^ logo_uri}}\r\n                                                       {{# logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/ logo_html}}\r\n                                                 {{/ logo_uri}}\r\n					&lt;/a&gt;\r\n				&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;You have received an order.&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color:#FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;Order Details&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;Order ID: &lt;span\r\n					style=\"color: #069; font-weight: bold;\"&gt;{{ order_number }}&lt;/span&gt;&lt;br/&gt;\r\n			Date Ordered: {{ date_added }}&lt;br&gt;\r\n			Payment Method: &lt;strong&gt;{{ payment_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			Shipping Method: &lt;strong&gt;{{ shipping_method }}&lt;/strong&gt;&lt;br/&gt;\r\n			&lt;br/&gt;\r\n			Email: &lt;strong&gt;{{ customer_email }}&lt;/strong&gt;&lt;br/&gt;\r\n			Telephone: &lt;strong&gt;{{ customer_telephone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{# customer_mobile_phone }}\r\n                            Telephone: &lt;strong&gt;{{ customer_mobile_phone }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_mobile_phone }}\r\n		         {{# customer_fax }}\r\n                            Fax: &lt;strong&gt;{{ customer_fax }}&lt;/strong&gt;&lt;br/&gt;\r\n                         {{/ customer_fax }}\r\n                         IP Address:&lt;strong&gt;{{ customer_ip }}&lt;/strong&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #FFFFFF;\"&gt;\r\n		&lt;tr style=\"background-color: #CCCCCC; text-transform: uppercase;\"&gt;\r\n					&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Shipping Address&lt;/th&gt;\r\n		&lt;th style=\"text-align: left; padding: 0.3em;\"&gt;Payment Address&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				&lt;tr&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# shipping_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n&lt;/td&gt;\r\n					&lt;td style=\"padding: 0.3em; background-color: #EEEEEE; color: #000;\"&gt;\r\n{{# payment_data }}\r\n{{ firstname }} {{lastname}} &lt;br/&gt;\r\n{{# company }}\r\n{{ company }} &lt;br/&gt; \r\n{{/ company }}\r\n{{# address_1 }}\r\n{{ address_1 }} &lt;br/&gt; \r\n{{/ address_1 }}\r\n{{# address_2 }}\r\n{{ address_2 }} &lt;br/&gt; \r\n{{/ address_2 }}\r\n{{ city }} {{ postcode }} &lt;br/&gt; \r\n{{# zone }}\r\n{{ zone }} &lt;br/&gt; \r\n{{/ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n&lt;/td&gt;\r\n				&lt;/tr&gt;\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"&gt;\r\n			&lt;table style=\"width: 100%; font-family: Verdana,sans-serif; font-size: 11px; color: #000000;\"&gt;\r\n				&lt;tr style=\"background-color: #CCCCCC;\"&gt;\r\n					&lt;th style=\"width: 40%; padding: 0.3em; color: #FFFFFF;\"&gt;Product&lt;/th&gt;\r\n					&lt;th class=\"align_left\" style=\"color: #FFFFFF;\"&gt;Model&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n		    style=\"width: 10%; padding: 0.3em; color: #FFFFFF;\"&gt;Price&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 15%; padding: 0.3em; color: #FFFFFF;\"&gt;Quantity&lt;/th&gt;\r\n					&lt;th class=\"align_right\"\r\n					    style=\"width: 20%; padding: 0.3em; color: #FFFFFF;\"&gt;Total&lt;/th&gt;\r\n				&lt;/tr&gt;\r\n				{{# products }}\r\n					&lt;tr style=\"background-color: #EEEEEE; text-align: center;\"&gt;\r\n						&lt;td class=\"align_left\" style=\"text-align: left;\"&gt;{{ name }}\r\n					{{# option }}\r\n								&lt;br/&gt;\r\n								&amp;nbsp;&amp;nbsp;- {{ name}} {{ value }} \r\n	{{/ option }}\r\n						&lt;td class=\"align_left\"&gt; {{ model }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ price }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ quantity }} &lt;/td&gt;\r\n						&lt;td class=\"align_right\"&gt; {{ total }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                {{/ products }}\r\n                                         {{# totals }}\r\n			&lt;tr style=\"text-align: right;\"&gt;\r\n						&lt;td colspan=\"3\"&gt;&amp;nbsp;&lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; font-weight: bold; padding: 0.3em;\"&gt;{{ title }} &lt;/td&gt;\r\n						&lt;td style=\"background-color: #EEEEEE; padding: 0.3em;\"&gt; {{ text }}&lt;/td&gt;\r\n					&lt;/tr&gt;\r\n                                         {{/ totals }}\r\n			&lt;/table&gt;\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_left\"\r\n		    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n	&lt;tr&gt;\r\n		&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n {{# comment }}\r\n	&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;The comments for your order are:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt; {{ comment }} &lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n {{/ comment }}\r\n {{# invoice }}\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"\r\n			    style=\"background-color: #069; color: #FFF; font-size: 12px; font-weight: bold; padding: 0.5em 1em;\"&gt;To view your order click on the link below:&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td class=\"align_left\"&gt;&lt;a href=\"{{ invoice }}\"&gt; {{ invoice }}&lt;/a&gt;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n		&lt;tr&gt;\r\n			&lt;td&gt;&amp;nbsp;&lt;/td&gt;\r\n		&lt;/tr&gt;\r\n{{/ invoice }}\r\n	&lt;tr&gt;\r\n		&lt;td class=\"align_center\"\r\n		    style=\"font-size: 10px; border-top: 1px solid #069; text-decoration: none; color: #374953;\"&gt;\r\n			&lt;a href=\"{{ store_url }}\"\r\n			   style=\"color: #069; font-weight: bold; text-decoration: none;\"&gt;{{ store_name }}&lt;/a&gt;\r\n{{ text_project_label }}\r\n		&lt;/td&gt;\r\n	&lt;/tr&gt;\r\n&lt;/table&gt;\r\n&lt;/body&gt;\r\n&lt;/html&gt;\r\n','You have received an order.\r\n\r\nOrder Details\r\n\r\nOrder ID: {{ order_number }}\r\nDate Ordered: {{ date_added }}\r\nPayment Method: {{ payment_method }}\r\nShipping Method: {{ shipping_method }}\r\n\r\nEmail: {{ customer_email }}\r\nTelephone: {{ customer_telephone }}\r\n{{# customer_mobile_phone }}\r\nTelephone: {{ customer_mobile_phone }}\r\n{{/ customer_mobile_phone }}\r\n{{# customer_fax }}\r\nFax: {{ customer_fax }}\r\n{{/ customer_fax }}\r\nIP Address: {{ customer_ip }}\r\n\r\n{{# shipping_data }}\r\nShipping Address:\r\n{{ firstname }} {{lastname}}\r\n{{ company }} \r\n{{ address_1 }} \r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ shipping_data }}\r\n\r\n{{# payment_data }}\r\nPayment Address:\r\n{{ firstname }} {{lastname}} \r\n{{ company }}\r\n{{ address_1 }}\r\n{{ address_2 }}\r\n{{ city }} {{ postcode }}\r\n{{ zone }}\r\n{{ country }}\r\n{{/ payment_data }}\r\n\r\n\r\nProduct                                                 Model                             Price                  Quantity              Total\r\n{{# products }}\r\n					\r\n{{ name }}                                               {{ model }}                       {{ price }}            {{ quantity }}        {{ total }}\r\n   {{# option }}\r\n    - {{ name}} {{ value }} \r\n   {{/ option }}\r\n\r\n\r\n{{/ products }}\r\n{{# totals }}\r\n  {{ title }}  {{ text }}\r\n{{/ totals }}\r\n\r\n\r\n{{# comment }}\r\n	The comments for your order are:\r\n {{ comment }} \r\n{{/ comment }}\r\n\r\n{{# invoice }}\r\nTo view your order click on the link below:\r\n{{ invoice }}\r\n{{/ invoice }}\r\n\r\n{{ store_name }}\r\n{{ text_project_label }}','store_name, order_number, store_url, logo_uri, logo_html, date_added, payment_method, shipping_method, customer_email, customer_telephone, customer_mobile_phone, customer_fax, customer_ip, shipping_data, payment_data, products, totals, comment, invoice,  text_project_label, order_total','2019-12-23 13:20:39','2019-12-23 13:20:39',NULL),(11,1,'admin_order_status_notify',1,'','{{ store_name }} - Order Update {{ order_id }}','Order:  {{ order_id }} &lt;br/&gt;\r\nDate added: {{ date_added }} &lt;br/&gt;\r\nOrder status: {{ order_status_name }} &lt;br/&gt;\r\nInvoice: {{ invoice }}&lt;br/&gt;\r\n&lt;br/&gt;\r\n\r\nComment:&lt;br/&gt;\r\n{{ comment }}&lt;br/&gt;\r\n\r\n&lt;br/&gt;\r\n\r\nPlease reply to this email if you have any questions.\r\n','Order:  {{ order_id }} \r\nDate added: {{ date_added }} \r\nOrder status: {{ order_status_name }} \r\nInvoice: {{ invoice }}\r\n\r\nComment:\r\n{{ comment }}\r\n\r\nPlease reply to this email if you have any questions.\r\n','store_name, order_id, date_added, order_status_name, invoice, comment','2019-12-23 13:50:03','2019-12-23 13:50:03',NULL),(12,1,'admin_new_transaction_notify',1,'','New transaction has been posted to your {{store_name}} account','New transaction has been posted to your {{ store_name }} account in the amount of {{ amount }}. For more details, login to {{ store_name }} account','New transaction has been posted to your {{ store_name }} account in the amount of {{ amount }}. For more details, login to {{ store_name }} account','store_name, amount','2019-12-23 13:55:42','2019-12-23 13:57:13',NULL),(13,1,'admin_approval_email',1,'','{{ store_name}} - Your Account has been activated!','&lt;a href=\"{{ store_url }}\" title=\"{{ store_name }}\"&gt;\r\n						{{# logo_uri}}\r\n							&lt;img src=\"{{ logo_uri }}\" alt=\"{{store_name}}\" style=\"border: none;\"&gt;\r\n                                                 {{/ logo_uri}}\r\n                                                 {{^ logo_uri}}\r\n                                                       {{# logo_html}}\r\n                                                        {{logo_html}}\r\n                                                       {{/ logo_html}}\r\n                                                 {{/ logo_uri}}\r\n					&lt;/a&gt;\r\n\r\nWelcome and thank you for registering at {{ store_name }} &lt;br/&gt;\r\nYour account has now been created and you can log in by using your email address and password by visiting our website or at the following URL: &lt;br/&gt;\r\n{{ store_url }}&lt;br/&gt;\r\n&lt;br/&gt;\r\n\r\nOnce you logging in, you will be able to access, your wishlist, order history, printing invoices and editing your account information.&lt;br/&gt;\r\n&lt;br/&gt;\r\nThank you,&lt;br/&gt;\r\n {{ store_name}} &lt;br/&gt;\r\n\r\n{{ text_project_label }}','Welcome and thank you for registering at {{ store_name }} \r\nYour account has now been created and you can log in by using your email address and password by visiting our website or at the following URL: \r\n{{ store_url }}\r\n\r\nOnce you logging in, you will be able to access, your wishlist, order history, printing invoices and editing your account information.\r\n\r\nThank you,\r\n {{ store_name}} \r\n\r\n{{text_project_label}}','store_name, store_url, text_project_label, logo_uri, logo_html','2019-12-23 14:17:58','2019-12-23 14:20:29',NULL);


#Admin Menu Icons
INSERT INTO `ac_resource_library` ( `resource_id`, `type_id`, `date_added`)
VALUES ( 281, 1, NOW() );

INSERT INTO `ac_resource_descriptions`
(`resource_id`, `language_id`, `name`, `title`, `description`, `resource_path`, `resource_code`, `date_added`)
VALUES
  ( 281,1,'Email templates', '', '', '', '<i class="fa fa-mail-bulk"></i>&nbsp;', NOW() );

--
-- SUBMENU DESIGN
-- ITEM_ID
INSERT INTO `ac_dataset_values` (`dataset_column_id`, `value_varchar`,`row_id`)
VALUES  (10,'email_templates',137);
-- ITEM_TEXT
INSERT INTO `ac_dataset_values` (`dataset_column_id`, `value_varchar`,`row_id`)
VALUES  (11,'email_templates',137);
-- ITEM_URL
INSERT INTO `ac_dataset_values` (`dataset_column_id`, `value_varchar`,`row_id`)
VALUES  (12,'design/email_templates',137);
-- PARENT_ID
INSERT INTO `ac_dataset_values` (`dataset_column_id`, `value_varchar`,`row_id`)
VALUES
    (13, 'design', 137);
-- SORT_ORDER
INSERT INTO `ac_dataset_values` (`dataset_column_id`, `value_integer`, `row_id`)
VALUES
    (14, 8, 137);
-- ITEM_TYPE
INSERT INTO `ac_dataset_values` (`dataset_column_id`, `value_varchar`, `row_id`)
VALUES
    (15, 'extension', 136);
-- ITEM_RL_ID
INSERT INTO `ac_dataset_values` (`dataset_column_id`, `value_varchar`, `row_id`)
VALUES
    (40, '281', 137);


##TODO: update datasources of listing blocks (see $this->data_sources = [] in the core/lib/listing.php. (use camelcase array keys!!!))