<?php


use Phinx\Migration\AbstractMigration;

class OrderTotalsDataColumnExpand extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        $table = $this->table('order_totals');
        if (!$table->hasColumn('data')) {
            $table->addColumn('data', 'text', ['limit' => 1500, 'after' => 'value'])->update();
        }
        $tableAdapter = new Phinx\Db\Adapter\TablePrefixAdapter($this->getAdapter());
        $full_table_name = $tableAdapter->getAdapterTableName('order_totals');
        $sql = "ALTER TABLE `" . $full_table_name . "` MODIFY `data` MEDIUMTEXT";
        $this->query($sql);
    }

    public function down()
    {

    }
}