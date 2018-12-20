<?php

use Phinx\Migration\AbstractMigration;

class DatetimeColumnsChanges extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     *
     *
     * //Suggestion: get all foreign keys of database
     * SELECT tb1.CONSTRAINT_NAME, tb1.TABLE_NAME, tb1.COLUMN_NAME, tb1.REFERENCED_TABLE_NAME,
        tb1.REFERENCED_COLUMN_NAME, tb2.MATCH_OPTION, tb2.UPDATE_RULE, tb2.DELETE_RULE
     FROM information_schema.`KEY_COLUMN_USAGE` AS tb1
     INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS AS tb2
     	ON tb1.CONSTRAINT_NAME = tb2.CONSTRAINT_NAME
     WHERE tb2.CONSTRAINT_SCHEMA = '******' AND table_schema =  '*****' AND referenced_column_name IS NOT NULL
     *
     *
     */

    public function up()
    {
        $db_name = $this->adapter->getOption('name');
        // create audit log tables
        $this->execute("SET SQL_MODE = '';");
        $prefix = $this->getAdapter()->getOption('table_prefix');

        $rows = $this->fetchAll(
            "SELECT TABLE_NAME, COLUMN_NAME 
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA='".$db_name."' 
               AND TABLE_NAME LIKE '".$prefix."%' 
               AND COLUMN_NAME IN ('date_added', 'date_modified');"
        );
        foreach($rows as $row){
            $update = "
                ALTER TABLE {$row['TABLE_NAME']}
                MODIFY COLUMN `".$row['COLUMN_NAME']."` timestamp NULL DEFAULT CURRENT_TIMESTAMP ";
            if($row['COLUMN_NAME'] == 'date_modified'){
                $update .= " ON UPDATE CURRENT_TIMESTAMP;";
            }
            $this->execute($update);
            $update = "UPDATE ".$row['TABLE_NAME']." 
            SET `".$row['COLUMN_NAME']."` = NULL 
            WHERE `".$row['COLUMN_NAME']."` = '0000-00-00 00:00:00';";
            $this->execute($update);
        }

        $update = "ALTER TABLE ".$prefix."ant_messages
                   MODIFY COLUMN `viewed_date` timestamp NULL;";
        $this->execute($update);
        $update = "UPDATE ".$prefix."ant_messages 
                    SET `viewed_date` = NULL 
                    WHERE `viewed_date` = '0000-00-00 00:00:00';";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_discounts`
                   MODIFY COLUMN `date_start` date NULL;";
        $this->execute($update);
        $update = "UPDATE ".$prefix."product_discounts 
                    SET `date_start` = NULL 
                    WHERE `date_start` = '0000-00-00';";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_discounts`
                   MODIFY COLUMN `date_end` date NULL;";
        $this->execute($update);
        $update = "UPDATE ".$prefix."product_discounts 
                    SET `date_end` = NULL 
                    WHERE `date_end` = '0000-00-00';";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_specials`
                   MODIFY COLUMN `date_start` date NULL;";
        $this->execute($update);
        $update = "UPDATE ".$prefix."product_specials 
                    SET `date_start` = NULL 
                    WHERE `date_start` = '0000-00-00';";
        $this->execute($update);
        $update = "ALTER TABLE `".$prefix."product_specials`
                   MODIFY COLUMN `date_end` date NULL;";
        $this->execute($update);
        $update = "UPDATE ".$prefix."product_specials 
                    SET `date_end` = NULL 
                    WHERE `date_end` = '0000-00-00';";
        $this->execute($update);

        $update = "UPDATE ".$prefix."order_downloads 
                    SET `expire_date` = NULL 
                    WHERE `expire_date` = '0000-00-00';";
        $this->execute($update);

        // other changes
        $update = "ALTER TABLE `".$prefix."customers`
                   CHANGE COLUMN `customer_group_id` `customer_group_id` INT(11) NULL;";
        $this->execute($update);

        //pages
        $update = "ALTER TABLE `".$prefix."pages`
        MODIFY COLUMN `parent_page_id` INT(10) NULL DEFAULT NULL;
        
        UPDATE `".$prefix."pages` SET `parent_page_id` = NULL WHERE `parent_page_id` = '0';
        
        ALTER TABLE `".$prefix."pages`
        ADD CONSTRAINT `".$prefix."pages_parent_fk` 
            FOREIGN KEY (`parent_page_id`) REFERENCES `".$prefix."pages` (`page_id`) 
            ON DELETE SET NULL ON UPDATE CASCADE;";
        $this->execute($update);

        //blocks_layouts
        //get inconsistency data and remove (orphan blocks in the layouts) before FK creation
        $update = "ALTER TABLE `".$prefix."block_layouts`
                    CHANGE COLUMN `parent_instance_id` `parent_instance_id` INT(10) NULL DEFAULT NULL,
                    CHANGE COLUMN `custom_block_id` `custom_block_id` INT(10) NULL DEFAULT NULL;
                    UPDATE `".$prefix."block_layouts` 
                    SET `parent_instance_id` = NULL 
                    WHERE `parent_instance_id` = '0';";
        $this->execute($update);

        $rows = $this->fetchAll(
            "SELECT * 
            FROM `".$prefix."block_layouts` 
            WHERE `parent_instance_id` 
                NOT IN (SELECT `instance_id` FROM `".$prefix."block_layouts`);"
        );
        if($rows){
            foreach($rows as $row){
                $update = "DELETE FROM `".$prefix."block_layouts` WHERE `instance_id` = '".$row['instance_id']."';";
                $this->execute($update);
            }
        }

        $update = "ALTER TABLE `".$prefix."block_layouts`
                   CONSTRAINT `".$prefix."block_layouts_parent_fk` 
                   FOREIGN KEY (`parent_instance_id`) 
                   REFERENCES `".$prefix."block_layouts` (`instance_id`) 
                       ON DELETE CASCADE ON UPDATE CASCADE;";
        $this->execute($update);

        //custom blocks relation
        $update = "UPDATE `".$prefix."block_layouts`
                    SET `custom_block_id` = NULL 
                    WHERE `custom_block_id` = '0';";
        $this->execute($update);
        $update = "ALTER TABLE `".$prefix."block_layouts` 
        ADD CONSTRAINT `".$prefix."block_layouts_cb_fk`
          FOREIGN KEY (`custom_block_id`)
          REFERENCES `".$prefix."custom_blocks` (`custom_block_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;
        ;";
        $this->execute($update);

        //downloads
        $update = "ALTER TABLE `".$prefix."downloads`
        CHANGE COLUMN `activate_order_status_id` `activate_order_status_id` INT(11) NULL DEFAULT NULL;
        
        ALTER TABLE `".$prefix."downloads`
        ADD CONSTRAINT `".$prefix."downloads_order_status_fk`
          FOREIGN KEY (`activate_order_status_id`)
          REFERENCES `".$prefix."order_statuses` (`order_status_id`)
          ON DELETE RESTRICT
          ON UPDATE CASCADE;";

        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_option_value_descriptions` 
        ADD CONSTRAINT `".$prefix."product_option_value_descriptions_ibfk_3`
          FOREIGN KEY (`product_option_value_id`)
          REFERENCES `".$prefix."product_option_values` (`product_option_value_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);


        $update = "ALTER TABLE `".$prefix."customer_transactions`
                   ADD INDEX `".$prefix."customer_transactions_ibfk_2_idx` (`order_id` ASC);
                   ALTER TABLE `".$prefix."customer_transactions`
                   ADD CONSTRAINT `".$prefix."customer_transactions_ibfk_2`
                      FOREIGN KEY (`order_id`)
                      REFERENCES `".$prefix."orders` (`order_id`)
                      ON DELETE NO ACTION
                      ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."global_attributes_value_descriptions` 
        ADD CONSTRAINT `".$prefix."global_attributes_value_descriptions_ibfk_3`
          FOREIGN KEY (`attribute_value_id`)
          REFERENCES `".$prefix."global_attributes_values` (`attribute_value_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."field_values`
        ADD INDEX `".$prefix."field_values_ibfk_2_idx` (`language_id` ASC);
        ALTER TABLE `".$prefix."field_values`
        ADD CONSTRAINT `".$prefix."field_values_ibfk_2`
          FOREIGN KEY (`language_id`)
          REFERENCES `".$prefix."languages` (`language_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."customer_communications` 
        CHANGE COLUMN `user_id` `user_id` INT(11) NULL DEFAULT NULL;
        ALTER TABLE `".$prefix."customer_communications`
        ADD CONSTRAINT `".$prefix."customer_communications_ibfk_2`
         FOREIGN KEY (`user_id`)
         REFERENCES `".$prefix."users` (`user_id`)
         ON DELETE NO ACTION
         ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."task_details` 
        ADD CONSTRAINT `".$prefix."task_details_ibfk_1`
          FOREIGN KEY (`task_id`)
          REFERENCES `".$prefix."tasks` (`task_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);


        $update = "UPDATE `".$prefix."global_attributes` 
                    SET `attribute_group_id` = NULL 
                    WHERE `attribute_group_id` = '0';";
        $this->execute($update);

        $update ="ALTER TABLE `".$prefix."global_attributes` 
        ADD INDEX `".$prefix."global_attributes_ibfk_1_idx` (`attribute_group_id` ASC);
        ALTER TABLE `".$prefix."global_attributes` 
        ADD CONSTRAINT `".$prefix."global_attributes_ibfk_1`
          FOREIGN KEY (`attribute_group_id`)
          REFERENCES `".$prefix."global_attributes_groups` (`attribute_group_id`)
          ON DELETE SET NULL
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update ="ALTER TABLE `".$prefix."online_customers`
          ADD INDEX `".$prefix."online_customers_fk_1_idx` (`customer_id` ASC);
          ALTER TABLE `".$prefix."online_customers`
          ADD CONSTRAINT `".$prefix."online_customers_fk_1`
            FOREIGN KEY (`customer_id`)
            REFERENCES `".$prefix."customers` (`customer_id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."global_attributes` 
        CHANGE COLUMN `attribute_parent_id` `attribute_parent_id` INT(11) NULL DEFAULT NULL;";
        $this->execute($update);

        $update = "UPDATE `".$prefix."global_attributes` SET `attribute_parent_id` = NULL 
        WHERE `attribute_parent_id` = '0';";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."global_attributes` 
        ADD CONSTRAINT `".$prefix."global_attributes_ibfk_2`
          FOREIGN KEY (`attribute_parent_id`)
          REFERENCES `".$prefix."global_attributes` (`attribute_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."task_steps` 
        ADD CONSTRAINT `".$prefix."task_steps_fk`
          FOREIGN KEY (`task_id`)
          REFERENCES `".$prefix."tasks` (`task_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."global_attributes_type_descriptions` 
        ADD INDEX `".$prefix."global_attributes_type_descriptions_fk_2_idx` (`language_id` ASC);
        ALTER TABLE `".$prefix."global_attributes_type_descriptions` 
        ADD CONSTRAINT `".$prefix."global_attributes_type_descriptions_fk_1`
          FOREIGN KEY (`attribute_type_id`)
          REFERENCES `".$prefix."global_attributes_types` (`attribute_type_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."global_attributes_type_descriptions_fk_2`
          FOREIGN KEY (`language_id`)
          REFERENCES `".$prefix."languages` (`language_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);


        $update = "ALTER TABLE `".$prefix."contents`
                CHANGE COLUMN `parent_content_id` `parent_content_id` INT(11) NULL DEFAULT NULL ,
                DROP PRIMARY KEY,
                ADD PRIMARY KEY (`content_id`),
                ADD INDEX `".$prefix."contents_fk_1_idx` (`parent_content_id` ASC);";
        $this->execute($update);

        $update = "UPDATE `".$prefix."contents` SET `parent_content_id` = NULL WHERE `parent_content_id` = '0';";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."contents`
                ADD CONSTRAINT `".$prefix."contents_fk_1`
                  FOREIGN KEY (`parent_content_id`)
                  REFERENCES `".$prefix."contents` (`content_id`)
                  ON DELETE SET NULL
                  ON UPDATE CASCADE;";
        $this->execute($update);


        $update = "ALTER TABLE `".$prefix."global_attributes_groups_descriptions` 
        ADD INDEX `".$prefix."global_attributes_groups_descriptions_fk_2_idx` (`language_id` ASC);
        ALTER TABLE `".$prefix."global_attributes_groups_descriptions` 
        ADD CONSTRAINT `".$prefix."global_attributes_groups_descriptions_fk_1`
          FOREIGN KEY (`attribute_group_id`)
          REFERENCES `".$prefix."global_attributes_groups` (`attribute_group_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."global_attributes_groups_descriptions_fk_2`
          FOREIGN KEY (`language_id`)
          REFERENCES `".$prefix."languages` (`language_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);


        $update = " ALTER TABLE `".$prefix."product_discounts`
                    ADD INDEX `".$prefix."product_discounts_ibfk_2_idx` (`customer_group_id` ASC);
                    ALTER TABLE `".$prefix."product_discounts`
                    ADD CONSTRAINT `".$prefix."product_discounts_ibfk_2`
                    FOREIGN KEY (`customer_group_id`)
                    REFERENCES `".$prefix."customer_groups` (`customer_group_id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_specials`
        ADD INDEX `".$prefix."product_specials_ibfk_2_idx` (`customer_group_id` ASC);
        ALTER TABLE `".$prefix."product_specials`
        ADD CONSTRAINT `".$prefix."product_specials_ibfk_2`
          FOREIGN KEY (`customer_group_id`)
          REFERENCES `".$prefix."customer_groups` (`customer_group_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."order_downloads` 
        ADD INDEX `".$prefix."order_downloads_ibfk_3_idx` (`download_id` ASC);
        ALTER TABLE `".$prefix."order_downloads` 
        ADD CONSTRAINT `".$prefix."order_downloads_ibfk_3`
          FOREIGN KEY (`download_id`)
          REFERENCES `".$prefix."downloads` (`download_id`)
          ON DELETE NO ACTION
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."categories`
        CHANGE COLUMN `parent_id` `parent_id` INT(11) NULL DEFAULT NULL ,
        ADD INDEX `".$prefix."categories_fk_1_idx` (`parent_id` ASC);
        UPDATE `".$prefix."categories` SET `parent_id` = NULL WHERE `parent_id` = '0';        
        ALTER TABLE `".$prefix."categories`
        ADD CONSTRAINT `".$prefix."categories_fk_1`
        FOREIGN KEY (`parent_id`)
        REFERENCES `".$prefix."categories` (`category_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;";
        $this->execute($update);

        
        $update = "ALTER TABLE `".$prefix."order_history`
        ADD CONSTRAINT `".$prefix."order_history_ibfk_2`
          FOREIGN KEY (`order_id`)
          REFERENCES `".$prefix."orders` (`order_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);
        
        $update = "ALTER TABLE `".$prefix."order_products`
                ADD INDEX `".$prefix."order_products_ibfk_2_idx` (`product_id` ASC);
                ALTER TABLE `".$prefix."order_products`
                ADD CONSTRAINT `".$prefix."order_products_ibfk_2`
                  FOREIGN KEY (`product_id`)
                  REFERENCES `".$prefix."products` (`product_id`)
                  ON DELETE NO ACTION
          ON UPDATE CASCADE;";
        $this->execute($update);
        
        $update = "ALTER TABLE `".$prefix."extension_dependencies`
        ADD CONSTRAINT `".$prefix."extension_dependencies_fk_1`
          FOREIGN KEY (`extension_id`)
          REFERENCES `".$prefix."extensions` (`extension_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."products_related` 
        ADD INDEX `".$prefix."products_related_ibfk_2_idx` (`related_id` ASC);
        ALTER TABLE `".$prefix."products_related` 
        ADD CONSTRAINT `".$prefix."products_related_ibfk_2`
        FOREIGN KEY (`related_id`)
        REFERENCES `".$prefix."products` (`product_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."order_downloads_history`
        ADD CONSTRAINT `".$prefix."order_downloads_history_ibfk_4`
          FOREIGN KEY (`download_id`)
          REFERENCES `".$prefix."downloads` (`download_id`)
          ON DELETE NO ACTION
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."length_class_descriptions` 
        ADD CONSTRAINT `".$prefix."length_class_descriptions_ibfk_2`
          FOREIGN KEY (`length_class_id`)
          REFERENCES `".$prefix."length_classes` (`length_class_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);
        
        $update = "ALTER TABLE `".$prefix."banner_stat`
        ADD INDEX `".$prefix."banner_stat_ibfk_2_idx` (`store_id` ASC);
        ALTER TABLE `".$prefix."banner_stat`
        ADD CONSTRAINT `".$prefix."banner_stat_ibfk_2`
          FOREIGN KEY (`store_id`)
          REFERENCES `".$prefix."stores` (`store_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."order_options`
        ADD INDEX `".$prefix."order_options_fk_2_idx` (`order_product_id` ASC);
        ALTER TABLE `".$prefix."order_options`
        ADD CONSTRAINT `".$prefix."order_options_fk_1`
          FOREIGN KEY (`order_id`)
          REFERENCES `".$prefix."orders` (`order_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."order_options_fk_2`
          FOREIGN KEY (`order_product_id`)
          REFERENCES `".$prefix."order_products` (`order_product_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

        //update restrict FKEYS
        $update = "ALTER TABLE `".$prefix."addresses` 
        DROP FOREIGN KEY `".$prefix."addresses_ibfk_2`,
        DROP FOREIGN KEY `".$prefix."addresses_ibfk_3`;
        ALTER TABLE `".$prefix."addresses` 
        ADD CONSTRAINT `".$prefix."addresses_ibfk_2`
          FOREIGN KEY (`country_id`)
          REFERENCES `".$prefix."countries` (`country_id`)
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."addresses_ibfk_3`
          FOREIGN KEY (`zone_id`)
          REFERENCES `".$prefix."zones` (`zone_id`)
          ON DELETE RESTRICT
          ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."customer_notes` 
        DROP FOREIGN KEY `".$prefix."customer_notes_ibfk_1`;
        ALTER TABLE `".$prefix."customer_notes` 
        ADD CONSTRAINT `".$prefix."customer_notes_ibfk_1`
          FOREIGN KEY (`user_id`)
          REFERENCES `".$prefix."users` (`user_id`)
          ON DELETE CASCADE ON UPDATE CASCADE; ";
        $this->execute($update);
        
        $update = "ALTER TABLE `".$prefix."customers` 
        DROP FOREIGN KEY `".$prefix."customers_ibfk_1`;
        ALTER TABLE `".$prefix."customers` 
        ADD CONSTRAINT `".$prefix."customers_ibfk_1`
          FOREIGN KEY (`store_id`)
          REFERENCES `".$prefix."stores` (`store_id`)
          ON DELETE RESTRICT
          ON UPDATE CASCADE;
        ";
        $this->execute($update);
        
      /*  $update = "ALTER TABLE `".$prefix."order_data`
        DROP FOREIGN KEY `".$prefix."order_data_ibfk_1`,
        DROP FOREIGN KEY `".$prefix."order_data_ibfk_2`;
        ALTER TABLE `".$prefix."order_data` 
        ADD CONSTRAINT `".$prefix."order_data_ibfk_1`
          FOREIGN KEY (`order_id`)
          REFERENCES `".$prefix."orders` (`order_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."order_data_ibfk_2`
          FOREIGN KEY (`type_id`)
          REFERENCES `".$prefix."order_data_types` (`type_id`)
          ON DELETE RESTRICT
          ON UPDATE CASCADE;";
        $this->execute($update);*/

        $update = "ALTER TABLE `".$prefix."order_downloads` 
        DROP FOREIGN KEY `".$prefix."order_downloads_ibfk_1`,
        DROP FOREIGN KEY `".$prefix."order_downloads_ibfk_2`;
        ALTER TABLE `".$prefix."order_downloads` 
        ADD CONSTRAINT `".$prefix."order_downloads_ibfk_1`
          FOREIGN KEY (`order_id`)
          REFERENCES `".$prefix."orders` (`order_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."order_downloads_ibfk_2`
          FOREIGN KEY (`order_product_id`)
          REFERENCES `".$prefix."order_products` (`order_product_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;
        ";
        $this->execute($update);
        
        
        $update = "ALTER TABLE `".$prefix."order_downloads_history` 
        DROP FOREIGN KEY `".$prefix."order_downloads_history_ibfk_1`,
        DROP FOREIGN KEY `".$prefix."order_downloads_history_ibfk_2`,
        DROP FOREIGN KEY `".$prefix."order_downloads_history_ibfk_3`;
        ALTER TABLE `".$prefix."order_downloads_history` 
        ADD CONSTRAINT `".$prefix."order_downloads_history_ibfk_1`
          FOREIGN KEY (`order_download_id`)
          REFERENCES `".$prefix."order_downloads` (`order_download_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."order_downloads_history_ibfk_2`
          FOREIGN KEY (`order_id`)
          REFERENCES `".$prefix."orders` (`order_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        ADD CONSTRAINT `".$prefix."order_downloads_history_ibfk_3`
          FOREIGN KEY (`order_product_id`)
          REFERENCES `".$prefix."order_products` (`order_product_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;
        ";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."order_products` 
        DROP FOREIGN KEY `".$prefix."order_products_ibfk_1`;
        ALTER TABLE `".$prefix."order_products` 
        ADD CONSTRAINT `".$prefix."order_products_ibfk_1`
          FOREIGN KEY (`order_id`)
          REFERENCES `".$prefix."orders` (`order_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;
        ";
        $this->execute($update);
        
        $update = "ALTER TABLE `".$prefix."order_totals` 
        DROP FOREIGN KEY `".$prefix."order_totals_ibfk_1`;
        ALTER TABLE `".$prefix."order_totals` 
        ADD CONSTRAINT `".$prefix."order_totals_ibfk_1`
          FOREIGN KEY (`order_id`)
          REFERENCES `".$prefix."orders` (`order_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);
        
        $update = "ALTER TABLE `".$prefix."users` 
        DROP FOREIGN KEY `".$prefix."users_ibfk_1`;
        ALTER TABLE `".$prefix."users` 
        ADD CONSTRAINT `".$prefix."users_ibfk_1`
          FOREIGN KEY (`user_group_id`)
          REFERENCES `".$prefix."user_groups` (`user_group_id`)
          ON DELETE RESTRICT
          ON UPDATE CASCADE;";
        $this->execute($update);
        
        $update = "ALTER TABLE `".$prefix."tax_rates` 
        DROP FOREIGN KEY `".$prefix."tax_rates_ibfk_3`;
        ALTER TABLE `".$prefix."tax_rates` 
        ADD CONSTRAINT `".$prefix."tax_rates_ibfk_3`
          FOREIGN KEY (`zone_id`)
          REFERENCES `".$prefix."zones` (`zone_id`)
          ON DELETE SET NULL
          ON UPDATE CASCADE;
        ";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."zones` 
        DROP FOREIGN KEY `".$prefix."zones_ibfk_1`;
        ALTER TABLE `".$prefix."zones` 
        ADD CONSTRAINT `".$prefix."zones_ibfk_1`
          FOREIGN KEY (`country_id`)
          REFERENCES `".$prefix."countries` (`country_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;
        ";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."global_attributes` 
        ADD INDEX `".$prefix."global_attributes_ibfk_3_idx` (`attribute_type_id` ASC);
        ALTER TABLE `".$prefix."global_attributes` 
        ADD CONSTRAINT `".$prefix."global_attributes_ibfk_3`
          FOREIGN KEY (`attribute_type_id`)
          REFERENCES `".$prefix."global_attributes_types` (`attribute_type_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);


        $update = "ALTER TABLE `".$prefix."block_layouts` 
          ADD INDEX `".$prefix."block_layouts_ibfk_3_idx` (`layout_id` ASC);
          ALTER TABLE `".$prefix."block_layouts` 
          ADD CONSTRAINT `".$prefix."block_layouts_ibfk_3`
            FOREIGN KEY (`layout_id`)
            REFERENCES `".$prefix."layouts` (`layout_id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."block_layouts` 
                  ADD INDEX `".$prefix."block_layouts_ibfk_4_idx` (`block_id` ASC);
                  ALTER TABLE `".$prefix."block_layouts` 
                  ADD CONSTRAINT `".$prefix."block_layouts_ibfk_4`
                    FOREIGN KEY (`block_id`)
                    REFERENCES `".$prefix."blocks` (`block_id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."customers` 
        CHANGE COLUMN `address_id` `address_id` INT(11) NULL DEFAULT NULL ,
        ADD INDEX `".$prefix."customers_ibfk_3_idx` (`address_id` ASC);";
        $this->execute($update);

        $update = "UPDATE `".$prefix."customers` 
        SET `address_id` = NULL 
        WHERE `address_id` = '0';";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."customers` 
        ADD CONSTRAINT `".$prefix."customers_ibfk_3`
          FOREIGN KEY (`address_id`)
          REFERENCES `".$prefix."addresses` (`address_id`)
          ON DELETE SET NULL
          ON UPDATE CASCADE;";
        $this->execute($update);

    }

    public function down()
    {

    }
}