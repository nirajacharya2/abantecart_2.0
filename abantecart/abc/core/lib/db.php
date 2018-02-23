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
use abc\core\engine\Registry;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ADB{
    /**
     * @var $orm Capsule
     */
    protected $orm;
    protected $db_config = array();
    public $error = '';
    public $registry;

    /**
     * @param array $db_config array(
     *                    'driver'    => 'mysql',
     *                    'host'      => 'localhost', // or array ['read' => '****', 'write' = '*****']
     *                    'port'      => '3306', // or array ['read' => '***', 'write' = '***']
     *                    'database'  => '***',
     *                    'username'  => '***',
     *                    'password'  => '***,
     *                    'charset'   => 'utf8',
     *                    'collation' => 'utf8_unicode_ci',
     *                    'prefix'    => 'ac_'
     * @throws AException
     */
    public function __construct( $db_config = array() ){
        if(!$db_config){
            throw new AException(AC_ERR_LOAD, 'Cannot initiate ADB class with empty config parameter!');
        }
        $this->db_config = $this->_prepare_config($db_config);

        try{
            $this->orm = new Capsule;
            $this->orm->addConnection($this->db_config);
            $this->orm->setAsGlobal();  //this is important
            $this->orm->bootEloquent();
        }catch(AException $e){
            throw new AException(AC_ERR_MYSQL, 'Error: Could not load ORM-database!');
        }
        $this->registry = Registry::getInstance();
    }

    /**
     * @param array $input
     *
     * @return array
     */
    protected function _prepare_config(array $input)
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
     * @param bool $noexcept
     * @return bool|\stdClass
     */
    public function query($sql, $noexcept = false){

        if ($this->registry->has('extensions')){
            $result = $this->registry->get('extensions')->hk_query($this, $sql, $noexcept);
        } else{
            $result = $this->_query($sql, $noexcept);
        }

        return $result;
    }
    /**
     * @param string $sql
     * @param bool $noexcept
     * @return bool|\stdClass
     * @throws AException
     */
    public function _query($sql, $noexcept = false){
        $orm = $this->orm;
        try{
            $result = $orm::select($orm::raw($sql));
            $data = json_decode(json_encode($result), true);
            /**
             * @var \stdClass $output
             */
            $output = new \stdClass();
            $output->row = isset($data[0]) ? $data[0] : array ();
            $output->rows = $data;
            $output->num_rows = sizeof($data);
            return $output;
        }catch(QueryException $ex){
            $this->error = 'SQL Error: ' . $ex->getMessage() . '<br />Error No: ' . $ex->getCode() . '<br />SQL: ' . $sql;
            if( !$noexcept ){
                throw new AException(AC_ERR_MYSQL, $this->error);
            }else{
                return false;
            }
        }
    }

    /**
     * @param string $table_name
     * @return string
     */
    public function table($table_name){
        //detect if encryption is enabled
        $postfix = '';
        if (is_object($this->registry->get('dcrypt'))){
            $postfix = $this->registry->get('dcrypt')->postfix($table_name);
        }
        return $this->db_config['prefix'] . $table_name . $postfix;
    }
    /**
     * Get database name
     * @return string
     */
    public function database(){
        return $this->db_config['database'];
    }
    /**
     * Get table prefix
     * @return string
     */
    public function prefix(){
        return $this->db_config['prefix'];
    }

    public function escape($value, $with_special_chars = false){
        $orm = $this->orm;
        //todo: need to fix sql-queries
        //Implement second parameter!!!!
        return str_replace("'","\'",$value);
        //return trim($orm::connection()->getPdo()->quote($value),"'"); // ??? it does not works
    }

    /**
     * @return int
     */
    public function getLastId(){
        $orm = $this->orm;
        return $orm::connection()->getPdo()->lastInsertId();
    }

    /**
     * @param string $file
     * @return bool
     */
    public function performSql($file){

        if ($sql = file($file)){
            $query = '';
            foreach ($sql as $line){
                $tsl = trim($line);
                if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')){
                    $query .= $line;
                    if (preg_match('/;\s*$/', $line)){
                        $query = str_replace("`ac_", "`" . $this->db_config['prefix'], $query);
                        $result = $this->_query($query, true);
                        if (!$result){
                            //todo: need to review
                            $err = $this->driver->getDBError();
                            $this->error = $err['error_text'];
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
     * @param array $args
     * @return \Illuminate\Database\Capsule\Manager | null
     */
    public function __call($function_name, $args){
        $item = $this->orm;
        if ( method_exists( $item, $function_name ) ){
            return call_user_func_array(array($item, $function_name), $args);
        } else {
            return null;
        }
    }
}
