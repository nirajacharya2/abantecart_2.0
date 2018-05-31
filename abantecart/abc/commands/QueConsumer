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

use PhpAmqpLib\Connection\AMQPConnection;

include_once('base/BaseCommand.php');

/**
 * QueConsumer for starting AbanteCart consumers of RabbitMQ message broker.
 *
 */
class QueConsumer extends BaseCommand
{
    /**
     * @var int Timeout limit.
     * Should be less than MySQL wait_timeout parameter to prevent DB connection timeout Exception
     */
    protected $queTimeout = QUE_TIMEOUT;

    /**
     * Start action method
     *
     * @param string    $className
     * @param int       $queLimit
     * @param null|bool $durable
     */
    public function start($className, $queLimit = 1, $durable = null)
    {
        if ($this->_fork($className, $queLimit, $durable) === 1) {
            // Additional delay to prevent excessive load in case worker fails to start
            sleep(300);
        }
    }

    /**
     * Starts an instance of consumer for the class and method
     *
     * @param string    $className
     * @param int       $queLimit
     * @param null|bool $durable
     *
     * @return int
     * @throws Exception
     * @throws QueConsumerException
     * @throws WorkerException
     */
    private function _fork($className, $queLimit = 1, $durable = null)
    {
        if (!class_exists($className, false)) {
            $workerClass = new $className;
            $methods = $workerClass->getWorkerMethods();

            // prepare params for rabbitmq
            $rabbitParams = config('rabbitmq'); //????? get config params
            try {
                $connection = new AMQPConnection(
                    $rabbitParams['host'],
                    $rabbitParams['port'],
                    $rabbitParams['login'],
                    $rabbitParams['password'],
                    $rabbitParams['vhost']
                );
                $channel = $connection->channel();

                if (!is_null($durable)) {
                    // Durable (the queue will survive a broker restart)
                    $rabbitParams['durable'] = (bool)$durable;
                }

                foreach ($methods as $queWorkMethod) {
                    if (!method_exists($workerClass, $queWorkMethod)) {
                        throw new QueConsumerException(
                            'Class '.$className.' does not have method '.$queWorkMethod
                        );
                    }

                    /*
                        name: $queue
                        passive: false
                        durable: true // the queue will survive server restarts
                        exclusive: false // the queue can be accessed in other channels
                        auto_delete: false //the queue won't be deleted once the channel is closed.
                        nowait: false // Doesn't wait on replies for certain things.
                        parameters: array // How you send certain extra data to the queue declare
                    */
                    $channel->queue_declare(
                        $queWorkMethod,
                        false,
                        $rabbitParams['durable'],
                        false,
                        false
                    );

                    $callback = function ($msg) use ($workerClass, $queWorkMethod) {
                        $workerClass->runWorkerJob($queWorkMethod, $msg);
                    };

                    echoCLI($workerClass::getTime().' QUE: '.$queWorkMethod.' started.');

                    /*
                        queue: Queue from where to get the messages
                        consumer_tag: Consumer identifier
                        no_local: Don't receive messages published by this consumer.
                        no_ack: Tells the server if the consumer will acknowledge the messages.
                        exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
                        nowait: don't wait for a server response. In case of error the server will raise a channel exception
                        callback: A PHP Callback
                    */
                    $channel->basic_qos(null, 1, null);
                    $channel->basic_consume($queWorkMethod, '', false, false, false, false, $callback);
                }

                //NOTE: Worker Process is running in the memory all the time.
                $count = 0;
                /**
                 * Limit check for forking of the worker
                 */
                while ($count < $queLimit) {
                    $channel->wait(null, null, $this->queTimeout);
                    $count++;
                }
                $channel->close();
                $connection->close();

            } catch (PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                echoCLI('Timeout '.($this->queTimeout / 60).' minutes. Finished.');
            } catch (WorkerException $e) {
                throw ($e->getPrevious() ? $e->getPrevious() : $e);
            } catch (Exception $e) {
                // Cannot start worker or connect to rabbitmq
                echoCLI('RabbitMQ: '.$e->getMessage().PHP_EOL.$e->getTraceAsString());
                return 1;
            }
        } else {
            echoCLI('Worker class '.$className.' does not exist.');
        }
    }
}