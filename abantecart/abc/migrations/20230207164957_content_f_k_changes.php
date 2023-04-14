<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class ContentFKChanges extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {

        try {
            $sql = "alter table tims_contents
                    drop constraint tims_contents_tims_contents_content_id_fk;";
            $this->query($sql);
        } catch (Exception $e) {
        }
        try {
            $sql = "alter table tims_contents
                    add constraint tims_contents_tims_contents_content_id_fk
                        foreign key (parent_id) references tims_contents (content_id)
                            on update cascade on delete cascade;";
            $this->query($sql);
        } catch (Exception $e) {
        }

        $sql = "DELETE FROM tims_contents_to_stores 
                WHERE content_id NOT IN (SELECT content_id FROM tims_contents)";
        $this->query($sql);
        try {
            $sql = "alter table tims_contents_to_stores
                    add constraint tims_contents_2_stores_id_fk
                        foreign key (content_id) references tims_contents (content_id)
                            on update cascade on delete cascade ;";
            $this->query($sql);
        } catch (Exception $e) {
        }
    }

    public function down()
    {

    }
}