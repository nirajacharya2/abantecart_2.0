<?php

namespace abc\core\backend;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AException;
use abc\core\lib\AJobManager;
use abc\modules\AModuleBase;
use Error;
use Exception;

class Job implements ABCExec
{
    public $errors = [];


    public function validate(string $action, array $options)
    {
        $action = ! $action ? 'run' : $action;
        //if now options - check action
        if ( ! $options) {
            if ( ! in_array($action, array('help', 'run', 'consumer'))) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        return [];
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $result = false;
        if ( ! in_array($action, array('run','consumer')) || ! $options) {
            return ['Error: Unknown action.'];
        }

        if ($action == 'run') {
            if(isset($options['job-id'])){
                $result = $this->runJobById($options['job-id']);
            }elseif(isset($options['next-job'])){
                $result = $this->runNextJob();
            }
        }elseif($action == 'consumer'){
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
        $handler = AHelperUtils::getInstance($class_name,['registry'=>Registry::getInstance()]);
        $job_info = $handler->getJobById($job_id);
        if(!$job_info
            || !$job_info['configuration']
            || !$job_info['configuration']['worker']
            || !$job_info['configuration']['worker']['file']
        ){
            $this->errors[] = 'Job ID '.$job_id.' not found or have incorrect configuration!';
            return false;
        }
        if($job_info['status'] === $handler::STATUS_DISABLED){
            $this->errors[] = 'Cannot to run disabled Job! To run please change Job status to "Ready" first.';
            return false;
        }

        $result = false;
        try{
            require_once ABC::env('DIR_MODULES').'moduleInterface.php';
            require_once ABC::env('DIR_MODULES').'moduleBase.php';
            require_once $job_info['configuration']['worker']['file'];
            //run worker
            $worker_class = $job_info['configuration']['worker']['class'];
            /**
             * @var AModuleBase $worker_module
             */
            $worker_module  = new $worker_class();

            if($job_info['status'] == $handler::STATUS_READY || $worker_module->isReRunAllowed()) {
                $handler->updateJob($job_id,['status' => $handler::STATUS_RUNNING]);
                $result = $worker_module->runJob(
                    $job_info['configuration']['worker']['method'],
                    $job_info['configuration']['worker']['parameters']
                    );
            }else{
                $this->errors[] = 'Rerun of Job forbidden by worker module '
                    .$worker_class.'! Please change Job status to "Ready" first.';
            }

            if (!$result) {

                $handler->updateJob($job_id,['status' => $handler::STATUS_FAILED]);
                $this->errors = array_merge($this->errors, $worker_module->errors);
            }else{
                $handler->updateJob($job_id,['status' => $handler::STATUS_COMPLETED]);
            }
        }catch(Error $e){
            $handler->updateJob($job_id,['status' => $handler::STATUS_FAILED]);
            $this->errors[] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
        }catch(Exception $e){
            $handler->updateJob($job_id,['status' => $handler::STATUS_FAILED]);
            $this->errors[] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
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
        $handler = AHelperUtils::getInstance($class_name,['registry'=>Registry::getInstance()]);
        $job_info = $handler->getReadyJob();
        if($job_info) {
            return $this->runJobById($job_info['job_id']);
        }else{
            $this->errors[] = 'Ready Jobs not found.';
            return false;
        }

    }
    protected function queueConsume()
    {


    }


    public function finish(string $action, array $options)
    {
        $output = "Success: Job have been successfully processed.\n";
        return $output;
    }

    public function help( $options = [] )
    {

        return $this->getOptionList();
    }

    protected function getOptionList()
    {
        return [
            'run' =>
                [
                    'description' => 'run scheduled background job',
                    'arguments'   => [
                        '--job-id'   => [
                            'description'   => 'Job ID from database table "jobs"',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*'
                        ],
                        '--next-job' => [
                            'description'   => 'Run next job from queue',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*'
                        ]
                    ],
                    'example'     => 'php abcexec runJob:run --job-id=1234'
                ],
            'consumer' =>
                [
                    'description' => 'run queue consumer',
                    'arguments'   => [],
                    'example'     => 'php abcexec runJob:consumer'
                ],

        ];
    }
}