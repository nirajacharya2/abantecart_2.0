<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class UrlAliasesDates extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        $table = $this->table('url_aliases');
        $table->changeColumn('date_added', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->changeColumn('date_modified', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->save();
    }

    public function down()
    {

    }
}