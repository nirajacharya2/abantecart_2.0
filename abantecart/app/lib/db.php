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
namespace abc\lib;
use abc\core\Registry;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;

if (!defined('DIR_CORE')){
	header('Location: static_pages/');
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
     *                    'host'      => 'localhost',
     *                    'database'  => '***',
     *                    'username'  => '***',
     *                    'password'  => '***,
     *                    'charset'   => 'utf8',
     *                    'collation' => 'utf8_unicode_ci',
     *                    'prefix'    => 'ac_'
     * @throws AException
     */
	public function __construct( $db_config = array() ){
		$this->db_config = $db_config;
		try{
			$this->orm = new Capsule;
			$this->orm->addConnection($db_config);
			$this->orm->setAsGlobal();  //this is important
			$this->orm->bootEloquent();
		}catch(AException $e){
			throw new AException(AC_ERR_MYSQL, 'Error: Could not load ORM-database!');
		}
		$this->registry = Registry::getInstance();
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

	public function escape($value){
		$orm = $this->orm;
		//todo: need to fix sql-queries
		return trim($orm::connection()->getPdo()->quote($value),"'");
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
			return call_user_func(array($item, $function_name), $args);
		} else {
			return null;
		}
	}
}
