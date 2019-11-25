<?php

namespace abc\core\lib\contracts;

/**
 * Interface ExportTaskController
 *
 * @package abc\core\lib\contracts
 */
interface ExportTaskController
{
    /**
     * @param array $params
     *
     * @return int
     */
    public function getCount(array $params);

    /**
     * @return mixed
     */
    public function export();
}