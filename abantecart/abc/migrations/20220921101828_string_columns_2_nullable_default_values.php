<?php
/**
* AbanteCart auto-generated migration file
*/


use abc\core\ABC;
use Phinx\Migration\AbstractMigration;

class StringColumns2NullableDefaultValues extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        $dbName = \abc\core\engine\Registry::db()->getDatabaseName();
        $tables = $this->fetchAll(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '".$dbName."' AND TABLE_NAME LIKE 'tims_%';"
        );

        foreach ($tables as $table) {

            $cols = $this->fetchAll(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = '".$dbName."' AND TABLE_NAME = '".$table['TABLE_NAME']."'
                    AND IS_NULLABLE = 'NO' AND DATA_TYPE = 'varchar' AND COLUMN_DEFAULT IS NULL;"
            );
            foreach($cols as $col) {
                //remove key and add new columns
                $update = "ALTER TABLE `" . $table['TABLE_NAME'] . "` 
                            MODIFY `" . $col['COLUMN_NAME'] . "` ".$col['COLUMN_TYPE']."  NULL;\n";
                $this->execute($update);
            }
        }
    }

    public function down()
    {}
}