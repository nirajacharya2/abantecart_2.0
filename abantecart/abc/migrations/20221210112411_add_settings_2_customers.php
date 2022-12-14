<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class AddSettings2Customers extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('customers');
        if (!$table->hasColumn('settings')) {
            $tableAdapter = new Phinx\Db\Adapter\TablePrefixAdapter($this->getAdapter());
            $full_table_name = $tableAdapter->getAdapterTableName('customers');
            $sql = "ALTER TABLE `" . $full_table_name . "` 
                ADD COLUMN `settings` MEDIUMTEXT COMMENT 'php-serialized stretch data' AFTER `last_login`;";
            $this->query($sql);
        }

    }

    public function down()
    {

    }
}