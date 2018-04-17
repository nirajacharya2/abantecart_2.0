<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

if ( ! class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

/**
 * Class ALog
 */
final class ALog
{
    private $mode = true;
    protected $error_filename = 'error.log';
    protected $security_filename, $warning_filename, $debug_filename;
    protected $loggers = [];

    /**
     * ALog constructor.
     *
     * @param string $error_filename - required
     * @param string $security_filename
     * @param string $warning_filename
     * @param string $debug_filename
     */
    public function __construct( string $error_filename, $security_filename = '', $warning_filename = '', $debug_filename = '' )
    {
        $dir_logs = ABC::env('DIR_LOGS');
        if ( !$dir_logs || !is_writable($dir_logs) ) {
            error_log('Error: Log directory "'.$dir_logs.'" is non-writable or undefined! Please check or change permissions.');
        }

        if( !$error_filename ){
            error_log('ALog Error: Please set error Log filename as argument! Empty value given!');
        }else{
            $security_filename = !$security_filename ? $error_filename : $security_filename;
            $warning_filename = !$warning_filename ? $error_filename : $warning_filename;
            $debug_filename = !$debug_filename ? $error_filename : $debug_filename;
        }

        $this->error_filename = $dir_logs.$error_filename;
        if(is_file($this->error_filename) && !is_writable($this->error_filename)){
            error_log('ALog Error: Log file '.$this->error_filename.' is not writable!');
        }else{
            $this->security_filename = $dir_logs.$security_filename;
            if(is_file($this->security_filename) && !is_writable($this->security_filename)){
                error_log('ALog Error: Log file '.$this->security_filename.' is not writable!');
                $this->security_filename = $this->error_filename;
            }
            $this->warning_filename = $dir_logs.$warning_filename;
            if(is_file($this->warning_filename) && !is_writable($this->warning_filename)){
                error_log('ALog Error: Log file '. $this->warning_filename .' is not writable!');
                $this->warning_filename = $this->error_filename;
            }

            $this->debug_filename = $dir_logs.$debug_filename;
            if(is_file($this->debug_filename) && !is_writable($this->debug_filename)){
                error_log('ALog Error: Log file '. $this->debug_filename .' is not writable!');
                $this->debug_filename = $this->error_filename;
            }
        }


        $stream = new StreamHandler($this->error_filename, Logger::DEBUG);
        // the default date format is "Y-m-d H:i:s"
        $dateFormat = "Y-m-d H:i:s";
        // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        $output = "%datetime% > ".ABC::env('APP_NAME')." v" . ABC::env('VERSION') ." > %level_name% > %message%\n";
        // finally, create a formatter
        $formatter = new LineFormatter($output, $dateFormat);
        $stream->setFormatter($formatter);
        $logger = new Logger('error_logger');
        $logger->pushHandler($stream);
        $this->loggers['error'] = $logger;

        if( $this->error_filename != $this->security_filename ){
            $stream = new StreamHandler($this->security_filename, Logger::DEBUG);
            $stream->setFormatter($formatter);
            $logger = new Logger('security_logger');
            $logger->pushHandler($stream);
            $this->loggers['security'] = $logger;
        }else{
            $this->loggers['security'] = $this->loggers['error'];
        }

        if( $this->error_filename != $this->warning_filename ){
            $stream = new StreamHandler($this->warning_filename, Logger::DEBUG);
            $stream->setFormatter($formatter);
            $logger = new Logger('warning_logger');
            $logger->pushHandler($stream);
            $this->loggers['warning'] = $logger;
        }else{
            $this->loggers['warning'] = $this->loggers['error'];
        }

        if( $this->error_filename != $this->debug_filename ){
            $stream = new StreamHandler($this->debug_filename, Logger::DEBUG);
            $stream->setFormatter($formatter);
            $logger = new Logger('debug_logger');
            $logger->pushHandler($stream);
            $this->loggers['debug'] = $logger;
        }else{
            $this->loggers['debug'] = $this->loggers['error'];
        }


        if (class_exists('\abc\core\engine\Registry')) {
            // for disabling via settings
            $registry = Registry::getInstance();
            if (is_callable($registry->get('config')) && $registry->get('config')->get('config_error_log') !== null) {
                $this->mode = $registry->get('config')->get('config_error_log') ? true : false;
            }
        }
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function write($message)
    {
        if ( ! $this->mode) {
            return null;
        }
        $this->loggers['error']->error($message);
    }
    /**
     * @param string $message
     *
     * @return null
     */
    public function error($message)
    {
        if ( ! $this->mode) {
            return null;
        }
        $this->loggers['error']->error($message);
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function security($message)
    {
        if ( ! $this->mode) {
            return null;
        }
        $this->loggers['security']->alert($message);
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function warning($message)
    {
        if ( ! $this->mode) {
            return null;
        }
        $this->loggers['warning']->notice($message);
    }
    /**
     * @param string $message
     *
     * @return null
     */
    public function debug($message)
    {
        if ( ! $this->mode) {
            return null;
        }
        $this->loggers['debug']->debug($message);
    }
    /**
     * @param string $message
     *
     * @return null
     */
    public function critical($message)
    {
        if ( ! $this->mode) {
            return null;
        }
        $this->loggers['error']->critical($message);
    }
}
