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
     * @param int $task_id
     * @param int $step_id
     * @param array $settings
     *
     * @return mixed
     */
    public function export($task_id, $step_id, $settings = []);
}