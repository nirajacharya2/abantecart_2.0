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



        //add primary AI key to pivot tables
        $pivots = [
            'banner_descriptions',
            'category_descriptions',
            'categories_to_stores',
            'content_descriptions',
            'contents_to_stores',
            'country_descriptions',
            'coupon_descriptions',
            'customer_notifications',
            'download_descriptions',
            'extension_dependencies',
            'field_descriptions',
            'fields_group_descriptions',
            'fields_groups',
            'form_descriptions',
            'global_attributes_descriptions',
            'global_attributes_type_descriptions',
            'global_attributes_value_descriptions',
            'length_class_descriptions',
            'manufacturers_to_stores',
            'order_data',
            'order_status_descriptions',
            'page_descriptions',
            'pages_forms',
            'pages_layouts',
            'product_descriptions',
            'product_option_descriptions',
            'product_option_value_descriptions',
            'products_related',
            'products_to_categories',
            'products_to_downloads',
            'products_to_stores',
            'product_tags',
            'resource_descriptions',
            'resource_map',
            'store_descriptions',
            'tax_class_descriptions',
            'tax_rate_descriptions',
            'weight_class_descriptions',
            'zone_descriptions',
            'global_attributes_groups_descriptions',
            ];
        foreach($pivots as $table_name){
            //check if table has no any ai pk
            $rows = $this->fetchAll(
                "SELECT * 
                FROM `information_schema`.COLUMNS
                WHERE `TABLE_SCHEMA` = '".$db_name."'
                    AND `TABLE_NAME` = '".$prefix.$table_name."' 
                    AND `COLUMN_KEY` = 'PRI' AND `EXTRA` = 'auto_increment'");
            if($rows){ continue;}

            //if Fkeys presents - remove it before primary key rebuild!
            $foreign_keys = $this->fetchAll(
                "SELECT cu.TABLE_NAME, cu.COLUMN_NAME, cu.CONSTRAINT_NAME, cu.REFERENCED_TABLE_NAME, cu.REFERENCED_COLUMN_NAME, tc.UPDATE_RULE, tc.DELETE_RULE 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu
                INNER JOIN information_schema.COLUMNS c 
                    ON ( cu.COLUMN_NAME = c.COLUMN_NAME 
                        AND c.TABLE_SCHEMA = cu.REFERENCED_TABLE_SCHEMA
                        AND c.TABLE_NAME = cu.TABLE_NAME
                    )
                LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS tc
                    ON (cu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME AND UNIQUE_CONSTRAINT_SCHEMA = '".$db_name."' )
                WHERE cu.REFERENCED_TABLE_SCHEMA = '".$db_name."' 
                   AND cu.TABLE_NAME = '".$prefix.$table_name."';");
            $fk_sql = [];

            if($foreign_keys){
                foreach($foreign_keys as $row){
                    $this->execute(
                        "ALTER TABLE `".$prefix.$table_name."` DROP FOREIGN KEY `".$row['CONSTRAINT_NAME']."`;"
                    );
                    //build SQL for recreation of FKeys LATER!
                    $fk_sql[] = "ALTER TABLE `".$prefix.$table_name."` 
                    ADD CONSTRAINT `".$row['CONSTRAINT_NAME']."`
                       FOREIGN KEY (`".$row['COLUMN_NAME']."`) 
                       REFERENCES `".$row['REFERENCED_TABLE_NAME']."` (`".$row['REFERENCED_COLUMN_NAME']."`)
                       ON DELETE ".$row['DELETE_RULE']." ON UPDATE ".$row['UPDATE_RULE'].";";
                }
            }

            //get all primary keys and detect if they have foreign key
            $primary_keys = $this->fetchAll(
                            "SELECT * 
                            FROM `information_schema`.COLUMNS
                            WHERE `TABLE_SCHEMA` = '".$db_name."'
                                AND `TABLE_NAME` = '".$prefix.$table_name."' 
                                AND `COLUMN_KEY` = 'PRI'"
            );
            if(!$primary_keys){
                continue;
            }
            if(!$this->table($table_name)->hasColumn('id')) {
                $this->execute(
                    "ALTER TABLE `".$prefix.$table_name."` DROP PRIMARY KEY,
                    ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
                    ADD PRIMARY KEY (`id`, `".implode("`,`", array_column($primary_keys, 'COLUMN_NAME'))."`);"
                );
            }

            if($fk_sql){
                foreach($fk_sql as $sql){
                    $this->execute($sql);
                }
            }
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
        UPDATE `".$prefix."pages` SET `parent_page_id` = NULL WHERE `parent_page_id` = '0';";
        $this->execute($update);

        $FKeys = [
            [
                'name' => $prefix."pages_parent_fk",
                'table' => $prefix."pages",
                'column' => "parent_page_id",
                'to_table' => $prefix."pages",
                'to_column' => "page_id",
                'delete_rule' => "SET NULL",
                'update_rule' => "CASCADE"
            ]
        ];

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



        //custom blocks relation
        $update = "UPDATE `".$prefix."block_layouts`
                    SET `custom_block_id` = NULL 
                    WHERE `custom_block_id` = '0';";
        $this->execute($update);
        $FKeys[] =
            [
                'name' => $prefix."block_layouts_parent_fk",
                'table' => $prefix."block_layouts",
                'column' => "parent_instance_id",
                'to_table' => $prefix."block_layouts",
                'to_column' => "instance_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        //downloads
        $update = "ALTER TABLE `".$prefix."downloads`
        CHANGE COLUMN `activate_order_status_id` `activate_order_status_id` INT(11) NULL DEFAULT NULL;";
        $this->execute($update);

        $FKeys[] =
            [   'name' => $prefix."downloads_order_status_fk",
                'table' => $prefix."downloads",
                'column' => "activate_order_status_id",
                'to_table' => $prefix."order_statuses",
                'to_column' => "order_status_id",
                'delete_rule' => "RESTRICT",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."product_option_value_descriptions_ibfk_3",
                'table' => $prefix."product_option_value_descriptions",
                'column' => "product_option_value_id",
                'to_table' => $prefix."product_option_values",
                'to_column' => "product_option_value_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('customer_transactions')->hasIndex(['order_id'])) {
            $update = "ALTER TABLE `".$prefix."customer_transactions`
                       ADD INDEX `".$prefix."customer_transactions_ibfk_2_idx` (`order_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."customer_transactions_ibfk_2",
                'table' => $prefix."customer_transactions",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "NO ACTION",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."global_attributes_value_descriptions_ibfk_3",
                'table' => $prefix."global_attributes_value_descriptions",
                'column' => "attribute_value_id",
                'to_table' => $prefix."global_attributes_values",
                'to_column' => "attribute_value_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('field_values')->hasIndex(['language_id'])) {
            $update = "ALTER TABLE `".$prefix."field_values`
                       ADD INDEX `".$prefix."field_values_ibfk_2_idx` (`language_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."field_values_ibfk_2",
                'table' => $prefix."field_values",
                'column' => "language_id",
                'to_table' => $prefix."languages",
                'to_column' => "language_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if ($this->hasTable('customer_communications')) {
            $update = "ALTER TABLE `".$prefix."customer_communications` 
            CHANGE COLUMN `user_id` `user_id` INT(11) NULL DEFAULT NULL;";
            $this->execute($update);
            $FKeys[] =
                [
                    'name' => $prefix."customer_communications_ibfk_2",
                    'table' => $prefix."customer_communications",
                    'column' => "user_id",
                    'to_table' => $prefix."users",
                    'to_column' => "user_id",
                    'delete_rule' => "NO ACTION",
                    'update_rule' => "CASCADE"
                ];
        }

        $FKeys[] =
            [
                'name' => $prefix."task_details_ibfk_1",
                'table' => $prefix."task_details",
                'column' => "task_id",
                'to_table' => $prefix."tasks",
                'to_column' => "task_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];


        $update = "UPDATE `".$prefix."global_attributes` 
                    SET `attribute_group_id` = NULL 
                    WHERE `attribute_group_id` = '0';";
        $this->execute($update);

        if(!$this->table('global_attributes')->hasIndex(['attribute_group_id'])) {
            $update = "ALTER TABLE `".$prefix."global_attributes` 
                       ADD INDEX `".$prefix."global_attributes_ibfk_1_idx` (`attribute_group_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."global_attributes_ibfk_1",
                'table' => $prefix."global_attributes",
                'column' => "attribute_group_id",
                'to_table' => $prefix."global_attributes_groups",
                'to_column' => "attribute_group_id",
                'delete_rule' => "SET NULL",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('online_customers')->hasIndex(['customer_id'])) {
            $update = "ALTER TABLE `".$prefix."online_customers`
                       ADD INDEX `".$prefix."online_customers_fk_1_idx` (`customer_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."online_customers_fk_1",
                'table' => $prefix."online_customers",
                'column' => "customer_id",
                'to_table' => $prefix."customers",
                'to_column' => "customer_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        $update = "ALTER TABLE `".$prefix."global_attributes` 
                   CHANGE COLUMN `attribute_parent_id` `attribute_parent_id` INT(11) NULL DEFAULT NULL;";
        $this->execute($update);

        $update = "UPDATE `".$prefix."global_attributes` 
                    SET `attribute_parent_id` = NULL 
                    WHERE `attribute_parent_id` = '0';";
        $this->execute($update);

        $FKeys[] =
            [
                'name' => $prefix."global_attributes_ibfk_2",
                'table' => $prefix."global_attributes",
                'column' => "attribute_parent_id",
                'to_table' => $prefix."global_attributes",
                'to_column' => "attribute_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        $FKeys[] =
            [
                'name' => $prefix."task_steps_fk",
                'table' => $prefix."task_steps",
                'column' => "task_id",
                'to_table' => $prefix."tasks",
                'to_column' => "task_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        if(!$this->table('global_attributes_type_descriptions')->hasIndex(['language_id'])) {
            $update = "ALTER TABLE `".$prefix."global_attributes_type_descriptions` 
                       ADD INDEX `".$prefix."global_attributes_type_descriptions_fk_2_idx` (`language_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."global_attributes_type_descriptions_fk_1",
                'table' => $prefix."global_attributes_type_descriptions",
                'column' => "attribute_type_id",
                'to_table' => $prefix."global_attributes_types",
                'to_column' => "attribute_type_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."global_attributes_type_descriptions_fk_2",
                'table' => $prefix."global_attributes_type_descriptions",
                'column' => "language_id",
                'to_table' => $prefix."languages",
                'to_column' => "language_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        $update = "ALTER TABLE `".$prefix."contents`
                CHANGE COLUMN `parent_content_id` `parent_content_id` INT(11) NULL DEFAULT NULL ,
                DROP PRIMARY KEY,
                ADD PRIMARY KEY (`content_id`)";
        $this->execute($update);

        if(!$this->table('contents')->hasIndex(['parent_content_id'])) {
            $update = "ALTER TABLE `".$prefix."contents`
                       ADD INDEX `".$prefix."contents_fk_1_idx` (`` ASC);";
            $this->execute($update);
        }

        $update = "UPDATE `".$prefix."contents` SET `parent_content_id` = NULL WHERE `parent_content_id` = '0';";
        $this->execute($update);

        $FKeys[] =
            [
                'name' => $prefix."contents_fk_1",
                'table' => $prefix."contents",
                'column' => "parent_content_id",
                'to_table' => $prefix."contents",
                'to_column' => "content_id",
                'delete_rule' => "SET NULL",
                'update_rule' => "CASCADE"
            ];


        if(!$this->table('global_attributes_groups_descriptions')->hasIndex(['language_id'])) {
            $update = "ALTER TABLE `".$prefix."global_attributes_groups_descriptions` 
                       ADD INDEX `".$prefix."global_attributes_groups_descriptions_fk_2_idx` (`language_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."global_attributes_groups_descriptions_fk_1",
                'table' => $prefix."global_attributes_groups_descriptions",
                'column' => "attribute_group_id",
                'to_table' => $prefix."global_attributes_groups",
                'to_column' => "attribute_group_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."global_attributes_groups_descriptions_fk_2",
                'table' => $prefix."global_attributes_groups_descriptions",
                'column' => "language_id",
                'to_table' => $prefix."languages",
                'to_column' => "language_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('product_discounts')->hasIndex(['customer_group_id'])) {
            $update = " ALTER TABLE `".$prefix."product_discounts`
                        ADD INDEX `".$prefix."product_discounts_ibfk_2_idx` (`customer_group_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."product_discounts_ibfk_2",
                'table' => $prefix."product_discounts",
                'column' => "customer_group_id",
                'to_table' => $prefix."customer_groups",
                'to_column' => "customer_group_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('product_specials')->hasIndex(['customer_group_id'])) {
            $update = "ALTER TABLE `".$prefix."product_specials`
                       ADD INDEX `".$prefix."product_specials_ibfk_2_idx` (`customer_group_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."product_specials_ibfk_2",
                'table' => $prefix."product_specials",
                'column' => "customer_group_id",
                'to_table' => $prefix."customer_groups",
                'to_column' => "customer_group_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('order_downloads')->hasIndex(['download_id'])) {
            $update = "ALTER TABLE `".$prefix."order_downloads`
                       ADD INDEX `".$prefix."order_downloads_ibfk_3_idx` (`download_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."order_downloads_ibfk_3",
                'table' => $prefix."order_downloads",
                'column' => "download_id",
                'to_table' => $prefix."downloads",
                'to_column' => "download_id",
                'delete_rule' => "NO ACTION",
                'update_rule' => "CASCADE"
            ];

        $update = "ALTER TABLE `".$prefix."categories`
        CHANGE COLUMN `parent_id` `parent_id` INT(11) NULL DEFAULT NULL;
        UPDATE `".$prefix."categories` SET `parent_id` = NULL WHERE `parent_id` = '0'; ";
        $this->execute($update);
        if(!$this->table('categories')->hasIndex(['parent_id'])){
            $update = "ALTER TABLE `".$prefix."categories`
                   ADD INDEX `".$prefix."categories_fk_1_idx` (`parent_id` ASC);";
            $this->execute($update);
        }


        $FKeys[] =
            [
                'name' => $prefix."categories_fk_1",
                'table' => $prefix."categories",
                'column' => "parent_id",
                'to_table' => $prefix."categories",
                'to_column' => "category_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        $FKeys[] =
            [
                'name' => $prefix."order_history_ibfk_2",
                'table' => $prefix."order_history",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('order_products')->hasIndex(['product_id'])) {
            $update = "ALTER TABLE `".$prefix."order_products`
                       ADD INDEX `".$prefix."order_products_ibfk_2_idx` (`product_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."order_products_ibfk_2",
                'table' => $prefix."order_products",
                'column' => "product_id",
                'to_table' => $prefix."products",
                'to_column' => "product_id",
                'delete_rule' => "NO ACTION",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."extension_dependencies_fk_1",
                'table' => $prefix."extension_dependencies",
                'column' => "extension_id",
                'to_table' => $prefix."extensions",
                'to_column' => "extension_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('products_related')->hasIndex(['related_id'])) {
            $update = "ALTER TABLE `".$prefix."products_related`
                        ADD INDEX `".$prefix."products_related_ibfk_2_idx` (`related_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."products_related_ibfk_2",
                'table' => $prefix."products_related",
                'column' => "related_id",
                'to_table' => $prefix."products",
                'to_column' => "product_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        $FKeys[] =
            [
                'name' => $prefix."order_downloads_history_ibfk_4",
                'table' => $prefix."order_downloads_history",
                'column' => "download_id",
                'to_table' => $prefix."downloads",
                'to_column' => "download_id",
                'delete_rule' => "NO ACTION",
                'update_rule' => "CASCADE"
            ];

        $FKeys[] =
            [
                'name' => $prefix."length_class_descriptions_ibfk_2",
                'table' => $prefix."length_class_descriptions",
                'column' => "length_class_id",
                'to_table' => $prefix."length_classes",
                'to_column' => "length_class_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('banner_stat')->hasIndex(['store_id'])) {
            $update = "ALTER TABLE `".$prefix."banner_stat`
                        ADD INDEX `".$prefix."banner_stat_ibfk_2_idx` (`store_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."banner_stat_ibfk_2",
                'table' => $prefix."banner_stat",
                'column' => "store_id",
                'to_table' => $prefix."stores",
                'to_column' => "store_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        if(!$this->table('order_options')->hasIndex(['order_product_id'])) {
            $update = "ALTER TABLE `".$prefix."order_options`
                        ADD INDEX `".$prefix."order_options_fk_2_idx` (`order_product_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."order_options_fk_1",
                'table' => $prefix."order_options",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."order_options_fk_2",
                'table' => $prefix."order_options",
                'column' => "order_product_id",
                'to_table' => $prefix."order_products",
                'to_column' => "order_product_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        //update restrict FKEYS
        if($this->table('addresses')->hasForeignKey($prefix."addresses_ibfk_2")){
            $update = "ALTER TABLE `".$prefix."addresses` 
                       DROP FOREIGN KEY `".$prefix."addresses_ibfk_2`;";
            $this->execute($update);
        }

        if($this->table('addresses')->hasForeignKey($prefix."addresses_ibfk_3")) {
            $update = "ALTER TABLE `".$prefix."addresses` 
                       DROP FOREIGN KEY `".$prefix."addresses_ibfk_3`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."addresses_ibfk_2",
                'table' => $prefix."addresses",
                'column' => "country_id",
                'to_table' => $prefix."countries",
                'to_column' => "country_id",
                'delete_rule' => "NO ACTION",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."addresses_ibfk_3",
                'table' => $prefix."addresses",
                'column' => "zone_id",
                'to_table' => $prefix."zones",
                'to_column' => "zone_id",
                'delete_rule' => "RESTRICT",
                'update_rule' => "CASCADE"
            ];

        if($this->table('customer_notes')->hasForeignKey($prefix."customer_notes_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."customer_notes` 
                       DROP FOREIGN KEY `".$prefix."customer_notes_ibfk_1`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."customer_notes_ibfk_1",
                'table' => $prefix."customer_notes",
                'column' => "user_id",
                'to_table' => $prefix."users",
                'to_column' => "user_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if($this->table('customers')->hasForeignKey($prefix."customers_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."customers` 
                       DROP FOREIGN KEY `".$prefix."customers_ibfk_1`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."customers_ibfk_1",
                'table' => $prefix."customers",
                'column' => "store_id",
                'to_table' => $prefix."stores",
                'to_column' => "store_id",
                'delete_rule' => "RESTRICT",
                'update_rule' => "CASCADE"
            ];

        if($this->table('order_data')->hasForeignKey($prefix."order_data_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."order_data`
                        DROP FOREIGN KEY `".$prefix."order_data_ibfk_1`;";
            $this->execute($update);
        }
        if($this->table('order_data')->hasForeignKey($prefix."order_data_ibfk_2")) {
            $update = "ALTER TABLE `".$prefix."order_data`
                        DROP FOREIGN KEY `".$prefix."order_data_ibfk_2`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."order_data_ibfk_1",
                'table' => $prefix."order_data",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."order_data_ibfk_2",
                'table' => $prefix."order_data",
                'column' => "type_id",
                'to_table' => $prefix."order_data_types",
                'to_column' => "type_id",
                'delete_rule' => "RESTRICT",
                'update_rule' => "CASCADE"
            ];


        if($this->table('order_downloads')->hasForeignKey($prefix."order_downloads_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."order_downloads` 
                    DROP FOREIGN KEY `".$prefix."order_downloads_ibfk_1`;";
            $this->execute($update);
        }

        if($this->table('order_downloads')->hasForeignKey($prefix."order_downloads_ibfk_2")) {
            $update = "ALTER TABLE `".$prefix."order_downloads` 
                    DROP FOREIGN KEY `".$prefix."order_downloads_ibfk_2`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."order_downloads_ibfk_1",
                'table' => $prefix."order_downloads",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."order_downloads_ibfk_2",
                'table' => $prefix."order_downloads",
                'column' => "order_product_id",
                'to_table' => $prefix."order_products",
                'to_column' => "order_product_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if($this->table('order_downloads_history')->hasForeignKey($prefix."order_downloads_history_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."order_downloads_history` 
                        DROP FOREIGN KEY `".$prefix."order_downloads_history_ibfk_1`;";
            $this->execute($update);
        }

        if($this->table('order_downloads_history')->hasForeignKey($prefix."order_downloads_history_ibfk_2")) {
            $update = "ALTER TABLE `".$prefix."order_downloads_history` 
                        DROP FOREIGN KEY `".$prefix."order_downloads_history_ibfk_2`;";
            $this->execute($update);
        }

        if($this->table('order_downloads_history')->hasForeignKey($prefix."order_downloads_history_ibfk_3")) {
            $update = "ALTER TABLE `".$prefix."order_downloads_history` 
                        DROP FOREIGN KEY `".$prefix."order_downloads_history_ibfk_3`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."order_downloads_history_ibfk_1",
                'table' => $prefix."order_downloads_history",
                'column' => "order_download_id",
                'to_table' => $prefix."order_downloads",
                'to_column' => "order_download_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."order_downloads_history_ibfk_2",
                'table' => $prefix."order_downloads_history",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];
        $FKeys[] =
            [
                'name' => $prefix."order_downloads_history_ibfk_3",
                'table' => $prefix."order_downloads_history",
                'column' => "order_product_id",
                'to_table' => $prefix."order_products",
                'to_column' => "order_product_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if($this->table('order_products')->hasForeignKey($prefix."order_products_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."order_products` 
                       DROP FOREIGN KEY `".$prefix."order_products_ibfk_1`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."order_products_ibfk_1",
                'table' => $prefix."order_products",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if($this->table('order_totals')->hasForeignKey($prefix."order_totals_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."order_totals` 
                        DROP FOREIGN KEY `".$prefix."order_totals_ibfk_1`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."order_totals_ibfk_1",
                'table' => $prefix."order_totals",
                'column' => "order_id",
                'to_table' => $prefix."orders",
                'to_column' => "order_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if($this->table('users')->hasForeignKey($prefix."users_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."users` 
                       DROP FOREIGN KEY `".$prefix."users_ibfk_1`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."users_ibfk_1",
                'table' => $prefix."users",
                'column' => "user_group_id",
                'to_table' => $prefix."user_groups",
                'to_column' => "user_group_id",
                'delete_rule' => "RESTRICT",
                'update_rule' => "CASCADE"
            ];
        if($this->table('tax_rates')->hasForeignKey($prefix."tax_rates_ibfk_3")) {
            $update = "ALTER TABLE `".$prefix."tax_rates` 
                        DROP FOREIGN KEY `".$prefix."tax_rates_ibfk_3`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."tax_rates_ibfk_3",
                'table' => $prefix."tax_rates",
                'column' => "zone_id",
                'to_table' => $prefix."zones",
                'to_column' => "zone_id",
                'delete_rule' => "SET NULL",
                'update_rule' => "CASCADE"
            ];

        if($this->table('zones')->hasForeignKey($prefix."zones_ibfk_1")) {
            $update = "ALTER TABLE `".$prefix."zones` 
                      DROP FOREIGN KEY `".$prefix."zones_ibfk_1`;";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."zones_ibfk_1",
                'table' => $prefix."zones",
                'column' => "country_id",
                'to_table' => $prefix."countries",
                'to_column' => "country_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('global_attributes')->hasIndex(['attribute_type_id'])) {
            $update = "ALTER TABLE `".$prefix."global_attributes` 
                       ADD INDEX `".$prefix."global_attributes_ibfk_3_idx` (`attribute_type_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."global_attributes_ibfk_3",
                'table' => $prefix."global_attributes",
                'column' => "attribute_type_id",
                'to_table' => $prefix."global_attributes_types",
                'to_column' => "attribute_type_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('block_layouts')->hasIndex(['layout_id'])) {
            $update = "ALTER TABLE `".$prefix."block_layouts` 
                       ADD INDEX `".$prefix."block_layouts_ibfk_3_idx` (`layout_id` ASC);";
            $this->execute($update);
        }

        $FKeys[] =
            [
                'name' => $prefix."block_layouts_ibfk_3",
                'table' => $prefix."block_layouts",
                'column' => "layout_id",
                'to_table' => $prefix."layouts",
                'to_column' => "layout_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        if(!$this->table('block_layouts')->hasIndex(['block_id'])) {
            $update = "ALTER TABLE `".$prefix."block_layouts`
                   ADD INDEX `".$prefix."block_layouts_ibfk_4_idx` (`block_id` ASC);";
            $this->execute($update);
        }
        $FKeys[] =
            [
                'name' => $prefix."block_layouts_ibfk_4",
                'table' => $prefix."block_layouts",
                'column' => "block_id",
                'to_table' => $prefix."blocks",
                'to_column' => "block_id",
                'delete_rule' => "CASCADE",
                'update_rule' => "CASCADE"
            ];

        $update = "ALTER TABLE `".$prefix."customers` 
                   CHANGE COLUMN `address_id` `address_id` INT(11) NULL DEFAULT NULL;";
        $this->execute($update);

        if(!$this->table('customers')->hasIndex(['address_id'])) {
            $update = "ALTER TABLE `".$prefix."customers`
                       ADD INDEX `".$prefix."customers_ibfk_3_idx` (`address_id` ASC);";
            $this->execute($update);
        }

        $update = "UPDATE `".$prefix."customers` 
        SET `address_id` = NULL 
        WHERE `address_id` = '0';";
        $this->execute($update);

        $FKeys[] =
            [
                'name' => $prefix."customers_ibfk_3",
                'table' => $prefix."customers",
                'column' => "address_id",
                'to_table' => $prefix."addresses",
                'to_column' => "address_id",
                'delete_rule' => "SET NULL",
                'update_rule' => "CASCADE"
            ];


//date_deleted & stage_id

        if($this->table('order_downloads_history')->hasColumn('time')) {
            $update = "ALTER TABLE `".$prefix."order_downloads_history`
                       CHANGE COLUMN `time` `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP;";
            $this->execute($update);
        }



        $tables_without_stage = [
            'order_totals',
            'addresses',
            'order_downloads_history',
            'order_options',
            'order_products',
            'orders',

        ];
        $tables = [
            'banner_descriptions',
            'banners',
            'block_descriptions',
            'block_layouts',
            'block_templates',
            'blocks',
            'categories',
            'content_descriptions',
            'coupons',
            'currencies',
            'custom_blocks',
            'custom_lists',
            'customer_communications',
            'customer_notes',
            'customer_notifications',
            'customer_transactions',
            'customers',
            'downloads',
            'extensions',
            'global_attributes_type_descriptions',
            'jobs',
            'language_definitions',
            'layouts',
            'length_classes',
            'locations',
            'messages',
            'order_data',
            'order_data_types',
            'order_downloads',
            'order_history',
            'page_descriptions',
            'pages',
            'product_discounts',
            'product_specials',
            'products',
            'resource_descriptions',
            'resource_library',
            'resource_map',
            'reviews',
            'settings',
            'task_details',
            'task_steps',
            'tasks',
            'tax_classes',
            'tax_rates',
            'user_groups',
            'user_notifications',
            'users',
            'weight_classes',
            'category_descriptions',
            'contents',
            'countries',
            'country_descriptions',
            'coupon_descriptions',
            'customer_groups',
            'download_descriptions',
            'encryption_keys',
            'field_descriptions',
            'field_values',
            'fields',
            'fields_group_descriptions',
            'fields_groups',
            'form_descriptions',
            'form_groups',
            'forms',
            'global_attributes',
            'global_attributes_descriptions',
            'global_attributes_groups',
            'global_attributes_groups_descriptions',
            'global_attributes_types',
            'global_attributes_value_descriptions',
            'languages',
            'length_class_descriptions',
            'weight_class_descriptions',
            'manufacturers',
            'order_status_descriptions',
            'order_statuses',
            'product_descriptions',
            'product_option_descriptions',
            'product_options',
            'product_option_value_descriptions',
            'product_option_values',
            'product_tags',
            'resource_types',
            'stock_statuses',
            'store_descriptions',
            'stores',
            'tax_class_descriptions',
            'tax_rate_descriptions',
            'url_aliases',
            'zone_descriptions',
            'zones'
        ];

        foreach(array_merge($tables, $tables_without_stage) as $table_name){
            $table = $this->table($table_name);
            if(!$table->hasColumn('date_added')){
                $table->addColumn( 'date_added', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'] );
            }
            if(!$table->hasColumn('date_modified')){
                $table->addColumn(
                    'date_modified',
                    'timestamp',
                    ['default' => 'CURRENT_TIMESTAMP', 'update'  => 'CURRENT_TIMESTAMP']);
            }
            if(!$table->hasColumn('date_deleted')){
                $table->addColumn( 'date_deleted', 'timestamp');
            }
            if( !in_array($table_name,$tables_without_stage) && !$table->hasColumn('stage_id')){
                $table->addColumn( 'stage_id', 'integer');
                $table->addIndex(['stage_id']);
            }
            $table->save();
        }




        //create Foreign keys
        foreach($FKeys as $FK){
            $exists = $this->fetchAll(
                "SELECT cu.TABLE_NAME, cu.COLUMN_NAME, cu.CONSTRAINT_NAME, cu.REFERENCED_TABLE_NAME, 
                        cu.REFERENCED_COLUMN_NAME, tc.UPDATE_RULE, tc.DELETE_RULE 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu
                INNER JOIN information_schema.COLUMNS c 
                	ON ( cu.COLUMN_NAME = c.COLUMN_NAME 
                		AND c.TABLE_SCHEMA = cu.REFERENCED_TABLE_SCHEMA
                		AND c.TABLE_NAME = cu.TABLE_NAME
                    )
                LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS tc
                	ON (cu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME AND UNIQUE_CONSTRAINT_SCHEMA = '".$db_name."' )
                WHERE cu.REFERENCED_TABLE_SCHEMA = '".$db_name."' 
                   AND cu.TABLE_NAME = '".$FK['table']."'
                   AND cu.COLUMN_NAME = '".$FK['column']."'
                   AND cu.REFERENCED_TABLE_NAME='".$FK['to_table']."'
                   AND cu.REFERENCED_COLUMN_NAME = '".$FK['to_column']."';"
            );
            if($exists){ continue; }

            if(!$this->table($FK['table'])->hasForeignKey($FK['column'], $FK['name'])) {
                $sql = "ALTER TABLE `".$FK['table']."`
                        ADD CONSTRAINT `".$FK['name']."`
                          FOREIGN KEY (`".$FK['column']."`)
                          REFERENCES `".$FK['to_table']."` (`".$FK['to_column']."`)
                          ON DELETE ".$FK['delete_rule']." ON UPDATE ".$FK['update_rule'].";";
                try {
                    $this->execute($sql);
                }catch(PDOException $e){
                    echo $e->getMessage();
                    var_Dump($e->errorInfo);
                    var_Dump($sql);
                }
            }
        }


    }

    public function down()
    {

    }
}