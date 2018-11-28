<?php

use Phinx\Migration\AbstractMigration;

class DatetimeColumnsChanges extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
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
                $update .= " ON UPDATE CURRENT_TIMESTAMP";
            }
            $this->execute($update);
        }

        $update = "ALTER TABLE ".$prefix."ant_messages
                   MODIFY COLUMN `viewed_date` timestamp NULL;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_discounts`
                   MODIFY COLUMN `date_start` date NULL;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_discounts`
                   MODIFY COLUMN `date_end` date NULL;";
        $this->execute($update);

        $update = "ALTER TABLE `".$prefix."product_specials`
                   MODIFY COLUMN `date_start` date NULL;";
        $this->execute($update);
        $update = "ALTER TABLE `".$prefix."product_specials`
                   MODIFY COLUMN `date_end` date NULL;";
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
        ADD CONSTRAINT `cba_product_option_value_descriptions_ibfk_3`
          FOREIGN KEY (`product_option_value_id`)
          REFERENCES `".$prefix."product_option_values` (`product_option_value_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE;";
        $this->execute($update);

    }

    public function down()
    {

    }
}