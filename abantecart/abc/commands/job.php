<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\commands;

use abc\commands\base\BaseCommand;
use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\JobManager;
use abc\modules\workers\ABaseWorker;
use Exception;
use H;

/**
 * Class Job
 *
 * @package abc\commands
 */
class Job extends BaseCommand
{
    public $errors = [];

    public function validate(string $action, array &$options)
    {
        $action = !$action ? 'run' : $action;
        //if now options - check action
        if (!$options) {
            if (!in_array($action, ['help', 'run', 'consumer'])) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        return [];
    }

    public function run(string $action, array $options)
    {
        parent::run($action, $options);
        $result = false;
        if (!in_array($action, ['run', 'consumer']) || !$options) {
            return ['Error: Unknown action.'];
        }

        require_once ABC::env('DIR_WORKERS') . 'AWorkerInterface.php';
        require_once ABC::env('DIR_WORKERS') . 'ABaseWorker.php';

        if ($action == 'run') {
            if (isset($options['job-id'])) {
                $result = $this->runJobById($options['job-id']);
            } elseif (isset($options['next-job'])) {
                $result = $this->runNextJob();
            } elseif (isset($options['next-jobs'])) {
                $result = $this->runNextJobs();
            } elseif (isset($options['worker'])) {
                $result = $this->runWorker($options);
            } else {
                $this->errors[] = 'Incorrect options to run the job!';
                $result = false;
            }
        } elseif ($action == 'consumer') {
            $result = $this->queueConsume();
        }

        return $result ? true : $this->errors;
    }

    /**
     * @param mixed $job_id
     *
     * @return bool
     * @throws AException
     */
    protected function runJobById($job_id)
    {
        $class_name = ABC::getFullClassName('JobManager');

        /**
         * @var JobManager $handler
         */
        $handler = H::getInstance($class_name, ['registry' => Registry::getInstance()]);
        $job_info = $handler->getJobById($job_id);
        if (!$job_info
            || !$job_info['configuration']
            || !$job_info['configuration']['worker']
            || !$job_info['configuration']['worker']['file']
        ) {
            $this->errors[] = 'Job ID '.$job_id.' not found or have incorrect configuration!';
            return false;
        }

        if ($job_info['status'] !== $handler::STATUS_READY) {
            $this->errors[] = 'Cannot to run Job! To re-run please change Job status to "Ready" first.';
            return true;
        }

        $result = false;
        try {
            require_once $job_info['configuration']['worker']['file'];
            //run worker
            $worker_class = $job_info['configuration']['worker']['class'];
            /**
             * @var ABaseWorker $worker_module
             */
            $worker_module = new $worker_class();

            if ($job_info['status'] == $handler::STATUS_READY || $worker_module->isReRunAllowed()) {
                $handler->updateJob(
                    $job_id,
                    [
                        'status'        => $handler::STATUS_RUNNING,
                        'last_time_run' => date("Y-m-d H:i:s", time()),
                    ]
                );
                $this->write(
                    "Worker Configuration:\n".var_export($job_info['configuration']['worker'], true)
                );
                $result = $worker_module->runJob(
                    $job_info['configuration']['worker']['method'],
                    $job_info['configuration']['worker']['parameters']
                );
                //pass workers output to command
                if ($worker_module->output) {
                    $this->write($worker_module->output);
                }
            } else {
                $this->errors[] = 'Rerun of Job forbidden by worker module '
                    .$worker_class.'! Please change Job status to "Ready" first.';
            }

            if (!$result) {
                $handler->updateJob(
                    $job_id,
                    [
                        'status'      => $handler::STATUS_FAILED,
                        'last_result' => 0,

                    ]
                );
                $this->errors = array_merge($this->errors, $worker_module->errors);
            } else {
                $handler->updateJob($job_id, ['status' => $handler::STATUS_COMPLETED, 'last_result' => 1]);
            }
        } catch (Exception $e) {
            $handler->updateJob(
                $job_id,
                [
                    'status'      => $handler::STATUS_FAILED,
                    'last_result' => 0,

                ]
            );
            $this->errors[] = $e->getMessage().PHP_EOL.$e->getTraceAsString();
        }

        return $result ? true : false;
    }

    /**
     * @return bool
     * @throws AException
     */
    protected function runNextJob()
    {
        //get job from queue
        $class_name = ABC::getFullClassName('JobManager');
        /**
         * @var JobManager $handler
         */
        $handler = H::getInstance($class_name, ['registry' => Registry::getInstance()]);
        $job_info = $handler->getReadyJob();
        if ($job_info) {
            $this->write('Job found. Start processing job #'.$job_info['job_id'].'!');
            $this->runJobById($job_info['job_id']);
            if (!$this->errors) {
                $this->write(implode("\n", $this->errors));
            }
            return $this->runJobById($job_info['job_id']);
        } else {
            $this->write('No job found for processing!');
            return true;
        }
    }

    /**
     * @return bool
     * @throws AException
     */
    protected function runNextJobs()
    {
        //get job from queue
        $class_name = ABC::getFullClassName('JobManager');
        /**
         * @var JobManager $handler
         */
        $handler = H::getInstance($class_name, ['registry' => Registry::getInstance()]);
        $jobs = $handler->getReadyJobs();
        if ($jobs) {
            $jobs_info=[];
            foreach ($jobs as $job) {
                $this->write('Job found. Start processing job #'.$job['job_id'].'!');
                $this->runJobById($job['job_id']);
                if (!$this->errors) {
                    $this->write(implode("\n", $this->errors));
                }
                $jobs_info[] = $this->runJobById($job['job_id']);
            }
            return $jobs_info;
        }

        $this->write('No job found for processing!');
        return true;
    }

    /**
     *
     */
    protected function queueConsume()
    {
    }

    /**
     * @param $options
     *
     * @return bool
     */
    protected function runWorker($options)
    {
        if (!$options['worker']) {
            $this->errors[] = 'Empty worker alias given.';
            return false;
        }

        $worker_class_name = ABC::getFullClassName($options['worker']);
        if (!$worker_class_name) {
            $this->errors[] = 'Worker with alias name "'.$options['worker'].'" not found in the classmap!';
            return false;
        }

        $result = false;
        try {

            /**
             * @var ABaseWorker $worker
             */
            $worker = H::getInstance($worker_class_name, $options);

            if (!$worker instanceof ABaseWorker) {
                throw new AException('Class  "'.$worker_class_name.'" is not a worker class!');
            }
            //check methods/ If method not set - try to find "main"
            $run_method = $options['method'];
            $run_method = !$run_method ? 'main' : $run_method;
            $methods = $worker->getModuleMethods();
            if (!in_array($run_method, $methods)) {
                throw new AException('Cannot to find method '.$run_method.' of worker class '.$worker_class_name.'!');
            }
            $result = $worker->runJob(
                $run_method,
                $options
            );
            if ($worker->output) {
                $this->write($worker->output);
            }
            if (!$result) {
                $this->errors = array_merge($this->errors, $worker->errors);
            }
        } catch (AException $e) {
            $this->errors[] = $e->getMessage(). $e->getTraceAsString();
        }

        return $result;
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @return bool|void
     */
    public function finish(string $action, array $options)
    {
        $this->write("Finished processing job.");
        parent::finish($action, $options);
    }

    /**
     * @return array
     */
    protected function getOptionList()
    {
        return [
            'run'      =>
                [
                    'description' => 'run scheduled background job',
                    'arguments'   => [
                        '--job-id'   => [
                            'description'   => 'Job ID from database table "jobs"',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                        '--next-job' => [
                            'description'   => 'Run next job from queue',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                        '--worker'   => [
                            'description'   => 'Alias of worker class from config/classmap',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                        '--method'   => [
                            'description'   => 'Method of worker class which will be called.'
                                .' Used with --worker options',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                    ],
                    'example'     => 'php abcexec job:run --job-id=1234',
                ],
            'consumer' =>
                [
                    'description' => 'run queue consumer',
                    'arguments'   => [],
                    'example'     => 'php abcexec runJob:consumer',
                ],

        ];
    }
}
