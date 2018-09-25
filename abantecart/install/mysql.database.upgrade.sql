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
ALTER TABLE `ac_products_featured` ENGINE=INNODB;
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
ALTER TABLE `ac_product_filters` ENGINE=INNODB;
ALTER TABLE `ac_product_filter_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_product_filter_ranges` ENGINE=INNODB;
ALTER TABLE `ac_product_filter_ranges_descriptions` ENGINE=INNODB;
ALTER TABLE `ac_extension_dependencies` ENGINE=INNODB;
ALTER TABLE `ac_encryption_keys` ENGINE=INNODB;
ALTER TABLE `ac_tasks` ENGINE=INNODB;
ALTER TABLE `ac_task_details` ENGINE=INNODB;
ALTER TABLE `ac_task_steps` ENGINE=INNODB;

UPDATE `ac_orders` SET `customer_id` = NULL WHERE `customer_id` = 0;
ALTER TABLE `ac_orders` ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE SET NULL;

ALTER TABLE `ac_orders` CHANGE COLUMN `coupon_id` `coupon_id` int(11) DEFAULT NULL;
UPDATE `ac_orders` SET `coupon_id` = NULL WHERE `coupon_id` = 0;

ALTER TABLE `ac_tax_rates` CHANGE COLUMN `zone_id` `zone_id` int(11) DEFAULT NULL;
UPDATE `ac_tax_rates` SET `zone_id` = NULL WHERE `zone_id` = 0;

ALTER TABLE `ac_language_definitions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_customers`
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`);
ALTER TABLE `ac_country_descriptions`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_country_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zones`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`);
ALTER TABLE `ac_zone_descriptions`
  ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zone_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zones_to_locations`
  ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zones_to_locations`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_zones_to_locations`
  ADD FOREIGN KEY (`location_id`) REFERENCES `ac_locations`(`location_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_addresses`
  ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_addresses`
  ADD FOREIGN KEY (`country_id`) REFERENCES `ac_countries`(`country_id`);
ALTER TABLE `ac_addresses`
  ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`);

ALTER TABLE `ac_category_descriptions`
  ADD FOREIGN KEY (`category_id`) REFERENCES `ac_categories`(`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_category_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_categories_to_stores`
  ADD FOREIGN KEY (`category_id`) REFERENCES `ac_categories`(`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_categories_to_stores`
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
  ADD FOREIGN KEY (`manufacturer_id`) REFERENCES `ac_manufacturers`(`manufacturer_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_manufacturers_to_stores`
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
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`);
ALTER TABLE `ac_order_products`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`);

ALTER TABLE `ac_order_downloads`
  ADD FOREIGN KEY (`download_id`) REFERENCES `ac_downloads`(`download_id`);
ALTER TABLE `ac_order_downloads`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`);
ALTER TABLE `ac_order_downloads`
  ADD FOREIGN KEY (`order_product_id`) REFERENCES `ac_order_products`(`order_product_id`);

ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`order_download_id`) REFERENCES `ac_order_downloads`(`order_download_id`);
ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`download_id`) REFERENCES `ac_downloads`(`download_id`);
ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`);
ALTER TABLE `ac_order_downloads_history`
  ADD FOREIGN KEY (`order_product_id`) REFERENCES `ac_order_products`(`order_product_id`);

ALTER TABLE `ac_order_data`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`);
ALTER TABLE `ac_order_data`
  ADD FOREIGN KEY (`type_id`) REFERENCES `ac_order_data_types`(`type_id`);
ALTER TABLE `ac_order_data_types`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`)  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_order_statuses`
  ADD FOREIGN KEY (`order_status_id`) REFERENCES `ac_order_status_ids`(`order_status_id`);
ALTER TABLE `ac_order_statuses`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_order_history`
  ADD FOREIGN KEY (`order_status_id`) REFERENCES `ac_order_status_ids`(`order_status_id`);

ALTER TABLE `ac_order_totals`
  ADD FOREIGN KEY (`order_id`) REFERENCES `ac_orders`(`order_id`);

ALTER TABLE `ac_product_descriptions`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;;
ALTER TABLE `ac_product_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_discounts`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_products_featured`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_options`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_option_descriptions`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_product_option_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_product_option_descriptions`
  ADD FOREIGN KEY (`product_option_id`) REFERENCES `ac_product_options`(`product_option_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_option_values`
  ADD FOREIGN KEY (`product_option_id`) REFERENCES `ac_product_options`(`product_option_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_product_option_values`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_option_value_descriptions`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_product_option_value_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_products_related`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_product_specials`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_product_tags`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_product_tags`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_products_to_categories`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_products_to_categories`
  ADD FOREIGN KEY (`category_id`) REFERENCES `ac_categories`(`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_products_to_downloads`
  ADD FOREIGN KEY (`product_id`) REFERENCES `ac_products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_products_to_downloads`
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
  ADD FOREIGN KEY (`zone_id`) REFERENCES `ac_zones`(`zone_id`) ON DELETE SET NULL;

ALTER TABLE `ac_tax_rate_descriptions`
  ADD FOREIGN KEY (`tax_rate_id`) REFERENCES `ac_tax_rates`(`tax_rate_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_tax_rate_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_url_aliases`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_users`
  ADD FOREIGN KEY (`user_group_id`) REFERENCES `ac_user_groups`(`user_group_id`);

ALTER TABLE `ac_user_notifications`
  ADD FOREIGN KEY (`user_id`) REFERENCES `ac_users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_user_notifications`
  ADD FOREIGN KEY (`store_id`) REFERENCES `ac_stores`(`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_customer_notifications`
  ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_weight_class_descriptions`
  ADD FOREIGN KEY (`weight_class_id`) REFERENCES `ac_weight_classes`(`weight_class_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_weight_class_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_page_descriptions`
  ADD FOREIGN KEY (`page_id`) REFERENCES `ac_pages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_page_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_content_descriptions`
  ADD FOREIGN KEY (`content_id`) REFERENCES `ac_contents`(`content_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_content_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_contents_to_stores`
  ADD FOREIGN KEY (`content_id`) REFERENCES `ac_contents`(`content_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_contents_to_stores`
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
  ADD FOREIGN KEY (`layout_id`) REFERENCES `ac_layouts`(`layout_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_pages_layouts`
  ADD FOREIGN KEY (`page_id`) REFERENCES `ac_pages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ac_pages_forms`
  ADD FOREIGN KEY (`form_id`) REFERENCES `ac_forms`(`form_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_pages_forms`
  ADD FOREIGN KEY (`page_id`) REFERENCES `ac_pages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_form_descriptions`
  ADD FOREIGN KEY (`form_id`) REFERENCES `ac_forms`(`form_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_form_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
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
ALTER TABLE `ac_resource_descriptions`
  ADD FOREIGN KEY (`resource_id`) REFERENCES `ac_resource_library`(`resource_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_resource_descriptions`
  ADD FOREIGN KEY (`language_id`) REFERENCES `ac_languages`(`language_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `ac_resource_map`
  ADD FOREIGN KEY (`resource_id`) REFERENCES `ac_resource_library`(`resource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
    `job_id` int(11) NOT NULL AUTO_INCREMENT,
    `job_name` varchar(255) NOT NULL,
    `status` int(11) DEFAULT '0' COMMENT '0 - disabled, 1 - ready, 2 - running, 3 - failed, 4 - scheduled, 5 - completed',
    `configuration` longtext COMMENT 'configuration for job-class',
    `start_time` datetime DEFAULT NULL,
    `last_time_run` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `last_result` int(11) NOT NULL DEFAULT '0' COMMENT '1 - success, 0 - failed',
    `actor_type` int(11) DEFAULT NULL COMMENT '0 - System user, 1 - Admin user, 2 - Customer',
    `actor_id` int(11) DEFAULT 0,
    `actor_name` varchar(128) DEFAULT '',
    `date_added` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`job_id`, `job_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ac_customers`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `last_login` `last_login` timestamp NULL DEFAULT NULL;

ALTER TABLE `ac_users`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `last_login` `last_login` timestamp NULL DEFAULT NULL;

ALTER TABLE `ac_banners`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_banner_descriptions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_ant_messages`
  CHANGE COLUMN `start_date` `start_date` timestamp NULL default NULL,
  CHANGE COLUMN `end_date` `end_date` timestamp NULL default NULL;

ALTER TABLE `ac_extensions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `date_installed` `date_installed` timestamp NULL default NULL;

ALTER TABLE `ac_tasks`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `last_time_run` `date_installed` timestamp NULL default NULL;

ALTER TABLE `ac_task_details`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_task_steps`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `last_time_run` `date_installed` timestamp NULL default NULL;

ALTER TABLE `ac_custom_lists`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_descriptions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_templates`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_layouts`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_block_layouts`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_messages`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_resource_library`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_resource_descriptions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_resource_map`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_global_attributes_type_descriptions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_custom_blocks`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_blocks`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_content_descriptions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_page_descriptions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_pages`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_weight_classes`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_customer_notifications`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_user_notifications`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_user_groups`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_settings`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_reviews`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_product_specials`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_product_discounts`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_history`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_data`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_data_types`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_order_downloads`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_products`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_tax_rates`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_tax_classes`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_orders`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_length_classes`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_downloads`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_online_customers`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_coupons`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_categories`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_zones_to_locations`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_locations`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_language_definitions`
  CHANGE COLUMN `date_added` `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `ac_products` CHANGE COLUMN `date_available` `date_available` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

DROP TABLE IF EXISTS `ac_customer_notes`;
CREATE TABLE `ac_customer_notes` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ac_customer_notes`
  ADD FOREIGN KEY (`user_id`) REFERENCES `ac_users`(`user_id`);
ALTER TABLE `ac_customer_notes`
  ADD FOREIGN KEY (`customer_id`) REFERENCES `ac_customers`(`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE `ac_customer_communications` (
  `communication_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`communication_id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ac_customer_communications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `ac_customers` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

