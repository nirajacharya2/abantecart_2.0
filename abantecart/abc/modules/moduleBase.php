<?php

namespace abc\modules;

use Exception;
use \PhpAmqpLib\Message\AMQPMessage as AMQPMessage;

abstract class AModuleBase
{
    public $errors = [];
    public $run_log = [];
    public $hasError;
    protected $reRunIfFailed = false;

    abstract public function getModuleMethods();

    /**
     * Starting worker`s method for processing incoming jobs
     *
     * @param string              $method
     * @param array | AMQPMessage $job_params
     *
     * @return bool
     */
    public function runJob($method, $job_params)
    {
        $result = false;
        $this->echoCli('****************************************************************');
        $this->echoCli($this->getTime() . "- Starting worker ");


        /**
         * Requesting method to call
         */
        if (method_exists($this, $method) && in_array($method, $this->getModuleMethods())) {
            $run_parameters = [];
            if(is_array($job_params)){
                $run_parameters = $job_params;
            }elseif ($job_params instanceof AMQPMessage){
                /* TODO: finish this in the future
                $this->jobString = $job_params->body;
                $this->run_log[] = "Job configuration: " . $this->jobString;
                $this->job = json_decode($job_params->body);
                $this->jobInit();
                */
            }

            $this->echoCli('****************************************************************');
            $this->echoCli('calling method: '.$method);

            try {
                /** @var boolean $result */
                //run worker
                $result = call_user_func([$this, $method],$run_parameters);
            } catch (Exception $e) {
                $this->echoCli('!!!!!!!!!!! Exception !!!!!!!!!!!!!');
                $error_message = 'Message: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                $this->errors[] = $error_message;
                $this->echoCli($error_message);
            }
            $this->echoCli('****************************************************************');
            if ($result !== true) {
                $this->hasError = true;
            }
            $this->postProcessing();

        }
        return $result;
    }

    /**
     * @return string
     */
    public static function getTime()
    {
        return date("Y-m-d H:i:s", time());
    }

    public function echoCli($text)
    {
        $this->run_log[] = $text;
        echo $text."\n";
    }

    public function isReRunAllowed(){
        return $this->reRunIfFailed;
    }

    abstract public function postProcessing();

}