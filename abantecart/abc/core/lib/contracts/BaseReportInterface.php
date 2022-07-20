<?php

namespace abc\core\lib\contracts;

/**
 * Interface BaseReportInterface
 *
 * @package abc\modules\reports
 */
interface BaseReportInterface
{
    /**
     * @return mixed
     */
    public function getName();

    /**
     * @return mixed
     */
    public function getGridSortName();

    /**
     * @return mixed
     */
    public function getGridSortOrder();

    /**
     * @return mixed
     */
    public function getGridColNames();

    /**
     * @return mixed
     */
    public function getGridColModel();

    /**
     * @param array $get
     * @param array $post
     *
     * @return mixed
     */
    public function getGridData(array $get, array $post, bool $export = null);

    public function exportCSV($report, $get, $post, $export);
}
