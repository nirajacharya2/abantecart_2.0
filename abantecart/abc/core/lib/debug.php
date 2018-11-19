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
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\StandardDebugBar;
use Exception;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ADebug
{
    static public $checkpoints = array();
    static public $queries = array();
    static public $queries_time = 0;
    static private $debug = 0; //off
    static private $debug_level = 0; //only exceptions
    static private $isInit = false;
    static private $isError = false;
    /**
     * @var \DebugBar\StandardDebugBar
     */
    static public $debug_bar;

    static function register()
    {
        if (!self::isActive()) {
            return false;
        }
        self::$debug_bar = new StandardDebugBar();
        return true;
    }

    static function getDebugBarAssets()
    {
        if (!self::$debug_bar) {
            return [];
        }
        $js_dbg_render = self::$debug_bar->getJavascriptRenderer();
        $js_dbg_render->disableVendor('jquery');
        $output['js'] = $js_dbg_render->getAssets('js', 'url');
        $output['css'] = $js_dbg_render->getAssets('css', 'url');

        return $output;
    }

    static private function isActive()
    {
        if (!self::$isInit) {
            if (ABC::env('INSTALL')) {
                self::$debug = 1;
                self::$debug_level = 1;
            } else {
                self::$debug = ABC::env('DEBUG');
                self::$debug_level = ABC::env('DEBUG_LEVEL');
            }
            self::$isInit = true;
        }

        return self::$debug;
    }

    static function set_query($query, $time, $backtrace)
    {
        if (!self::isActive()) {
            return false;
        }
        self::$queries[] = array(
            'sql'  => $query,
            'time' => sprintf('%01.5f', $time),
            'file' => $backtrace['file'],
            'line' => $backtrace['line'],
        );
        self::$queries_time += $time;
        return true;
    }

    static function checkpoint($name)
    {
        if (!self::isActive()) {
            return false;
        }

        $e = new Exception();
        $array = array(
            'name'           => $name,
            'time'           => self::microtime(),
            'memory'         => memory_get_usage(),
            'included_files' => count(get_included_files()),
            'queries'        => count(self::$queries),
            'type'           => 'checkpoint',
            'trace'          => $e->getTraceAsString(),
        );
        self::$checkpoints[] = $array;
        return true;
    }

    /**
     * @param string $name
     * @param mixed  $variable
     *
     * @return bool
     */
    static function variable(string $name, $variable)
    {
        if (!self::isActive()) {
            return false;
        }

        ob_start();
        echo '<pre>';
        var_export($variable);
        echo '</pre>';
        $msg = ob_get_clean();

        self::$checkpoints[] = array(
            'name' => $name,
            'msg'  => $msg,
            'type' => 'variable',
        );

        return true;
    }

    static function error($name, $code, $msg)
    {
        self::$checkpoints[] = array(
            'name'           => $name,
            'time'           => self::microtime(),
            'memory'         => memory_get_usage(),
            'included_files' => count(get_included_files()),
            'queries'        => count(self::$queries),
            'msg'            => $msg,
            'code'           => $code,
            'type'           => 'error',
        );
        self::$isError = true;
    }

    static function warning($name, $code, $msg)
    {
        self::$checkpoints[] = array(
            'name'           => $name,
            'time'           => self::microtime(),
            'memory'         => memory_get_usage(),
            'included_files' => count(get_included_files()),
            'queries'        => count(self::$queries),
            'msg'            => $msg,
            'code'           => $code,
            'type'           => 'warning',
        );
        self::$isError = true;
    }

    static function microtime()
    {
        list($usec, $sec) = explode(' ', microtime());

        return ((float)$usec + (float)$sec);
    }

    static function display_queries($start, $end)
    {

        if ($end - $start <= 0) {
            return null;
        }

        $output = "Time File Line SQL\n";
        for ($i = $start; $i < $end; $i++) {
            $key = $i;
            $query = self::$queries[$key];
            $output .= $query['time'].' '.$query['file'].' '.$query['line'].' '.$query['sql']."\n";
        }
        $output = "\n";

        return $output;
    }

    static function display_errors()
    {
        if (!self::$isError) {
            return null;
        }
        $output = "Name Info\n";

        $show = array('error', 'warning');
        foreach (self::$checkpoints as $c) {
            if (!in_array($c['type'], $show)) {
                continue;
            }
            $output .= $c['code'].'::'.$c['name'].'      '.$c['msg']."\n";
        }
        $output .= "\n";

        return $output;
    }

    static function display()
    {
        if (!self::isActive()) {
            return false;
        }

        $env_data = ABC::getEnv();
        //remove credentials by security reason
        unset($env_data['DATABASES'], $env_data['ADMIN_SECRET']);
        self::$debug_bar->addCollector(new ConfigCollector($env_data));

        $previous = array();
        $cumulative = array();

        $first = true;
        $msg = '';
        switch (self::$debug_level) {

            case 0 :
                //show only exceptions
                //shown in debug_bar
                break;
            case 1 :
                //show errors and warnings
                $msg .= self::display_errors();
                break;
            case 2 :
                // #1 + mysql site load, php file execution time and page elements load time
                $msg .= self::display_errors();

                //count php execution time
                foreach (self::$checkpoints as $name => $c) {
                    if ($c['type'] != 'checkpoint') {
                        continue;
                    }
                    if ($first == true) {
                        $first = false;
                        $cumulative = $c;
                    }
                    $time = sprintf("%01.4f", $c['time'] - $cumulative['time']);
                }

                $msg .= 'Queries - '.count(self::$queries)."\n";
                $msg .= 'Queries execution time - '.sprintf('%01.5f', self::$queries_time)."\n";
                $msg .= 'PHP Execution time - '.$time."\n";
                break;

            case 3 :
            case 4 :
            case 5 :
                // #2 + basic logs and stack of execution
                // #3 + dump mysql statements
                // #4 + call stack
                $msg .= "\tName\tInfo\n";

                foreach (self::$checkpoints as $c) {
                    $msg .= $c['name'];

                    if ($first == true && $c['type'] != 'variable') {
                        $previous = array(
                            'time'           => $c['time'],
                            'memory'         => 0,
                            'included_files' => 0,
                            'queries'        => 0,
                        );
                        $first = false;
                        $cumulative = $c;
                    }

                    switch ($c['type']) {
                        case 'variable':
                            $msg .= $c['msg']."\n";
                            break;
                        case 'error':
                        case 'warning':
                            $msg .= $c['msg']."\n";
                        case 'checkpoint':
                            $msg .= '- Memory: '.(number_format($c['memory'] - $previous['memory'])).' ('
                                .number_format($c['memory']).')'."\n";
                            $msg .= '- Files: '.($c['included_files'] - $previous['included_files']).' ('
                                .$c['included_files'].')'."\n";
                            $msg .= '- Queries: '.($c['queries'] - $previous['queries']).' ('.$c['queries'].')'."\n";
                            $msg .= '- Time: '.sprintf("%01.4f", $c['time'] - $previous['time']).' ('.sprintf("%01.4f",
                                    $c['time'] - $cumulative['time']).')'."\n";
                            if (self::$debug_level > 3) {
                                $msg .= self::display_queries($previous['queries'], $c['queries']);
                            }
                            if (self::$debug_level > 4) {
                                $msg .= $c['trace'];
                            }
                            $previous = $c;
                            break;
                    }
                    $msg .= "\n";
                }
                $msg .= "\n";

                break;

            default:

        }

        if (self::$debug) {
            //display debug-bar js and html
            if (self::$debug_bar) {
                self::$debug_bar["messages"]->addMessage($msg);
                $debug_renderer = self::$debug_bar->getJavascriptRenderer();
                echo $debug_renderer->render();
            }

            self::toLog($msg);
        }
        return true;
    }

    static function toLog($message)
    {
        $message = strip_tags(str_replace('<br />', "\r\n", $message));
        if (!$message) {
            return false;
        }
        if (class_exists('\abc\core\engine\Registry')) {
            $logger = Registry::getInstance()->get('log');
        } else {
            $logger = ABC::getObjectByAlias('ALog', [['app' => 'debug.log']]);
        }

        return $logger->debug($message);
    }
}
if(class_exists('\DebugBar\DataCollector\PDO\PDOCollector')) {
    class PHPDebugBarEloquentCollector extends \DebugBar\DataCollector\PDO\PDOCollector
    {
        /**
         * @var \Illuminate\Database\Capsule\Manager
         */
        protected $orm;

        /**
         * PHPDebugBarEloquentCollector constructor.
         *
         * @param \Illuminate\Database\Capsule\Manager $orm
         */
        public function __construct(\Illuminate\Database\Capsule\Manager $orm)
        {
            $this->orm = $orm;
            parent::__construct();
            $this->addConnection($this->getTraceablePdo(), 'Eloquent PDO');
        }

        /**
         * @return \Illuminate\Database\Capsule\Manager;
         */
        protected function getEloquentCapsule()
        {
            return $this->orm;
        }

        /**
         * @return \PDO
         */
        protected function getEloquentPdo()
        {
            return $this->orm::connection()->getPdo();
        }

        /**
         * @return \DebugBar\DataCollector\PDO\TraceablePDO
         */
        protected function getTraceablePdo()
        {
            return new \DebugBar\DataCollector\PDO\TraceablePDO($this->getEloquentPdo());
        }

        // Override
        public function getName()
        {
            return "eloquent_pdo";
        }

        // Override
        public function getWidgets()
        {
            return array(
                "eloquent"       => array(
                    "icon"    => "inbox",
                    "widget"  => "PhpDebugBar.Widgets.SQLQueriesWidget",
                    "map"     => "eloquent_pdo",
                    "default" => "[]",
                ),
                "eloquent:badge" => array(
                    "map"     => "eloquent_pdo.nb_statements",
                    "default" => 0,
                ),
            );
        }
    }
}