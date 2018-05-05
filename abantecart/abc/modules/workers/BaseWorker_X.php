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

use application\components\InProgress;
use application\components\workers\WorkerException;

/**
 * Used to give a common functional for workers.
 * @package components.workers
 */
abstract class BaseWorker
{
    use \application\components\helpers\ContainerHelper;

    /**
     * @var int Process initiator User ID. To this user will be sent instant message when process will finish.
     */
    protected $initiatorID;

    /**
     * @var int Owner User ID. From this user will be sent personal messages.
     */
    protected $ownerID;

    /**
     * @var \PhpAmqpLib\Message\AMQPMessage current RabbitMQ message
     */
    protected $request;

    /**
     * @var array current Job
     */
    protected $job;

    /**
     * @var string current JSON string with Job
     */
    protected $jobString;

    /**
     * @var string current method for background task
     */
    protected $method;

    /**
     * Working model of having behavior InProgressBehavior
     * @see InProgressBehavior
     * @var BaseModel|null
     */
    protected $inProgressModel;

    /**
     * update status of inProgressModel
     * @see updateInProgressModel()
     * @var bool
     */
    protected $updateInProgressModel = true;

    /**
     * Reset scope in progress model
     * @var bool
     */
    protected $inProgresModelResetScope = false;

    /**
     * @var string
     */
    protected $inProgressCallback;

    /**
     * @var string
     */
    protected $inProgressMessage;

    /**
     * @var boolean
     */
    protected $hasError = false;

    /**
     * @var string Start time
     */
    private static $startedAt;

    /**
     * @return string
     */
    public static function getTime()
    {
        return date("Y-m-d H:i:s", time());
    }

    /**
     * Render current job and time started.
     *
     * @param string $jobMessage JSON-encoded messages as Job Params
     * @param string $name
     */
    protected static function renderStart($jobMessage, $name)
    {
        self::$startedAt = microtime(true);
        echoCLI(self::getTime() . '- Received job [' . $name . ']: ' . $jobMessage);
    }

    /**
     * Render end of job.
     *
     * @param string $message
     * @see printf()
     * @return void
     */
    protected static function renderFinish($message = null)
    {
        if ($message === null) {
            $message = self::getTime() . 'FINISHED';
        }

        self::render($message);
        self::render('Processing time is ' . round(microtime(true) - self::$startedAt, 2) . ' sec');
    }

    /**
     * Gets any number of arguments and prints them function {@link printf()}
     * Sharing with each new line.
     *
     * @param bool $lastEol
     * @see printf()
     * @return void
     */
    protected static function render($lastEol = true)
    {
        ob_start();
        echo PHP_EOL;
        call_user_func_array('printf', func_get_args());
        $output = ob_get_contents();
        ob_end_clean();

        echoCLI($output, $lastEol);
    }

    /**
     * @return array worker callbacks
     */
    abstract public function getWorkerMethods();

    /**
     * @param $event CErrorEvent
     */
    public function handleError($event)
    {
        echoCLI('****************************************************************');
        echoCLI("Error");
        $this->hasError = true;
        // После обработки события завершаем приложение
        $event->handled = true;
        $this->postprocessing();
    }

    /**
     * @param $event CExceptionEvent
     */
    public function handleException($event)
    {
        echoCLI('****************************************************************');
        echoCLI("Exception");
        $this->hasError = true;
        // После обработки события завершаем приложение
        $event->handled = true;
        $this->postprocessing();
    }

    /**
     * Starting worker`s method for processing incoming tasks
     *
     * @param $method string
     * @param $request |object \PhpAmqpLib\Message\AMQPMessage
     * @throws WorkerException
     */
    public function runWorkerJob($method, $request)
    {
        echoCLI('****************************************************************');
        echoCLI(self::getTime() . "- Starting worker");
        $this->registerHandlers();

        /**
         * Default message
         */
        $this->inProgressMessage = 'Internal Server Error.';

        /**
         * Request message from broker
         */
        $this->request = $request;

        /**
         * Requesting method to call
         */
        $this->method = $method;

        if (method_exists($this, $method) && in_array($method, $this->getWorkerMethods())) {
            /**
             * Store requested job into array
             */
            $this->jobString = $this->request->body;
            echoCLI("Job Request: " . $this->jobString);
            $this->job = CJSON::decode($this->request->body);
            $this->mainInit();
            $this->init();
            echoCLI('****************************************************************');
            try {
                $result = $this->{$method}();
            } catch (Exception $e) {
                if ($e instanceof WorkerException) {
                    $e = ($e->getPrevious()) ?: $e;
                }
                echoCLI('!!!!!!!!!!! Exception !!!!!!!!!!!!!');
                echoCLI('Message: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());

                throw new WorkerException('', 0, $e);
            }
            echoCLI('****************************************************************');
            if ($result !== true) {
                $this->hasError = true;
            }
            $this->postprocessing();
        }
    }

    /**
     * Processing fatal errors
     */
    public function onShutdownHandler()
    {
        $e = error_get_last();

        $errorsToHandle = E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING;

        if (!is_null($e) && ($e['type'] & $errorsToHandle)) {
            // handling error & disable error capturing to avoid recursive errors
            restore_error_handler();
            restore_exception_handler();
            echoCLI('****************************************************************');
            echoCLI("Fatal error");
            $this->hasError = true;
            $this->postprocessing();

            $log = "'Fatal error: {$e['message']} ({$e['file']}:{$e['line']})\n";
            if (isset($_SERVER['REQUEST_URI'])) {
                $log .= 'REQUEST_URI=' . $_SERVER['REQUEST_URI'];
            }
            //?????? Log error

        }
    }

    /**
     * Calls callback several times until result has been received.
     *
     * @param callable $callback
     * @param int $numberOfAttempts
     * @param int $delay
     * @return mixed|null
     */
    public function makeAttempts(callable $callback, $numberOfAttempts = 60, $delay = 1)
    {
        return makeAttempts($callback, $numberOfAttempts, $delay);
    }

    /**
     * Save the result of the work
     */
    protected function postprocessing()
    {
        /**
         * Убираем флаг прогресса с модели
         */
        $this->updateInProgressModel();

        /**
         * Подтверждаем обработку сообщения или отклоняем его, что бы оно ушло из очереди и не запускалось повторно
         */
        if ($this->hasError) {
            echoCLI("Reject RabbitMQ message");
            $this->nackMsg();

            echoCLI("After error callback");
            $this->afterError();

            if (isset($this->inProgressMessage)) {
                $this->inProgressErrorMessage($this->inProgressMessage, $this->inProgressCallback);
            }
        } else {
            echoCLI("Accept RabbitMQ message");
            $this->ackMgs();

            if (isset($this->inProgressMessage)) {
                $this->inProgressSuccessMessage($this->inProgressMessage, $this->inProgressCallback);
            }
        }

        echoCLI(self::getTime() . '- End of work');
        echoCLI('-------------------------------------------------------------------------');
    }

    /**
     * Called after the error and exceptions
     */
    protected function afterError()
    {
    }

    /**
     * Main initialization before the job
     */
    protected function mainInit()
    {
        $this->setUserTimesone();
        $this->setInprogressModel();
        $this->updateInProgressModel(1);
    }

    /**
     * Init for extending workers
     */
    protected function init()
    {
    }

    /**
     * Send instant success message to initiator of the background task.
     *
     * @param string $message Text message to initiator.
     * @param null|string $callback JS code to eval it on initiator computer.
     */
    protected function inProgressSuccessMessage($message, $callback = null)
    {
        $messageData = [
            'type' => 'inProgress',
            'alertType' => 'success',
            'title' => 'BACKGROUND PROCESS COMPLETED',
            'message' => $message,
        ];
        if ($callback) {
            $messageData['callback'] = $callback;
        }
        $this->instantMessage($messageData);
    }

    /**
     * Send instant info message to initiator of the background task.
     *
     * @param string $message Text message to initiator.
     * @param null|string $callback JS code to eval it on initiator computer.
     */
    protected function infoMessage($message, $callback = null)
    {
        $messageData = [
            'type' => 'inProgress',
            'alertType' => 'info',
            'title' => t('AlertMessage', 'BACKGROUND PROCESS INFO'),
            'message' => $message,
        ];

        if ($callback) {
            $messageData['callback'] = $callback;
        }

        $this->instantMessage($messageData);
    }

    /**
     * Send instant error message to initiator of the background task.
     *
     * @param string $message Text message to initiator.
     * @param null|string $callback JS code to eval it on initiator computer.
     */
    protected function inProgressErrorMessage($message, $callback = null)
    {
        $messageData = [
            'type' => 'inProgress',
            'alertType' => 'error',
            'title' => 'BACKGROUND PROCESS CANNOT BE COMPLETED',
            'message' => $message,
        ];
        if ($callback) {
            $messageData['callback'] = $callback;
        }
        $this->instantMessage($messageData);
    }

    /**
     * @param int $id Set initiator user ID for this background task.
     * @return void Set initiator user ID for this background task.
     */
    protected function setInitiatorID($id)
    {
        $this->initiatorID = $id;
    }

    /**
     * @param int $id Set owner user ID for this background task.
     * @return void Set owner user ID for this background task.
     */
    protected function setOwnerID($id)
    {
        $this->ownerID = $id;
        user()->id = $id;
    }

    /**
     * Rejects message
     */
    protected function nackMsg()
    {
        if ($this->request instanceof \PhpAmqpLib\Message\AMQPMessage) {
            $this->request->delivery_info['channel']->basic_nack($this->request->delivery_info['delivery_tag']);
        }
    }

    /**
     * Acknowledges message
     */
    protected function ackMgs()
    {
        if ($this->request instanceof \PhpAmqpLib\Message\AMQPMessage) {
            $this->request->delivery_info['channel']->basic_ack($this->request->delivery_info['delivery_tag']);
        }
    }

    /**
     * Setting up time zone from job data
     * @throws CDbException
     */
    private function setUserTimesone()
    {
        $tz = $this->job['timeZone'] ?: app()->getTimeZone();
        echoCLI("Set timeZone: {$tz}");
        setUserTimeZone($tz);
    }

    private function instantMessage(array $messageData)
    {
        if (isset($this->initiatorID)) {
            $messageData['message'] .= '<span class="hide">' . time() . '</span>';
            app()->notifier->send($this->initiatorID, \CJSON::encode($messageData));
            echoCLI('Message sent for user: ' . $this->initiatorID);
        }
    }

    /**
     * Setting up current model of having "inProgress" behavior
     * @throws CException
     */
    private function setInprogressModel()
    {
        if (false !== $this->job['class'] && false !== $this->job['pk']) {
            $className = $this->job['class'];
            if (!class_exists($className)) {
                throw new \CException("Class not found. [{$className}]");
            }

            $resetScope = $this->inProgresModelResetScope;

            echoCLI("Setup inProgress model: $className");

            $this->inProgressModel = $this->makeAttempts(function () use ($className, $resetScope) {
                if ($resetScope) {
                    return $className::model()->resetScope()->findByPk($this->job['pk']);
                } else {
                    return $className::model()->findByPk($this->job['pk']);
                }
            });

            echoCLI("inProgress model ID: " . ($this->inProgressModel ? $this->job['pk'] : null));
        }
    }

    /**
     * @param int $status
     */
    private function updateInProgressModel($status = 0)
    {
        $bh = $this->inProgressModel && $this->updateInProgressModel
            ? $this->inProgressModel->asa('InProgressBehavior')
            : null;

        if ($bh && $bh->getEnabled()) {
            $status ? echoCLI("Lock") : echoCLI("Unlock");
            $status ? $this->inProgressModel->mutex->lock() : $this->inProgressModel->mutex->unlock();
            if ($this->inProgressModel->getAttribute(InProgress::IN_PROGRESS_FIELD_NAME) != $status) {
                $this->inProgressModel->{InProgress::IN_PROGRESS_FIELD_NAME} = $status;
                $result = $this->inProgressModel->updateByPk($this->job['pk'], [
                    InProgress::IN_PROGRESS_FIELD_NAME => $status
                ]);
                echoCLI("Update inProgress model by status $status: " . ($result ? "true" : "false"));
            }
        }
    }

    /**
     * Register exception handler, error handler
     * @throws CException
     */
    private function registerHandlers()
    {
        register_shutdown_function([$this, 'onShutdownHandler']);
        app()->attachEventHandler('onException', [$this, 'handleException']);
        app()->attachEventHandler('onError', [$this, 'handleError']);
    }
}
