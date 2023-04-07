<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class AddIncentivesAppliedTable extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        $table = $this->table('incentive_applied');
        if (!$table->exists()) {
            $this->execute(
                "CREATE TABLE `tims_incentive_applied` (
                            `incentive_id` int(11) NOT NULL,
                            `customer_id` int(11) NOT NULL,
                            `result_code` smallint(6) NOT NULL DEFAULT '0',
                            `result` text NOT NULL,
                            `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`incentive_id`,`customer_id`,`date_added`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );
        }
    }

    public function down()
    {
    }
}