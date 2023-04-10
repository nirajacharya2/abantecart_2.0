<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class FeaturedProductField extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        // create the table
        $table = $this->table('products');
        if (!$table->hasColumn('featured')) {
            $table->addColumn('featured', 'integer', ['after' => 'status', 'default' => 0])
                ->save();
        }
    }
}