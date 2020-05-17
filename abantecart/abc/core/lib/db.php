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
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;

/**
 * Class ADB
 *
 * @package abc\core\lib
 *
 * @method transaction(\Closure $function) Capsule
 * @method beginTransaction() Capsule
 * @method commit() Capsule
 * @method rollback() Capsule
 * @method enableQueryLog() void
 * @method getQueryLog() array
 */
class ADB
{
    /**
     * @var Capsule
     */
    protected $orm;
    protected $conName = 'default';
    protected $db_config = [];
    public $error = '';
    public $registry;

    /**
     * @param array  $db_config array(
     *                          'driver'    => 'mysql',
     *                          'host'      => 'localhost', // or array ['read' => '****', 'write' = '*****']
     *                          'port'      => '3306', // or array ['read' => '***', 'write' = '***']
     *                          'database'  => '***',
     *                          'username'  => '***',
     *                          'password'  => '***,
     *                          'charset'   => 'utf8',
     *                          'collation' => 'utf8_unicode_ci',
     *                          'prefix'    => 'ac_'
     *
     * @param string $conName
     *
     * @throws AException
     * @throws \DebugBar\DebugBarException
     */
    public function __construct($db_config = [], $conName = 'default')
    {
        if (!$db_config) {
            throw new \Exception('Cannot initiate ADB class with empty config parameter!', AC_ERR_LOAD, __FILE__);
        }
        $this->db_config = $this->prepareDBConfig($db_config);
        $this->conName = $conName;
        try {
            if(Registry::db()){
                $this->orm = Registry::db()->getOrm();
            }else {
                $this->orm = new Capsule;
            }
            $this->orm->addConnection($this->db_config, $this->conName);

            $this->orm->setAsGlobal();  //this is important
            //register ORM-model event listeners
            /** @var Dispatcher $evd */
            $evd = ABC::getObjectByAlias('EventDispatcher');
            if (is_object($evd)) {
                $this->orm->setEventDispatcher($evd);
                foreach ((array)ABC::env('MODEL')['EVENTS'] as $event_alias => $listeners) {
                    foreach ($listeners as $listener) {
                        $this->orm->getEventDispatcher()->listen($event_alias, $listener);
                    }
                }
            }

            $this->orm->bootEloquent();

            //check connection
            $this->table($this->raw('DUAL'))->first([$this->raw(1)]);
            $debug_bar = ADebug::$debug_bar;
            if ($debug_bar) {
                $debug_bar->addCollector(new PHPDebugBarEloquentCollector($this->orm));
            }
            if ($this->db_config['driver'] == 'mysql') {
                $this->orm->getConnection($this->conName)->select($this->raw("SET SQL_MODE='NO_ZERO_DATE,NO_ZERO_IN_DATE';"));
            }

        } catch (\PDOException $e) {
            throw new AException($e->getMessage()."\n".$e->getTraceAsString(), $e->getCode(), $e->getFile(),
                $e->getLine());
        } catch (\Error $e) {
            exit($e->getMessage()."\n".$e->getTraceAsString());
        }
        $this->registry = Registry::getInstance();
    }

    /**
     * @param array $input
     *
     * @return array
     */
    protected function prepareDBConfig(array $input)
    {
        // convert array keys to lower case
        $cfg = [];
        foreach ($input as $k => $v) {
            $k = strtolower($k);
            if (is_array($v)) {
                foreach ($v as $kk => $vv) {
                    $cfg[$k][$kk] = $vv;
                }
            } else {
                $cfg[$k] = $v;
            }
        }
        //see if different hosts and ports
        if (is_array($cfg['db_host'])) {
            $cfg['read'] = [
                'host' => $cfg['db_host']['read'],
                'port' => $cfg['db_port']['read'],
            ];
            $cfg['write'] = [
                'host' => $cfg['db_host']['write'],
                'port' => $cfg['db_port']['write'],
            ];
            unset($cfg['db_host'], $cfg['db_port']);
        }

        if (isset($cfg['db_host'])) {
            $output['host'] = $cfg['db_host'];
            if (isset($cfg['db_port']) && $cfg['db_port']) {
                $output['port'] = $cfg['db_port'];
                //when use some different database port
                $output['read'] = ['host' => $cfg['db_host'], 'port' => $output['port']];
                $output['write'] = ['host' => $cfg['db_host'], 'port' => $output['port']];
                unset($output['host'], $output['port']);
            }
        } else {
            $output['read'] = $cfg['read'];
            $output['write'] = $cfg['write'];
        }

        $output += [
            'driver'    => $cfg['db_driver'],
            'database'  => $cfg['db_name'],
            'username'  => $cfg['db_user'],
            'password'  => $cfg['db_password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => $cfg['db_prefix'],
        ];

        return $output;
    }

    /**
     * @param string $sql
     * @param bool   $noexcept
     *
     * @return bool|\stdClass
     * @throws \Exception
     */
    public function query($sql, $noexcept = false)
    {

        if ($this->registry->has('extensions')) {
            $result = $this->registry->get('extensions')->hk_query($this, $sql, $noexcept);
        } else {
            $result = $this->_query($sql, $noexcept);
        }

        return $result;
    }

    /**
     * @param string $sql
     * @param bool   $noexcept
     *
     * @return bool|\stdClass
     *
     * @throws AException
     */
    public function _query($sql, $noexcept = false)
    {
        $orm = $this->orm;
        try {
            $result = $orm->getConnection($this->conName)->select($this->raw($sql));
            $data = json_decode(json_encode($result), true);
            /**
             * @var \stdClass $output
             */
            $output = new \stdClass();
            $output->row = isset($data[0]) ? $data[0] : [];
            //get total rows count for pagination
            if ($data && is_int(strpos($sql, $this->raw_sql_row_count()))) {
                $output->total_num_rows = $this->sql_get_row_count();
                $data[0]['total_num_rows'] = $output->total_num_rows;
            }
            $output->rows = $data;
            $output->num_rows = sizeof($data);

            return $output;
        } catch (QueryException $ex) {
            $this->error = 'SQL Error: '.$ex->getMessage()."\nError No: ".$ex->getCode()."\nSQL: \n".$sql;
            if (!$noexcept) {
                throw new AException($this->error, AC_ERR_MYSQL);
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $table_name
     *
     * @return string
     */
    public function table_name($table_name)
    {
        //detect if encryption is enabled
        $postfix = '';
        if (is_object($this->registry->get('dcrypt'))) {
            $postfix = $this->registry->get('dcrypt')->postfix($table_name);
        }

        return $this->db_config['prefix'].$table_name.$postfix;
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->db_config['database'];
    }

    /**
     * Get database name
     *
     * @return array
     */
    public function getDBConfig()
    {
        return $this->db_config;
    }

    /**
     * Get table prefix
     *
     * @return string
     */
    public function prefix()
    {
        return $this->db_config['prefix'];
    }

    public function escape($value, $with_special_chars = false)
    {
        $orm = $this->orm;
        //todo: need to fix sql-queries
        //Implement second parameter!!!!
        $output = $orm::connection($this->conName)->getPdo()->quote($value);

        return substr($output, 1, -1);
    }

    /**
     * @return int
     */
    public function getLastId()
    {
        $orm = $this->orm;

        return $orm::connection($this->conName)->getPdo()->lastInsertId();
    }

    /**
     * TODO: need to add support other db-drivers
     *
     * @return bool
     */
    public function raw_sql_row_count()
    {
        if ($this->db_config['driver'] == 'mysql') {
            // turn on total row calculation
            return (string)$this->raw('SQL_CALC_FOUND_ROWS');
        }

        return false;
    }

    /**
     * TODO: need to add support other db-drivers
     *
     * @return bool
     */
    public function sql_get_row_count()
    {
        if ($this->db_config['driver'] == 'mysql') {
            // turn on total row calculation
            return $this->orm->getConnection($this->conName)->selectOne('select found_rows() as total')->total;
        }

        return false;
    }

    /**
     * @param string $file
     *
     * @return bool
     * @throws \Exception
     */
    public function performSql($file)
    {

        if ($sql = file($file)) {
            $query = '';
            foreach ($sql as $line) {
                $tsl = trim($line);
                if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')) {
                    $query .= $line;
                    if (preg_match('/;\s*$/', $line)) {
                        $query = str_replace("`ac_", "`".$this->db_config['prefix'], $query);
                        $result = $this->_query($query, true);
                        if (!$result) {
                            return false;
                        }
                        $query = '';
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param string $function_name
     * @param array  $args
     *
     * @return \Illuminate\Database\Capsule\Manager | null
     */
    public function __call($function_name, $args)
    {
        $item = $this->orm->getConnection($this->conName);
        if (method_exists($item, $function_name)) {
            return call_user_func_array([$item, $function_name], $args);
        } else {
            return null;
        }
    }

    /**
     * @param string $table_name - table name without prefix
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table_name)
    {
        return $this->orm->getConnection($this->conName)->table($table_name);
    }

    public function raw($string)
    {
        return $this->orm->getConnection($this->conName)->raw($string);
    }

    /**
     * @return Capsule
     */
    public function getORM()
    {
        return $this->orm;
    }

    public function database()
    {
        return $this->orm->getConnection($this->conName)->getSchemaBuilder();
    }

    public function connection()
    {
        return $this->orm->getConnection($this->conName);
    }


    /**
     * @return mixed
     */
    public function CurrentTimeStamp()
    {
        return $this->raw('CURRENT_TIMESTAMP');
    }
}
