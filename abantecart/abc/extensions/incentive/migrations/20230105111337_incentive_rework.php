<?php

use Phinx\Migration\AbstractMigration;

class IncentiveRework extends AbstractMigration
{

    public function up()
    {

        $tableAdapter = new Phinx\Db\Adapter\TablePrefixAdapter($this->getAdapter());
        $full_table_name = $tableAdapter->getAdapterTableName('incentives');

        $sql = "alter table " . $full_table_name . "
                    modify conditions longtext null;";
        $this->execute($sql);
        $sql = "alter table " . $full_table_name . "
                    modify bonuses longtext null;";
        $this->execute($sql);
        $sql = "alter table " . $full_table_name . "
                    modify stop smallint default 0 not null;";
        $this->execute($sql);
        $sql = "alter table " . $full_table_name . "
                    modify conditions_hash text null;";
        $this->execute($sql);
        $sql = "alter table " . $full_table_name . "
                    modify status smallint default 0 not null;";
        $this->execute($sql);
        $sql = "alter table " . $full_table_name . "
                    alter column number_of_usages set default 0;";
        $this->execute($sql);
        $sql = "alter table " . $full_table_name . "
                    modify users_conditions_hash text null;";
        $this->execute($sql);

        $full_table_name = $tableAdapter->getAdapterTableName('incentive_descriptions');
        $sql = "alter table " . $full_table_name . "
                    modify description_short text null;";
        $this->execute($sql);
        $sql = "alter table " . $full_table_name . "
                    modify description mediumtext null;";
        $this->execute($sql);

        $full_table_name = $tableAdapter->getAdapterTableName('incentive_applied');
        $sql = "alter table " . $full_table_name . "
                    modify result text null;";
        $this->execute($sql);

        $row = $this->fetchRow(
            "SELECT * FROM " . $tableAdapter->getAdapterTableName('order_data_types') . " WHERE name = 'incentive_data'"
        );

        if (!$row) {
            $table = $this->table('order_data_types');

            // inserting only one row
            $singleRow = [
                'language_id' => 1,
                'name'        => 'incentive_data',
                'date_added'  => date('Y-m-d H:i:s')
            ];

            $table->insert($singleRow)->saveData();
        }

    }

    public function down()
    {

    }
}