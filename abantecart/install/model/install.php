<?php

/*
------------------------------------------------------------------------------
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
------------------------------------------------------------------------------  
*/
namespace abantecart\install\model;
use abc\ABC;
use abc\core\helper\AHelperUtils;
use abc\core\engine\Model;
use abc\lib\ACache;
use abc\lib\ADB;
use abc\lib\AException;
use DirectoryIterator;

class ModelInstall extends Model{
	public $errors = array ();

	/**
	 * @param array $data
	 * @return bool
	 */
	public function validateSettings($data){
		if (!$data['admin_path']) {
			$this->errors['admin_path'] = 'Admin unique name is required!';
		} else {
			if (preg_match('/[^A-Za-z0-9_]/', $data['admin_path'])) {
				$this->errors['admin_path'] = 'Admin unique name contains non-alphanumeric characters!';
			}
		}

		if (!$data['db_driver']) {
			$this->errors['db_driver'] = 'Database driver required!';
		}
		if (!$data['db_host']) {
			$this->errors['db_host'] = 'Host name required!';
		}

		if (!$data['db_user']) {
			$this->errors['db_user'] = 'User name required!';
		}

		if (!$data['db_name']) {
			$this->errors['db_name'] = 'Database Name required!';
		}

		if (!$data['username']) {
			$this->errors['username'] = 'Username required!';
		}

		if (!$data['password']) {
			$this->errors['password'] = 'Password required!';
		}
		if ($data['password'] != $data['password_confirm']) {
			$this->errors['password_confirm'] = 'Password does not match the confirm password!';
		}

		$pattern = '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';

		if (!preg_match($pattern, $data['email'])) {
			$this->errors['email'] = 'Invalid E-Mail!';
		}

		if (!empty($data['db_prefix']) && preg_match('/[^A-Za-z0-9_]/', $data['db_prefix'])) {
			$this->errors['db_prefix'] = 'DB prefix contains non-alphanumeric characters!';
		}

		if ($data['db_driver']
				&& $data['db_host']
				&& $data['db_user']
				&& $data['db_password']
				&& $data['db_name']
		) {
			try{
				new ADB(array(
						'driver'    => $data['db_driver'],
						'host'      => $data['db_host'],
						'database'  => $data['db_name'],
						'username'  => $data['db_user'],
						'password'  => $data['db_password'],
						'charset'   => 'utf8',
						'collation' => 'utf8_unicode_ci',
						'prefix'    => $data['db_prefix']
						));
			} catch (AException $exception){
				$this->errors['warning'] = $exception->getMessage();
			}
		}

		if (!is_writable(ABC::env('DIR_CONFIG')) ) {
			$this->errors['warning'] = 'Error: Could not write to abc/config folder. Please check you have set the correct permissions on: ' . ABC::env('DIR_CONFIG') .'!';
		}

		if (!$this->errors) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function validateRequirements(){
		if (version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<') == true) {
			$this->errors['warning'] = 'Warning: You need to use PHP ' . ABC::env('MIN_PHP_VERSION') . ' or above for AbanteCart to work!';
		}

		if (!ini_get('file_uploads')) {
			$this->errors['warning'] = 'Warning: file_uploads needs to be enabled in PHP!';
		}

		if (ini_get('session.auto_start')) {
			$this->errors['warning'] = 'Warning: AbanteCart will not work with session.auto_start enabled!';
		}

		if (!extension_loaded('mysqli') && !extension_loaded('pdo_mysql')) {
			$this->errors['warning'] = 'Warning: MySQLi extension needs to be loaded for AbanteCart to work!';
		}

		if (!function_exists('simplexml_load_file')) {
			$this->errors['warning'] = 'Warning: SimpleXML functions needs to be available in PHP!';
		}

		if (!extension_loaded('gd')) {
			$this->errors['warning'] = 'Warning: GD extension needs to be loaded for AbanteCart to work!';
		}

		if (!extension_loaded('mbstring') || !function_exists('mb_internal_encoding')) {
			$this->errors['warning'] = 'Warning: MultiByte String extension needs to be loaded for AbanteCart to work!';
		}
		if (!extension_loaded('zlib')) {
			$this->errors['warning'] = 'Warning: ZLIB extension needs to be loaded for AbanteCart to work!';
		}

		if ( !is_writable(ABC::env('DIR_CONFIG')) ) {
			$this->errors['warning'] = 'Warning: abc/config folder and files needs to be writable for AbanteCart to be installed!';
		}

		if (!is_writable(ABC::env('DIR_SYSTEM'))) {
			$this->errors['warning'] = 'Warning: System directory and all its children files/directories need to be writable for AbanteCart to work!';
		}

		if (!is_writable(ABC::env('DIR_CACHE'))) {
			$this->errors['warning'] = 'Warning: Cache directory needs to be writable for AbanteCart to work!';
		}

		if (!is_writable(ABC::env('DIR_LOGS'))) {
			$this->errors['warning'] = 'Warning: Logs directory needs to be writable for AbanteCart to work!';
		}

		if (!is_writable(ABC::env('DIR_ASSETS') . 'images')) {
			$this->errors['warning'] = 'Warning: Image directory and all its children files/directories need to be writable for AbanteCart to work!';
		}

		if (!is_writable(ABC::env('DIR_ASSETS') . 'images/thumbnails')) {
			if (file_exists(ABC::env('DIR_ASSETS') . 'images/thumbnails') && is_dir(ABC::env('DIR_ASSETS') . 'images/thumbnails')) {
				$this->errors['warning'] = 'Warning: images/thumbnails directory needs to be writable for AbanteCart to work!';
			} else {
				$result = mkdir(ABC::env('DIR_ASSETS') . 'images/thumbnails', 0777, true);
				if ($result) {
					chmod(ABC::env('DIR_ASSETS') . 'images/thumbnails', 0777);
					chmod(ABC::env('DIR_ASSETS') . 'image', 0777);
				} else {
					$this->errors['warning'] = 'Warning: images/thumbnails does not exists!';
				}
			}
		}

		if (!is_writable(ABC::env('DIR_APP') . 'downloads')) {
			$this->errors['warning'] = 'Warning: Download directory needs to be writable for AbanteCart to work!';
		}

		if (!is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
			$this->errors['warning'] = 'Warning: Extensions directory needs to be writable for AbanteCart to work!';
		}

		if (!is_writable(ABC::env('DIR_ASSETS') . 'resources')) {
			$this->errors['warning'] = 'Warning: Resources directory needs to be writable for AbanteCart to work!';
		}

		if (!is_writable(ABC::env('DIR_SYSTEM'))) {
			$this->errors['warning'] = 'Warning: Admin/system directory needs to be writable for AbanteCart to work!';
		}

		if (!$this->errors) {
			return true;
		} else {
			return false;
		}
	}

	public function configure($data){
		if (!$data) {
			return false;
		}
		if (!ABC::env('DIR_CONFIG')) {
			ABC::env('DIR_CONFIG', ABC::env('DIR_APP') . 'system/config/');
		}

		$result = true;
		//write application config
		$server_name = getenv("SERVER_NAME");
		$unique_id = md5(time());
		$content = <<<EOD
<?php
return [
		'APP_NAME' => 'AbanteCart',
		'MIN_PHP_VERSION' => '7.0',
		'DIR_ROOT' => '{$data['root_dir']}',
		'DIR_APP' => '{$data['app_dir']}',
		'DIR_PUBLIC' => '{$data['public_dir']}',
		'SERVER_NAME' => '{$server_name}',
		'ADMIN_PATH' => '{$data['admin_path']}',
		'UNIQUE_ID' => '{$unique_id}',
		// SEO URL Keyword separator
		'SEO_URL_SEPARATOR' => '-',
		// EMAIL REGEXP PATTERN
		'EMAIL_REGEX_PATTERN' => '/^[A-Z0-9._%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i',
		//postfixes for template override
		'POSTFIX_OVERRIDE' => '.override',
		'POSTFIX_PRE' => '.pre',
		'POSTFIX_POST' => '.post',
		'APP_CHARSET' => 'UTF-8'
];
EOD;
		$file = fopen(ABC::env('DIR_CONFIG') . 'app.php', 'w');
		if (!fwrite($file, $content)) {
			$result = false;
		}
		fclose($file);

		//write database config
		$content = <<<EOD
<?php
// Database Configuration
return [
	'DB_DRIVER' => '{$data['db_driver']}',
	'DB_HOSTNAME' => '{$data['db_host']}',
	'DB_USERNAME' => '{$data['db_user']}',
	'DB_PASSWORD' => '{$data['db_password']}',
	'DB_DATABASE' => '{$data['db_name']}',
	'DB_PREFIX' => '{$data['db_prefix']}',
	'DB_CHARSET' => 'utf8',
	'DB_COLLATION' => 'utf8_unicode_ci'
];
EOD;
		$file = fopen(ABC::env('DIR_CONFIG') . 'database.php', 'w');
		if (!fwrite($file, $content)) {
			$result = false;
		}
		fclose($file);

		//write cache config
		$content = <<<EOD
<?php
return [
		'CACHE_DRIVER' => '{$data['cache_driver']}'
];
EOD;
		$file = fopen(ABC::env('DIR_CONFIG') . 'cache.php', 'w');
		if (!fwrite($file, $content)) {
			$result = false;
		}
		fclose($file);

		return $result;
	}

	public function RunSQL($data){
		$file = ABC::env('DIR_INSTALL') . 'abantecart_database.sql';
		if (!is_file($file)) {
			$this->errors[] = 'Error: file ' . $file . ' not found!';
			return false;
		}
		$sql = file($file);
		if ($sql === false) {
			$this->errors[] = 'Error: cannot open file ' . $file;
			return false;
		}

		$db = new ADB(array(
						'driver'    => $data['db_driver'],
						'host'      => $data['db_host'],
						'database'  => $data['db_name'],
						'username'  => $data['db_user'],
						'password'  => $data['db_password'],
						'charset'   => 'utf8',
						'collation' => 'utf8_unicode_ci',
						'prefix'    => $data['db_prefix']
						));
		$query = '';
		foreach ($sql as $line) {
			$tsl = trim($line);

			if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')) {
				$query .= $line;

				if (preg_match('/;\s*$/', $line)) {
					$query = str_replace(" `ac_", " `" . $data['db_prefix'], $query);
					$db->query($query); //no silence mode! if error - will throw to exception
					$query = '';
				}
			}
		}

		$db->query("SET CHARACTER SET utf8;");
		$db->query("SET @@session.sql_mode = 'MYSQL40';");
		$salt_key = AHelperUtils::genToken(8);
		$db->query(
				"INSERT INTO `" . $data['db_prefix'] . "users`
			SET user_id = '1',
				user_group_id = '1',
				email = '" . $db->escape($data['email']) . "',
				username = '" . $db->escape($data['username']) . "',
				salt = '" . $db->escape($salt_key) . "', 
				password = '" . $db->escape(sha1($salt_key . sha1($salt_key . sha1($data['password'])))) . "',
				status = '1',
				date_added = NOW();");

		$db->query(
				"UPDATE `" . $data['db_prefix'] . "settings` 
					SET value = '" . $db->escape($data['email']) . "' 
					WHERE `key` = 'store_main_email'; ");
		$db->query(
				"UPDATE `" . $data['db_prefix'] . "settings` 
					SET value = '" . $db->escape($data['http_server']) . "' 
					WHERE `key` = 'config_url'; ");
		if (ABC::env('HTTPS')) {
			$db->query(
					"UPDATE `" . $data['db_prefix'] . "settings` 
						SET value = '" . $db->escape($data['http_server']) . "' 
						WHERE `key` = 'config_ssl_url'; ");
			$db->query(
					"UPDATE `" . $data['db_prefix'] . "settings` 
						SET value = '2' 
						WHERE `key` = 'config_ssl'; ");
		}
		$db->query(
				"UPDATE `" . $data['db_prefix'] . "settings` 
				SET value = '" . $db->escape(AHelperUtils::genToken(16)) . "' 
				WHERE `key` = 'task_api_key'; ");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "settings` 
					SET `group` = 'config', `key` = 'install_date', value = NOW(); ");
		$db->query("UPDATE `" . $data['db_prefix'] . "products` SET `viewed` = '0';");

		//process triggers
		//$this->create_triggers($db, $data['db_name']);

		//run destructor and close db-connection
		unset($db);
		//clear cache dir in case of reinstall
		$cache = new ACache();
		$cache->setCacheStorageDriver('file');
		$cache->enableCache();
		$cache->remove('*');
		return true;
	}

	/**
	 * @param ADB $db
	 * @param string $database_name
	 */
	private function create_triggers($db, $database_name){
		$tables_sql = "
			SELECT DISTINCT TABLE_NAME 
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE COLUMN_NAME IN ('date_added')
			AND TABLE_SCHEMA='" . $db->escape($database_name) . "'";

		$query = $db->query($tables_sql);
		foreach ($query->rows as $t) {
			$table_name = $t['TABLE_NAME'];
			$trigger_name = $table_name . "_date_add_trg";

			$trigger_checker = $db->query("SELECT TRIGGER_NAME
								FROM information_schema.triggers
								WHERE TRIGGER_SCHEMA = '" . $db->escape($database_name) . "' AND TRIGGER_NAME = '" . $db->escape($trigger_name) . "'");
			if (!$query->row[0]) {
				//create trigger
				$sql = "
				CREATE TRIGGER `" . $db->escape($trigger_name) . "` BEFORE INSERT ON `" . $db->escape($table_name) . "` FOR EACH ROW
				BEGIN
					SET NEW.date_added = NOW();
				END;
				";
				$db->query($sql);
			}
		}
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function loadDemoData($data){
		//ABC::env('DIR_LANGUAGE', ABC::env('DIR_ABANTECART') . 'admin/languages/');
		$file = ABC::env('DIR_INSTALL') . 'demo_data/abantecart_demo_data.sql';
		if (!is_file($file)) {
			$this->errors[] = 'Error: file ' . $file . ' not found!';
			return false;
		}
		$sql = file($file);
		if ($sql === false) {
			$this->errors[] = 'Error: cannot open file ' . $file;
			return false;
		}

		$db = new ADB( array(
						'driver'    => $data['db_driver'],
						'host'      => $data['db_host'],
						'database'  => $data['db_name'],
						'username'  => $data['db_user'],
						'password'  => $data['db_password'],
						'charset'   => 'utf8',
						'collation' => 'utf8_unicode_ci',
						'prefix'    => $data['db_prefix']
						) );
		$db->query("SET NAMES 'utf8'");
		$db->query("SET CHARACTER SET utf8");

		$query = '';

		foreach ($sql as $line) {
			$tsl = trim($line);

			if (($sql != '') && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != '#')) {
				$query .= $line;

				if (preg_match('/;\s*$/', $line)) {
					$query = str_replace("DROP TABLE IF EXISTS `ac_", "DROP TABLE IF EXISTS `" . $data['db_prefix'], $query);
					$query = str_replace("CREATE TABLE `ac_", "CREATE TABLE `" . $data['db_prefix'], $query);
					$query = str_replace("INSERT INTO `ac_", "INSERT INTO `" . $data['db_prefix'], $query);

					$result = $db->query($query);

					if (!$result || $db->error) {
						die($db->error . '<br>' . $query);
					}

					$query = '';
				}
			}
		}
		$db->query("SET CHARACTER SET utf8");
		$db->query("SET @@session.sql_mode = 'MYSQL40'");

		//clear earlier created cache by AConfig and ALanguage classes in previous step
		$cache = new ACache();
		$cache->setCacheStorageDriver('file');
		$cache->enableCache();
		$cache->remove('*');
		return true;
	}

	public function getLanguages(){
		$query = $this->db->query("SELECT *
									FROM " . $this->db->table("languages")."
									ORDER BY sort_order, name");
		$language_data = array ();

		foreach ($query->rows as $result) {
			$language_data[$result['code']] = array (
					'language_id' => $result['language_id'],
					'name'        => $result['name'],
					'code'        => $result['code'],
					'locale'      => $result['locale'],
					'directory'   => $result['directory'],
					'filename'    => $result['filename'],
					'sort_order'  => $result['sort_order'],
					'status'      => $result['status']
			);
		}

		return $language_data;
	}

	public function buildAssets($data = array()){
		//process storefront assets
		$this->_copyDir(ABC::env('DIR_APP').'templates/default/storefront/assets', ABC::env('DIR_ASSETS').'templates/default/storefront');
		$this->_copyDir(ABC::env('DIR_APP').'templates/default/admin/assets', ABC::env('DIR_ASSETS').'templates/default/admin');
		return true;
	}
	protected function _copyDir($src, $dest){
		// If source is not a directory stop processing
		if (!is_dir($src)) return false;

		// If the destination directory does not exist create it
		if (!is_dir($dest)){
			if (!mkdir($dest)){
				// If the destination directory could not be created stop processing
				return false;
			}
		}

		// Open the source directory to read in files
		$i = new DirectoryIterator($src);
		foreach ($i as $f){
			$real_path = $f->getRealPath();
			/**
			 * @var $f DirectoryIterator
			 */
			if ($f->isFile()){
				copy($real_path, $dest . '/'. $f->getFilename());
			} else if (!$f->isDot() && $f->isDir()){
				$this->_copyDir($real_path, $dest . '/' . $f);
			}
		}
		return true;
	}
}
