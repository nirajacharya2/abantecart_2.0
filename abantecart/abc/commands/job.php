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

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AException;
use abc\core\lib\AJobManager;
use abc\modules\AModuleBase;
use Error;
use Exception;

include_once('base/BaseCommand.php');

class Job extends BaseCommand
{
    public $errors = [];

    public function validate(string $action, array $options)
    {
        $action = !$action ? 'run' : $action;
        //if now options - check action
        if (!$options) {
            if (!in_array($action, array('help', 'run', 'consumer'))) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        return [];
    }

    public function run(string $action, array $options)
    {
        parent::run($action, $options);
        $result = false;
        if (!in_array($action, array('run', 'consumer')) || !$options) {
            return ['Error: Unknown action.'];
        }

        if ($action == 'run') {
            if (isset($options['job-id'])) {
                $result = $this->runJobById($options['job-id']);
            } elseif (isset($options['next-job'])) {
                $result = $this->runNextJob();
            } elseif (isset($options['worker'])) {
                $result = $this->runWorker($options);
            } else {
                $this->errors[] = 'Incorect options to run the job!';
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
        $class_name = ABC::getFullClassName('AJobManager');

        /**
         * @var AJobManager $handler
         */
        $handler = AHelperUtils::getInstance($class_name, ['registry' => Registry::getInstance()]);
        $job_info = $handler->getJobById($job_id);
        if (!$job_info
            || !$job_info['configuration']
            || !$job_info['configuration']['worker']
            || !$job_info['configuration']['worker']['file']
        ) {
            $this->errors[] = 'Job ID '.$job_id.' not found or have incorrect configuration!';
            return false;
        }
        if ($job_info['status'] === $handler::STATUS_DISABLED) {
            $this->errors[] = 'Cannot to run disabled Job! To run please change Job status to "Ready" first.';
            return false;
        }

        $result = false;
        try {
            require_once ABC::env('DIR_WORKERS').'WorkerInterface.php';
            require_once ABC::env('DIR_WORKERS').'BaseWorker.php';
            require_once $job_info['configuration']['worker']['file'];
            //run worker
            $worker_class = $job_info['configuration']['worker']['class'];
            /**
             * @var AModuleBase $worker_module
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
                $result = $worker_module->runJob(
                    $job_info['configuration']['worker']['method'],
                    $job_info['configuration']['worker']['parameters']
                );
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
        } catch (Error $e) {
            $handler->updateJob(
                $job_id,
                [
                    'status'      => $handler::STATUS_FAILED,
                    'last_result' => 0,

                ]
            );
            $this->errors[] = $e->getMessage().PHP_EOL.$e->getTraceAsString();
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

    protected function runNextJob()
    {
        //get job from queue
        $class_name = ABC::getFullClassName('AJobManager');
        /**
         * @var AJobManager $handler
         */
        $handler = AHelperUtils::getInstance($class_name, ['registry' => Registry::getInstance()]);
        $job_info = $handler->getReadyJob();
        if ($job_info) {
            return $this->runJobById($job_info['job_id']);
        } else {
            $this->write('No job found for processing!');
            return true;
        }
    }

    protected function queueConsume()
    {
    }

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

        $worker_args = ABC::getClassDefaultArgs($options['worker']);
        $result = false;
        try {
            require_once ABC::env('DIR_WORKERS').'WorkerInterface.php';
            require_once ABC::env('DIR_WORKERS').'BaseWorker.php';
            /**
             * @var AModuleBase $worker
             */
            $worker = AHelperUtils::getInstance($worker_class_name, $worker_args);

            if (!$worker instanceof AModuleBase) {
                throw new AException('Class  "'.$worker_class_name.'" is not not worker!');
            }
            //check methods/ If method not set - try to find "main"
            $run_method = $options['method'];
            $run_method = !$run_method ? 'main' : $run_method;
            $methods = $worker->getModuleMethods();
            if (!in_array($run_method, $methods)) {
                throw new AException('Cannot to find method '.$run_method.' of worker class'.$worker_class_name.'!');
            }
            $result = call_user_func([$worker, $run_method], $worker_args);
            if (!$result) {
                $this->errors = array_merge($this->errors, $worker->errors);
            }
        } catch (AException $e) {
            $this->errors[] = $e->getMessage();
        }

        return $result;
    }

    public function finish(string $action, array $options)
    {
        parent::finish($action, $options);
        $this->write("Finished processing job.");
    }

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