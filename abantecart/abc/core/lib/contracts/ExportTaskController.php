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
     * @param int $taskId
     * @param int $stepId
     * @param array $settings
     *
     * @return mixed
     */
    public function export($taskId, $stepId, $settings = []);
}