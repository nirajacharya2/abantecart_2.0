<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class IncentiveAppliedLongText extends AbstractMigration
{

    public function up()
    {
        $sql = "alter table tims_incentive_applied
                    modify result longtext null;";
        $this->execute($sql);
    }

    public function down()
    {

    }
}