<?php
/**
* AbanteCart auto-generated migration file
*/


use abc\core\engine\Registry;
use Phinx\Migration\AbstractMigration;

class IntColumnsDefaultValues extends AbstractMigration
{

    public function up()
    {
        $dbName = Registry::db()->getDatabaseName();
        $tables = $this->fetchAll(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '".$dbName."' AND TABLE_NAME LIKE 'tims_%';"
        );

        foreach ($tables as $table) {

            $cols = $this->fetchAll(
                "SELECT a.*
                FROM INFORMATION_SCHEMA.COLUMNS as a
                LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE b
                    ON (a.TABLE_NAME = b.TABLE_NAME AND b.TABLE_SCHEMA = '".$dbName."' 
                         AND b.COLUMN_NAME = a.COLUMN_NAME AND REFERENCED_TABLE_SCHEMA = '".$dbName."')
                WHERE b.REFERENCED_COLUMN_NAME IS NULL
                    AND a.TABLE_NAME = '".$table['TABLE_NAME']."'
                    AND a.EXTRA<>'auto_increment' 
                    AND a.TABLE_SCHEMA = '".$dbName."'
                    AND a.IS_NULLABLE = 'NO' 
                    AND a.DATA_TYPE = 'int' 
                    AND a.COLUMN_DEFAULT IS NULL
                    AND a.COLUMN_KEY <> 'PRI'"
            );
            foreach($cols as $col) {
                //remove key and add new columns
                $update = "ALTER TABLE `" . $table['TABLE_NAME'] . "` 
                            MODIFY `" . $col['COLUMN_NAME'] . "` ".$col['COLUMN_TYPE']."  DEFAULT 0;\n";
                $this->execute($update);
            }
        }
    }

    public function down()
    {

    }
}