<?php
/**
* AbanteCart auto-generated migration file
*/

use Illuminate\Database\Schema\Blueprint;
use Phinx\Migration\AbstractMigration;

class AuditTableCreation extends AbstractMigration
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
        if(!$table->exists()) {
            $table
                ->addColumn( 'user_type', 'string', ['null' => true] )
                ->addColumn( 'user_id', 'integer', ['null' => true] )
                ->addColumn( 'user_name', 'string', ['null' => true] )
                ->addColumn( 'alias_id', 'integer', ['null' => true] )
                ->addColumn( 'alias_name', 'string', ['null' => true] )
                ->addColumn( 'event', 'string' )
                ->addColumn( 'request_id', 'string', ['null' => true] )
                ->addColumn( 'session_id', 'string', ['null' => true] )
                ->addColumn( 'auditable_type', 'string' )
                ->addColumn( 'auditable_id', 'integer', ['null' => true] )
                ->addColumn( 'attribute_name', 'string')
                ->addColumn( 'old_value', 'text', ['null' => true] )
                ->addColumn( 'new_value', 'text', ['null' => true] )
                ->addColumn( 'date_added', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'] )
                ->addIndex( ['user_id', 'user_type', 'user_name'])
                ->addIndex( ['request_id', 'session_id'])
                ->addIndex( ['auditable_type', 'auditable_id'])
                ->addIndex( ['attribute_name'])
                ->save();
        }
    }

    public function down()
    {
        $table = $this->table('audits');
        if($table->exists()) {
            $table->drop();
        }
    }
}