<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class AddPrimaryKeyToIncentiveDescription extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        $tableName = $this->table('tims_incentive_descriptions')->getName();
        $this->query('alter table ' . $tableName . ' drop primary key, add column `id` int NOT NULL AUTO_INCREMENT primary key;');
    }

    public function down()
    {
        /*
         $table = $this->table('table_name_with_prefix');
         if($table->exists()) {
             $table->drop();
         }
        */

    }
}