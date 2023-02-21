<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class OrderShippingCompanyLength extends AbstractMigration
{

    public function up()
    {
        // create the table
        $tableAdapter = new Phinx\Db\Adapter\TablePrefixAdapter($this->getAdapter());

        $sql = "alter table " . $tableAdapter->getAdapterTableName('orders') . "
                    modify shipping_company varchar(64) null;";
        $this->execute($sql);
        $sql = "alter table " . $tableAdapter->getAdapterTableName('orders') . "
                    modify payment_company varchar(64) null;";
        $this->execute($sql);
    }

    public function down()
    {

    }
}