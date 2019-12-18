<?php
/**
 * AbanteCart auto-generated migration file
 */

use abc\core\lib\AMenu;
use abc\core\lib\AResourceManager;
use Phinx\Migration\AbstractMigration;

class CreateEmailTemplatesTable extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        $table = $this->table('email_templates');
        if (!$table->exists()) {
            $table->addColumn('status', 'boolean')
                ->addColumn('text_id', 'string')
                ->addColumn('language_id', 'integer')
                ->addColumn('headers', 'string')
                ->addColumn('subject', 'string')
                ->addColumn('html_body', 'text')
                ->addColumn('text_body', 'text')
                ->addColumn('allowed_placeholders', 'text')
                ->addColumn('date_added', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('date_modified', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addColumn('date_deleted', 'timestamp', ['null' => true])
                ->addIndex(['text_id', 'language_id'], [
                    'unique' => true,
                    'name'   => 'email_templates_text_id_idx',
                ])
                ->create();
        }

        //create menuItem
        $rm = new AResourceManager();
        $rm->setType('image');

        $language_id = 1;
        $data = [];
        $data['resource_code'] = '<i class="fa fa-mail-bulk"></i>&nbsp;';
        $data['name'] = [$language_id => 'Menu Icon Email Templates'];
        $data['title'] = [$language_id => ''];
        $data['description'] = [$language_id => ''];
        $resource_id = $rm->addResource($data);

        $menu = new AMenu ('admin');
        $menu->insertMenuItem([
                'item_id'         => 'email_templates',
                'parent_id'       => 'design',
                'item_text'       => 'email_templates',
                'item_url'        => 'design/email_templates',
                'item_icon_rl_id' => $resource_id,
                'item_type'       => 'core',
                'sort_order'      => '30',
            ]
        );
    }

    public function down()
    {
         $table = $this->table('email_templates');
         if($table->exists()) {
             $table->drop()->save();
         }
        $menu = new AMenu ('admin');
         $menu->deleteMenuItem('email_templates');
    }
}