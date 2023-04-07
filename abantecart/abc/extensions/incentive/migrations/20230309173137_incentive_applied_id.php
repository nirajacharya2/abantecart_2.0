<?php
/**
 * AbanteCart auto-generated migration file
 */


use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use Phinx\Migration\AbstractMigration;

class IncentiveAppliedId extends AbstractMigration
{
    /**
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     */

    public function up()
    {
        // create the table

        $tableData = \abc\core\engine\Registry::db()->query('SELECT * FROM tims_incentive_applied');

        $this->execute("rename table tims_incentive_applied to tims_incentive_applied_old;");

        $this->execute(
            "CREATE TABLE `tims_incentive_applied` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `incentive_id` int(11) NOT NULL,
                `customer_id` int(11) NOT NULL,
                `result_code` smallint(6) NOT NULL DEFAULT '0' COMMENT '0 - success, 1- fail',
                `result` text NOT NULL,
                `bonus_amount` decimal(15,4) NOT NULL DEFAULT 0.0,
                `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX (`incentive_id`,`customer_id`,`date_added`, `bonus_amount`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

        $table = $this->table('incentive_applied');

        if ($tableData->rows) {
            $table->insert($tableData->rows)->saveData();
        }

        $sql = "drop table tims_incentive_applied_old;";
        $this->execute($sql);


        $db = Registry::db();
        $lang = new ALanguage(Registry::getInstance(), 'en');
        $dbLangDef = $lang->getASet('incentive/incentive');
        $dbKeys = array_keys($dbLangDef);
        $xmlLangDef = [
            'incentive_name_applied'          => 'Applied Promotions',
            'incentive_text_customer'         => 'Customer',
            'incentive_text_incentive'        => 'Promotion',
            'incentive_text_bonus_amount'     => 'Bonus Amount',
            'incentive_text_date'             => 'Date',
            'incentive_text_result'           => 'Result',
            'incentive_text_all_incentives'   => 'All Promotions',
            'incentive_text_match_conditions' => 'Matched Conditions',
            'incentive_text_match_items'      => 'Matched Items'
        ];
        $changed = array_keys($xmlLangDef);
        foreach ($xmlLangDef as $k => $v) {
            if (!in_array($k, $dbKeys)) {
                $sql = "INSERT INTO tims_language_definitions 
                            (language_id, section, block,language_key, language_value)
                    VALUES (1, 1,'incentive_incentive', '" . $db->escape($k) . "','" . $db->escape($v) . "')";
                $db->query($sql);
            } elseif (in_array($k, $changed)) {
                $sql = "UPDATE tims_language_definitions
                        SET  language_value = '" . $db->escape($v) . "'
                        WHERE language_key = '" . $db->escape($k) . "' 
                            AND block = 'incentive_incentive' 
                            AND section = 1
                            AND language_id=1";
                $db->query($sql);
            }
        }


    }

    public function down()
    {
    }
}