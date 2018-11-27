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

    }

    public function down()
    {

    }
}