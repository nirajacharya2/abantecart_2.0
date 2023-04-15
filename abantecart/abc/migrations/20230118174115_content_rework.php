<?php
/**
 * AbanteCart auto-generated migration file
 */


use Phinx\Migration\AbstractMigration;

class ContentRework extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {

        if ($this->table('content_descriptions')->hasForeignKey('content_id')) {
            return;
        }
        $sql = "update tims_content_descriptions SET name = title WHERE COALESCE(name,'') = '';";
        $this->query($sql);
        try {
            $sql = "alter table tims_contents  drop foreign key tims_contents_fk_1;";
            $this->query($sql);
        } catch (Exception $e) {
        }

        $sql = "alter table tims_contents modify content_id int null;";
        $this->query($sql);
        try {
            $sql = "drop index content_id on tims_contents;";
            $this->query($sql);
            $sql = "drop index stage_id on tims_contents;";
            $this->query($sql);
            $sql = "drop index tims_contents_fk_1_idx on tims_contents;";
            $this->query($sql);
        } catch (Exception $e) { }

        if ($this->table('contents')->hasColumn('parent_content_id')) {
            $sql = "alter table tims_contents change parent_content_id parent_id int null;";
            $this->query($sql);
        }

        try {
            $sql = "alter table tims_contents add constraint tims_contents_pk primary key (content_id);";
            $this->query($sql);
            $sql = "alter table tims_contents modify content_id int auto_increment;";
            $this->query($sql);
        } catch (Exception $e) { }
        try {
            $sql = "alter table tims_contents
                    add constraint tims_contents_tims_contents_content_id_fk
                        foreign key (parent_id) references tims_contents (content_id)
                            on update cascade on delete set null;";
            $this->query($sql);
        } catch (Exception $e) {
        }


        $sql = "alter table tims_contents modify content_id int auto_increment;";
        $this->query($sql);

        if( !$this->table('contents')->hasForeignKey('parent_id') ) {
            $sql = "alter table tims_contents
                    add constraint tims_contents_tims_contents_content_id_fk
                        foreign key (parent_id) references tims_contents (content_id)
                            on update cascade on delete set null;";
            $this->query($sql);
        }
    }

    public function down()
    {


    }
}