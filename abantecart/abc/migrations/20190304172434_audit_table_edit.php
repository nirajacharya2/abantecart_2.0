<?php
/**
* AbanteCart auto-generated migration file
*/


use Phinx\Migration\AbstractMigration;

class AuditTableEdit extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        // create the table
        $table = $this->table('audits');
        if(!$table->hasColumn('main_auditable_model')) {
            $table->addColumn('main_auditable_model', 'string', ['null' => true, 'after' => 'session_id']);
        }
        if(!$table->hasColumn('main_auditable_id')) {
            $table->addColumn('main_auditable_id', 'integer', ['null' => true, 'after' => 'main_auditable_model']);
        }

        if($table->hasColumn('auditable_type')) {
            $table->renameColumn('auditable_type', 'auditable_model');
        }

        $table->save();
    }

    public function down()
    {

    }
}