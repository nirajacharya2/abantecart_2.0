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

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use H;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use DebugBar\Bridge\MonologCollector;

/**
 * Class ALog
 */
final class ALog
{
    private $mode = true;
    protected $app_filename, $security_filename, $warning_filename, $debug_filename;
    protected $loggers = [];
    //maximum of file count for rotation log
    const MAX_FILE_COUNT = 10;

    /**
     * ALog constructor.
     *
     * @param array  $file_names
     * @param string $dir_logs
     *
     * @throws \DebugBar\DebugBarException
     */
    public function __construct(array $file_names, $dir_logs = '')
    {
        $application_log_filename = (string)$file_names['app'];
        $security_filename = (string)$file_names['security'];
        $warning_filename = (string)$file_names['warn'];
        $debug_filename = (string)$file_names['debug'];

        $dir_logs = !$dir_logs || !is_dir($dir_logs) || !is_writable($dir_logs)
            ? ABC::env('DIR_LOGS')
            : $dir_logs;

        if (!$dir_logs || !is_writable($dir_logs)) {
            error_log(
                'Error: Log directory "'
                .$dir_logs
                .'" is non-writable or undefined! Please check or change permissions.'
            );
        }

        if (!$application_log_filename) {
            error_log('ALog Error: Please set error Log filename as argument! Empty value given!');
        } else {
            $security_filename = !$security_filename ? $application_log_filename : $security_filename;
            $warning_filename = !$warning_filename ? $application_log_filename : $warning_filename;
            $debug_filename = !$debug_filename ? $application_log_filename : $debug_filename;
        }

        $this->app_filename = $dir_logs.$application_log_filename;

        if(!is_file($this->app_filename)){
            $tmp = fopen($this->app_filename,'a');
            chmod($this->app_filename, 0664);
            fclose($tmp);
        }

        if (is_file($this->app_filename) && !is_writable($this->app_filename)) {
            $error_text = 'ALog Error: Log file '.$this->app_filename.' is not writable!';
            error_log($error_text);
            throw new \Exception($error_text);
        } else {
            $this->security_filename = $dir_logs.$security_filename;
            if (is_file($this->security_filename) && !is_writable($this->security_filename)) {
                error_log('ALog Error: Log file '.$this->security_filename.' is not writable!');
                $this->security_filename = $this->app_filename;
            }
            $this->warning_filename = $dir_logs.$warning_filename;
            if (is_file($this->warning_filename) && !is_writable($this->warning_filename)) {
                error_log('ALog Error: Log file '.$this->warning_filename.' is not writable!');
                $this->warning_filename = $this->app_filename;
            }

            $this->debug_filename = $dir_logs.$debug_filename;
            if (is_file($this->debug_filename) && !is_writable($this->debug_filename)) {
                error_log('ALog Error: Log file '.$this->debug_filename.' is not writable!');
                $this->debug_filename = $this->app_filename;
            }
        }

        $stream = new StreamHandler($this->app_filename, Logger::DEBUG, 0664);
        // the default date format is "Y-m-d H:i:s"
        $dateFormat = "Y-m-d H:i:s.u";
        // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        if (Registry::request()) {
            $request_id = Registry::request()->getUniqueId();
        } elseif(class_exists('\H')) {
            $request_id = H::genRequestId();
        }
        $output = "%datetime% > ".ABC::env('APP_NAME')." v".ABC::env('VERSION')." > Request ID: ".$request_id
            ." > %level_name% > %message%\n";
        // create a formatter which allows line breaks in the message
        $formatter = new LineFormatter($output, $dateFormat, true);
        $stream->setFormatter($formatter);
        $logger = new Logger('error_logger');
        $debug_bar = ADebug::$debug_bar;
        if ($debug_bar && !$debug_bar->hasCollector('monolog')) {
            $debug_bar->addCollector(new MonologCollector($logger));
        }
        $logger->pushHandler($stream);
        $this->loggers['error'] = $logger;

        if ($this->app_filename != $this->security_filename) {
            $stream = new StreamHandler($this->security_filename, Logger::DEBUG, 0664);
            $stream->setFormatter($formatter);
            $logger = new Logger('security_logger');
            $debug_bar = ADebug::$debug_bar;
            if ($debug_bar && !$debug_bar->hasCollector('monolog')) {
                $debug_bar->addCollector(new MonologCollector($logger));
            }
            $logger->pushHandler($stream);
            $this->loggers['security'] = $logger;
        } else {
            $this->loggers['security'] = $this->loggers['error'];
        }

        if ($this->app_filename != $this->warning_filename) {
            $stream = new StreamHandler($this->warning_filename, Logger::DEBUG, 0664);
            $stream->setFormatter($formatter);
            $logger = new Logger('warning_logger');
            $debug_bar = ADebug::$debug_bar;
            if ($debug_bar && !$debug_bar->hasCollector('monolog')) {
                $debug_bar->addCollector(new MonologCollector($logger));
            }
            $logger->pushHandler($stream);
            $this->loggers['warning'] = $logger;
        } else {
            $this->loggers['warning'] = $this->loggers['error'];
        }

        if ($this->app_filename != $this->debug_filename) {
            $stream = new RotatingFileHandler($this->debug_filename, 10, Logger::DEBUG, true, 0664);
            $stream->setFilenameFormat('{filename}-{date}', 'Y-m-d');

            $output = "%datetime% > ".ABC::env('APP_NAME')." v"
                .ABC::env('VERSION')." > ".$_GET['rt']."> %level_name% > %message%\n";
            $formatter = new LineFormatter($output, $dateFormat, true);
            $stream->setFormatter($formatter);
            $logger = new Logger('debug_logger');
            $debug_bar = ADebug::$debug_bar;
            if ($debug_bar && !$debug_bar->hasCollector('monolog')) {
                $debug_bar->addCollector(new MonologCollector($logger));
            }
            $logger->pushHandler($stream);
            $this->loggers['debug'] = $logger;
        } else {
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

    public function getLoggers()
    {
        return $this->loggers;
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function write($message)
    {
        if (!$this->mode) {
            return null;
        }
        $this->loggers['error']->error($message);
        return true;
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function error($message)
    {
        if (!$this->mode) {
            return null;
        }
        return $this->loggers['error']->error($message);
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function security($message)
    {
        if (!$this->mode) {
            return null;
        }
        return $this->loggers['security']->alert($message);
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function warning($message)
    {
        if (!$this->mode) {
            return null;
        }
        return $this->loggers['warning']->notice($message);
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function debug($message)
    {
        if (!$this->mode) {
            return null;
        }
        return $this->loggers['debug']->debug($message);
    }

    /**
     * @param string $message
     *
     * @return null
     */
    public function critical($message)
    {
        if (!$this->mode) {
            return null;
        }
        return $this->loggers['error']->critical($message);
    }
}
