<?php

namespace abc\core\lib;

use abc\core\engine\Registry;
use stdClass;

/**
 * Class BaseReport
 *
 * @package abc\modules\reports
 */
class BaseReport
{
    /**
     * @var \abc\core\lib\ALanguageManager
     */
    protected $language;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \abc\core\lib\ADB
     */
    protected $db;

    /**
     * BaseReport constructor.
     *
     */
    public function __construct()
    {
        $this->registry = Registry::getInstance();
        $this->language = $this->registry->get('language');
        $this->db = $this->registry->get('db');
    }

    public function getGridSortOrder()
    {
        return 'asc';
    }

    public function exportCSV($report, $get, $post, $export)
    {
        header('Content-type: text/csv');
        header("Content-Disposition: attachment; filename={$report}-".date('YmdHis').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        $this->getGridData($get, $post, $export)->chunk(1000, function($rows) use (&$output) {
            foreach ($rows as $row) {
                if ($row instanceof stdClass) {
                    fputcsv($output, json_decode(json_encode($row), true));
                } else {
                    fputcsv($output, $row->toArray());
                }
            }
        });

        fclose($output);

    }

}
