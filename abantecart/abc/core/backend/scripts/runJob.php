<?php

namespace abc\core\backend;

use abc\core\ABC;
use abc\controllers\admin\ControllerPagesToolCache;
use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AConfig;
use abc\core\lib\AConnect;
use abc\core\lib\AContentManager;
use abc\core\lib\ACurrency;
use abc\core\lib\AJobManager;
use abc\core\lib\ALanguageManager;
use abc\models\admin\ModelSettingStore;
use abc\models\admin\ModelToolInstallUpgradeHistory;
use ReflectionClass;

class RunJob implements ABCExec
{
    public $errors = [];
    protected $results = [];
    protected $languages = [];
    protected $currencies = [];
    /**
     * @var AConnect
     */
    protected $connect;

    public function validate(string $action, array $options)
    {
        $action = ! $action ? 'run' : $action;
        //if now options - check action
        if ( ! $options) {
            if ( ! in_array($action, array('help', 'run'))) {
                return ['Error: Unknown Action Parameter!'];
            }
        }

        return [];
    }

    public function run(string $action, array $options)
    {
        $output = null;
        $result = false;
        if ( ! in_array($action, array('run')) || ! $options) {
            return ['Error: Unknown action.'];
        }
        //looking for "ALL" parameter in option set. If presents - skip other.
        $opt_list = $this->getOptionList();

        foreach (array_keys($options) as $cache_section) {
            $alias = $opt_list[$action]['arguments']['--'.$cache_section]['alias'];
            if ( ! $alias) {
                continue;
            }
            $cache_groups = explode(',', $alias);
            $cache_groups = array_map('trim', $cache_groups);

            if ($action == 'run') {
                $result = $this->_process_clear($cache_groups);
                if(isset($options['job-id'])){
                    $this->runJobById($options['job-id']);
                }elseif(isset($options['next-job'])){
                    $this->runNexJob();
                }
            }
        }

        return $result ? true : $this->errors;
    }

    protected function runJobById($job_id)
    {

        $class_name = ABC::getFullClassName('AJobManager');

        /**
         * @var AJobManager $handler
         */
        $handler = AHelperUtils::getInstance($class_name,['registry'=>Registry::getInstance()]);
        $job_info = $handler->getJobById($job_id);

        ///TODO RUN worker
        include_once $job_info['worker']['file'];
        if(class_exists($job_info['worker']['class'])){
            //run worker
            $reflector = new ReflectionClass( $job_info['worker']['class'] );
            $result = $reflector->newInstanceArgs( $job_info['worker']['parameters'] );

        }
        //// incomplete!!!


    }
    protected function runNexJob()
    {
        ///TODO call worker from queue

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
                            'alias'         => '*',
                        ],
                        '--next-job' => [
                            'description'   => 'Run next job from queue',
                            'default_value' => '',
                            'required'      => false,
                            'alias'         => '*',
                        ],
                    ],
                    'example'     => 'php abcexec runJob:run --job-id=1234',
                ]
        ];
    }
}