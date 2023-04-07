<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class IncentiveResource extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        // create the table
        $table = $this->table('incentives');
        if (!$table->hasColumn('resource_id')) {
            $table->addColumn('resource_id', 'integer', ['null' => true, 'after' => 'status'])
                ->addForeignKey(
                    'resource_id',
                    'resource_library',
                    'resource_id',
                    ['delete' => 'RESTRICT', 'update' => 'CASCADE']
                )->save();
        }
    }

    public function down()
    {
        $table = $this->table('incentives');
        if ($table->hasColumn('resource_id')) {
            $table->removeColumn('resource_id')->save();
        }
    }
}