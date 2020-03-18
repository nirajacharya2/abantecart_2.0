<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\engine;

use abc\core\ABC;
use abc\core\lib\AbcCache;
use abc\core\lib\ADB;
use abc\core\lib\ADebug;
use abc\core\lib\AError;
use abc\core\lib\AWarning;
use abc\core\lib\AException;
use abc\models\storefront\ModelLocalisationLanguage;
use Exception;
use H;
use ReflectionException;

/**
 * Class ALanguage
 *
 * @package abc\core\engine
 *
 */
class ALanguage
{
    public $entries = [];
    public $language_details;
    public $current_languages_scope = []; //This is and array of available languages for calling scope
    public $is_admin = 0;
    public $error = '';

    protected $code = '';
    /**
     * @var ADB
     */
    protected $db;
    /**
     * @var AbcCache
     */
    protected $cache;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var ALoader
     */
    protected $loader;
    protected $language_path;

    protected $available_languages = []; //Array of available languages configured in abantecart
    protected $current_language = []; //current used main language array data

    /**
     * @param Registry $registry
     * @param string $code - two letter language code
     * @param int $section - 0(storefront) or 1 (admin)
     *
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct($registry, $code = '', $section = 0)
    {
        $this->registry = $registry;
        if ($section === '') {
            $this->is_admin = (!ABC::env('IS_ADMIN') ? 0 : 1);
        } else {
            $this->is_admin = (int)$section;
        }

        //Load available languages;
        $this->loader = $registry->get('load');

        $result = $this->loader->model('localisation/language', 'silent');
        if ($result !== false) {
            /**
             * @var ModelLocalisationLanguage $model
             */
            $model = $registry->get('model_localisation_language');
            $this->available_languages = $model->getLanguages();
        } else {
            //problem no languages available
            $err = new AError('Error: no languages available in AbanteCart !', AC_ERR_LOAD);
            $err->toLog()->toDebug();
            throw new AException(
                'Error: Can not Load any language!',
                AC_ERR_LOAD
            );
        }

        //If No language code, we need to detect language, set site language to use and set content language separately
        if (!$code) {
            $this->setCurrentLanguage();
            //session language contains main language code
            $this->code = $registry->get('session')->data['language'];
        } else {
            $this->code = $code;
        }

        $this->db = $registry->get('db');
        $this->cache = Registry::cache();

        //current active language details
        $this->language_details = $this->getLanguageDetails($this->code);
        if (ABC::env('INSTALL')) {
            $this->language_path = ABC::env('DIR_INSTALL').'languages/'.$this->language_details['directory'].'/';
        } elseif ($this->is_admin) {
            $this->language_path = ABC::env('DIR_APP').'languages/'.$this->language_details['directory'].'/admin/';
        } else {
            $this->language_path = ABC::env('DIR_APP').'languages/'.$this->language_details['directory'].'/storefront/';
        }
        $this->entries = [];
    }

    /* Main Language API methods */

    // NOTE: Template language variables do not use ->get and loaded automatically in controller class.
    //		 There is no way to get access to used definitions and not possible to validate missing values

    /**
     * Get single language definition
     *
     * @param string $key - Language definition key
     * @param string $block - RT (block) for corresponding key (optional) with slash.
     *                      Block will be loaded to memory if not yet loaded
     * @param bool $silent
     *
     * @return null|string - Definition value
     * @throws ReflectionException
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($key, $block = '', $silent = false)
    {
        if (empty($key)) {
            return null;
        }

        //if no specific area specified return main language
        if (!empty($block)) {
            if (!$this->isLoaded($block)) {
                $this->_load($block);
            }
            $return_text = $this->getLanguageValue($key, $block);
        } else {
            if (!$silent) {
                $backtrace = debug_backtrace();
                $caller_file = $backtrace[0]['file'];
                $caller_file_line = $backtrace[0]['line'];
                $return_text = $this->getLastLanguageValue($key, $caller_file, $caller_file_line, $silent);
            } else {
                $return_text = $this->getLastLanguageValue($key, '', '', $silent);
            }
        }
        if (empty($return_text)) {
            $return_text = $key;
        }

        return $return_text;
    }

    /**
     * Get language definition for error. Function returns text anyway!
     *
     * @param string $key
     *
     * @return string
     * @throws ReflectionException
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get_error($key)
    {

        $result = $this->get($key);

        if ($key == $result || trim($result) == '') {
            $backtrace = debug_backtrace();
            $ts = time();
            $log_message = $ts."- Not described error.\n"
                ."File: ".$backtrace[0]['file']."\n"
                ."Line: ".$backtrace[0]['line']."\n"
                ."Args: ".var_export($backtrace[0]['args'], true)."\n";
            $e = new AError($log_message);
            $e->toDebug()->toLog();
            $result = "Not described error happened.";
            if (ABC::env('IS_ADMIN') === true) {
                $result .= "Check log for details. Code [".$ts."]";
            }
        }

        return $result;
    }

    /**
     * Get all language definitions
     *
     * Note: If RT is not provided definition keys will be taken
     * from main language section (ex: english.xml) if available
     *
     * @param string $block - RT (block) for corresponding key.
     *                          Block will be loaded to memory if not yet loaded
     *
     * @return array- Array with key/definition
     * @throws ReflectionException
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getASet($block = '')
    {
        //if no specific area specified return main language set
        if (empty($block) && empty($this->current_languages_scope)) {
            $block = $this->language_details['filename'];
        } else {
            if (!empty($block) && !$this->isLoaded($block)) {
                $this->_load($block);
            }
        }

        return $this->getLanguageSet($block);
    }

    /**
     * Load language definitions for provided RT(block) into memory
     * Main method called by default from controllers to load language definitions per RT
     * wrapper for loading language via hook
     * Note: If RT(block) is not provided definition keys will be taken from main
     *                      language section (ex: english.xml) if available
     *
     * @param string $block - RT (block) for corresponding key.
     * @param string $mode - Load mode. silent - No error if XML file is missing.
     *
     * @return array|null - Array with key/definition loaded
     * @throws ReflectionException
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function load($block = '', $mode = '')
    {
        //If $filename is not provided load current language main file
        if (!$block) {
            $block = $this->current_language['filename'];
        }
        $this->registry = Registry::getInstance();
        if ($this->registry->has('extensions')) {
            $result = $this->registry->get('extensions')->hk_load($this, $block, $mode);
        } else {
            $result = $this->_load($block, $mode);
        }

        return $result;
    }

    /**
     * Returns array of all available languages in the abantecart system
     * NOTE: These include active and inactive languages
     *    If only active languages needed use getActiveLanguages()
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        return $this->available_languages;
    }

    /**
     * Returns array of all active (status=1) languages
     *
     * @return array
     */
    public function getActiveLanguages()
    {
        $active_languages = [];
        foreach ($this->available_languages as $result) {
            if ($result['status'] == 1) {
                $active_languages[] = $result;
            }
        }

        return $active_languages;
    }

    /**
     * Load all information about specified language from language table.
     *
     * @param string $code - Language two letter code
     *
     * @return array - Array with language details
     */
    public function getLanguageDetails($code)
    {
        if (empty($code)) {
            return [];
        }

        foreach ($this->available_languages as $lang) {
            if ($lang['code'] == $code) {
                return $lang;
            }
        }

        return [];
    }

    /**
     * Load all information about specified language from language table.
     *
     * @param int $id - Language ID
     *
     * @return array - Array with language details
     */
    public function getLanguageDetailsByID($id)
    {
        if (!is_numeric($id)) {
            return [];
        }

        foreach ($this->available_languages as $lang) {
            if ($lang['language_id'] == $id) {
                return $lang;
            }
        }

        return [];
    }

    /**
     * Detect language used by the client's browser
     *
     * @return int|null|string - language code for detected locale
     * @throws AException
     * @throws ReflectionException
     */
    public function getClientBrowserLanguage()
    {
        $request = $this->registry->get('request');
        $browser_langs = (string)$request->server['HTTP_ACCEPT_LANGUAGE'];

        if ($browser_langs) {
            $parse = explode(';', $browser_langs);
            $browser_languages = array_map('trim', explode(',', $parse[0]));
            if ($browser_languages) {
                foreach ($browser_languages as $browser_language) {
                    $browser_language = trim($browser_language);
                    $browser_language = preg_replace('/[^a-zA-Z\-_]/', '', $browser_language);
                    //validate and ignore browser data if causing warnings
                    if (!$browser_language) {
                        continue;
                    }
                    foreach ($this->getActiveLanguages() as $key => $value) {
                        $locale = array_map('trim', explode(',', $value['locale']));
                        if (!$locale) {
                            continue;
                        }
                        //match browser language code with AbanteCart language locales
                        if (preg_grep("/^(".$browser_language.")/i", $locale)) {
                            //matching language was found
                            return $value['code'];
                        }
                    }
                }
            }
        }
        $default = $this->getDefaultLanguage();

        return $default['code'];
    }

    /**
     * Function to decide what language to use
     * Result: Selected language set to be current and saved to session
     */
    public function setCurrentLanguage()
    {
        $config = $this->registry->get('config');
        $session = $this->registry->get('session');
        $request = (object)$this->registry->get('request');

        //build code based array
        $languages = [];
        foreach ($this->getActiveLanguages() as $lng) {
            $languages[$lng['code']] = $lng;
        }

        //language code is provided as input. Higher priority
        $request_lang = $request->get['language'] ?? '';
        $request_lang = $request->post['language'] ?? $request_lang;
        unset($_GET['language'],$_POST['language']);

        if ($request_lang && array_key_exists($request_lang, $languages)) {
            $lang_code = $request_lang;
            //Session based language
        } elseif (isset($session->data['language']) && array_key_exists($session->data['language'], $languages)) {
            $lang_code = $session->data['language'];
            //Cookie based language
        } elseif (isset($request->cookie['language']) && array_key_exists($request->cookie['language'], $languages)) {
            $lang_code = $request->cookie['language'];
            //Try autodetect the language based on the browser languages
        } elseif ($detect = $this->getClientBrowserLanguage()) {
            $lang_code = $detect;
        } else {
            $lang_code = $config->get('config_storefront_language');
        }

        // check if is code of enabled language
        if (!isset($languages[$lang_code])) {
            $lang_code = key($languages);
            $error = new AError(
                'Error! Default language with code "'.$lang_code
                .'" is not available or disabled. Loading '.$languages[$lang_code]['name']
                .' language to keep system operating. Check your settings for default language.'
            );
            $error->toLog()->toDebug();
        }

        if (!isset($session->data['language']) || $session->data['language'] != $lang_code) {
            $session->data['language'] = $lang_code;
        }

        if ( ! headers_sent()
            && (!isset($request->cookie['language']) || $request->cookie['language'] != $lang_code)
        ) {
            //Set cookie for the language code
            setcookie('language',
                $lang_code,
                time() + 60 * 60 * 24 * 30,
                dirname($request->server['PHP_SELF']),
                null,
                ABC::env('HTTPS')
            );
        }
        //set current language
        $this->current_language = $languages[$lang_code];
        $config->set('storefront_language_id', $this->current_language['language_id']);

        if ($this->is_admin) {
            // set up language for content separately (admin only)
            $cont_lang_code = $config->get('admin_language');
            if (isset($request->get['content_language_code'])) {
                $cont_lang_code = $request->get['content_language_code'];
            } else {
                $cont_lang_code = !isset($session->data['content_language'])
                    ? $cont_lang_code
                    : $session->data['content_language'];
            }
            $this->setCurrentContentLanguage('', $cont_lang_code);
        }
    }

    /**
     * Set New Content Language in admin
     *
     * @param int|string $language_id
     * @param string $language_code
     *
     * @return NULL
     */
    public function setCurrentContentLanguage($language_id = '', $language_code = '')
    {
        $session = $this->registry->get('session');
        if ($language_id) {
            $session->data['content_language'] = $this->getLanguageCodeById($language_id);
            $session->data['content_language_id'] = $language_id;
            return true;
        } else {
            if ($language_code) {
                $session->data['content_language_id'] = $this->getLanguageIdByCode($language_code);
                $session->data['content_language'] = $language_code;
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Default site language Code
     *
     * @return string
     * @throws AException
     * @throws ReflectionException
     */
    public function getDefaultLanguageCode()
    {
        return $this->is_admin
            ? $this->registry->get('config')->get('admin_language')
            : $this->registry->get('config')->get('config_storefront_language');
    }

    /**
     * Default site language ID
     *
     * @return int
     * @throws AException
     * @throws ReflectionException
     */
    public function getDefaultLanguageID()
    {
        $info = $this->getDefaultLanguage();
        return $info['language_id'];
    }

    /**
     * Default site language info
     *
     * @return array
     * @throws AException
     * @throws ReflectionException
     */
    public function getDefaultLanguage()
    {
        //build code based array
        $languages = [];
        foreach ($this->available_languages as $lng) {
            $languages[$lng['code']] = $lng;
        }

        return $languages[$this->getDefaultLanguageCode()];
    }

    /**
     * Current site language Code
     *
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->current_language['code'];
    }

    /**
     * Current site language ID
     *
     * @return int
     */
    public function getLanguageID()
    {
        return $this->current_language['language_id'];
    }

    /**
     * Current site language details Array
     *
     * @return array
     */
    public function getCurrentLanguage()
    {
        return $this->current_language;
    }

    /**
     * Current content language ID (admin only)
     *
     * @return int
     */
    public function getContentLanguageID()
    {
        $session = $this->registry->get('session');

        return $session->data['content_language_id'];
    }

    /**
     * Current content language Code (admin only)
     *
     * @return string
     */
    public function getContentLanguageCode()
    {
        $session = $this->registry->get('session');

        return $session->data['content_language'];
    }

    /**
     * Check if block id a special case with main file (english, russian, etc ).
     * NOTE: Candidate for improvement. Rename these files to main.xml
     *
     * @param string $block - block name
     * @param string $lang_id - language ID (optional) if missing check if main block in any language
     *
     * @return bool
     */
    public function isMainBlock($block, $lang_id = '')
    {
        if (H::has_value($lang_id)) {
            $lang_det = $this->getLanguageDetailsByID($lang_id);
            if ($lang_det['filename'] == $block) {
                return true;
            }
        } else {
            foreach ($this->available_languages as $lang) {
                if ($lang['filename'] == $block) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Read XML language file and return array with definitions
     *
     * @param string $file - Full file path to language XML file
     *
     * @return array - Array with key/definition
     */
    public function ReadXmlFile($file)
    {
        $definitions = [];
        if (file_exists($file) && filesize($file) > 0) {
            $xml = simplexml_load_file($file);
            if (isset($xml->definition)) {
                foreach ($xml->definition as $item) {
                    $definitions[(string)$item->key] = trim((string)$item->value, "\t\n\r\0\x0B");
                }
            }
        }

        return $definitions;
    }

    /*
    * Set scope of available language blocks for the caller
    */
    public function setLanguageScope($block_list)
    {
        $this->current_languages_scope = $block_list;
    }

    /* END Main Language API methods */

    /**
     * load language
     *
     * @param string $filename
     * @param string $mode
     *
     * @return array|null
     * @throws ReflectionException
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function _load($filename, $mode = '')
    {

        if (empty($filename)) {
            return null;
        }
        $load_data = null;

        //Check if we already have language loaded. Skip and return the language set
        if ($this->isLoaded($filename)) {
            $load_data = $this->getLanguageSet($filename);
            return $load_data;
        }

        $cache_key = 'localization.lang.'.$this->code.'.'.(($this->is_admin) ? 'a' : 's').'.'.$filename;
        $cache_key = str_replace('/', '_', $cache_key);

        if ($this->cache) {
            $load_data = $this->cache->get($cache_key);
        }
        if ($load_data === null) {

            //Check that filename has proper name with no other special characters.
            $block_name = str_replace('/', '_', $filename);
            //prevent error for pre and post controllers
            $block_name = str_replace('.', '_', $block_name);
            if (preg_match("/[\W]+/", $block_name)) {
                $error = new AError('Error! Trying to load language with invalid path: "'.$filename.'"!');
                $error->toLog()->toDebug();
                return [];
            }

            $directory = $this->language_details['directory'];
            // nothing in cache. Start loading
            ADebug::checkpoint('ALanguage '.$this->language_details['name'].' '.$filename.' no cache, so loading');

            //try to get text data from db
            $_ = $this->loadFromDB($this->language_details['language_id'], $filename, $this->is_admin);
            if (empty($_)) {
                // nothing in the database. This block (rt) was never accessed
                // before for this language. Need to load definitions from XML
                $_ = $this->loadFromXml($filename, $directory, $mode);
                $this->saveToDb($filename, $_);
            } else {
                //We have something in database, look for missing or new values.
                //Do this silently in case language file is missing, Not a big problem
                $xml_vals = $this->loadFromXml($filename, $directory, 'silent');
                $diff = array_diff_assoc($xml_vals, $_);
                if($diff){
                    foreach ($diff as $key => $value) {
                        //missing value for $key
                        if (!isset($_[$key])) {
                            $_[$key] = $value;
                            $this->writeMissingDefinition(
                                [
                                    'language_id'    => $this->language_details['language_id'],
                                    'section'        => $this->is_admin,
                                    'block'          => $block_name,
                                    'language_key'   => $key,
                                    'language_value' => $value,
                                ]
                            );
                        }
                    }
                }

                if (count($xml_vals) != count($_)) {
                    //we have missing value in language XML. Probably newly added
                    foreach ($xml_vals as $key => $value) {
                        //missing value for $key
                        if (empty($_[$key])) {
                            $_[$key] = $value;
                            $this->writeMissingDefinition(
                                [
                                    'language_id'    => $this->language_details['language_id'],
                                    'section'        => $this->is_admin,
                                    'block'          => $block_name,
                                    'language_key'   => $key,
                                    'language_value' => $value,
                                ]
                            );
                        }
                    }
                }
            }

            $load_data = $_;
            if ($this->cache) {
                $this->cache->put($cache_key, $load_data);
            }
        }

        ADebug::checkpoint('ALanguage '.$this->language_details['name'].' '.$filename.' is loaded');
        $this->entries[$filename] = $load_data;
        //add filename to scope
        $this->current_languages_scope[] = $filename;

        return $this->entries[$filename];
    }

    /**
     * load all definitions for provided RT(block)
     *
     * @param string $block
     *
     * @return array
     */
    protected function getLanguageSet($block)
    {
        $entries = [];
        //if no rt look in all languages for last available translation
        if (empty ($block)) {

            $look_in_list = $this->current_languages_scope;
            //look in all languages and merge
            if (empty($look_in_list)) {
                $look_in_list = array_keys($this->entries);
            }

            foreach ($look_in_list as $block) {
                if (!empty($this->entries[$block])) {
                    $entries = array_merge($entries, $this->entries[$block]);
                }
            }
        } else {
            $entries = $this->entries[$block];
        }

        return $entries;
    }

    /**
     * Find language ID by provided language code
     *
     * @param string $code - two letter code
     *
     * @return null
     */
    protected function getLanguageIdByCode($code)
    {
        foreach ($this->available_languages as $lang) {
            if ($lang['code'] == $code) {
                return $lang['language_id'];
            }
        }

        return null;
    }

    /**
     * Find language code by provided language ID
     *
     * @param int $ID
     *
     * @return null
     */
    protected function getLanguageCodeById($ID)
    {
        foreach ($this->available_languages as $lang) {
            if ($lang['language_id'] == $ID) {
                return $lang['code'];
            }
        }

        return null;
    }

    /**
     * Check if block was loaded yet into memory
     *
     * @param string $block
     *
     * @return bool
     */
    protected function isLoaded($block)
    {
        if (isset ($this->entries[$block]) && count($this->entries[$block]) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param int $language_id
     * @param string $filename
     * @param int $section
     *
     * @return array
     * @throws Exception
     */
    protected function loadFromDB($language_id, $filename, $section)
    {
        if (empty ($language_id) || empty($filename)) {
            return [];
        }
        $block_name = str_replace('/', '_', $filename);
        $lang_array = [];

        $language_query = $this->db->table("language_definitions")
            ->where(
                [
                    'language_id' => (int)$language_id,
                    'section' => (int)$section,
                    'block' => $block_name
                ]
            )->get();
        if ($language_query) {
            foreach ($language_query as $language) {
                $lang_array[$language->language_key] = trim($language->language_value, "\t\n\r\0\x0B");
            }
        }

        return $lang_array;
    }

    /**
     * @param        $filename
     * @param  array $definitions
     *
     * @return bool
     * @throws Exception
     */
    protected function saveToDb($filename, $definitions)
    {
        if (!$definitions) {
            return false;
        }

        $block = str_replace('/', '_', $filename);
        ADebug::checkpoint('ALanguage '.$this->language_details['name'].' '.$block.' saving to database');

        $sql = "INSERT INTO ".$this->db->table_name("language_definitions");
        $sql .= " (language_id,block,section,language_key,language_value,date_added) VALUES ";
        $values = [];
        foreach ($definitions as $k => $v) {
            //preventing duplication sql-error by unique index
            $check_array = [
                'language_id'    => (int)$this->language_details['language_id'],
                'block'          => $this->db->escape($block),
                'section'        => $this->is_admin,
                'language_key'   => $this->db->escape($k),
                'language_value' => $this->db->escape($v),
            ];
            if ($this->isDefinitionInDb($check_array)) {
                continue;
            }

            $values[] = "('".(int)$this->language_details['language_id']."',
                          '".$this->db->escape($block)."',
                          '".$this->is_admin."',
                          '".$this->db->escape($k)."',
                          '".$this->db->escape($v)."',
                          NOW() )";
        }
        if ($values) {
            $sql = $sql.implode(', ', $values);
            $this->db->query($sql);
        }

        return true;
    }

    /**
     * Detect file for default or extension language
     *
     * @param string $filename
     * @param string $language_dir_name
     *
     * @return null|string
     */
    protected function detectLanguageXmlFile($filename, $language_dir_name = 'english')
    {
        if (empty($filename)) {
            return null;
        }
        $file_path = $this->language_path.$filename.'.xml';
        if ($this->registry->has('extensions')
            && $result = $this->registry->get('extensions')->isExtensionLanguageFile($filename, $language_dir_name, $this->is_admin)
        ) {
            if (is_file($file_path)) {
                $warning =
                    new AWarning("Extension <b>".$result['extension']."</b> overrides language file <b>".$filename
                        ."</b>");
                $warning->toDebug();
            }
            $file_path = $result['file'];
        }

        return $file_path;
    }

    /**
     * Load definition values from XML
     *
     * @param string $filename
     * @param string $directory
     * @param string $mode
     *
     * @return array|null
     * @throws ReflectionException
     * @throws AException
     */
    protected function loadFromXml($filename, $directory, $mode)
    {
        if (!$filename) {
            return null;
        }
        $definitions = [];
        ADebug::checkpoint('ALanguage '.$this->language_details['name'].' '.$filename
            .' prepare loading language from XML');

        //get default extension language file
        $default_language_info = $this->getDefaultLanguage();

        if ($filename == $directory) { // for common language file (english.xml. russian.xml, etc)
            $file_name = $default_language_info['filename'];
            $mode = 'silent';
        } else {
            $file_name = $filename;
        }
        // get path to actual language
        $file_path = $this->detectLanguageXmlFile($filename, $this->language_details['directory']);
        if (file_exists($file_path)) {
            ADebug::checkpoint('ALanguage '.$this->language_details['name'].' loading XML file '.$file_path);
            $definitions = $this->ReadXmlFile($file_path);
        } else {
            //Missing xml, now handle default language XML load
            $default_file_path = $this->detectLanguageXmlFile($file_name, $default_language_info['directory']);
            // if default language file path wrong - takes english as a fallback
            if (!file_exists($default_file_path) && $default_language_info['directory'] != 'english') {
                $file_name = $filename == $directory ? 'english' : $file_name;
                $default_file_path = $this->detectLanguageXmlFile($file_name, 'english');
            }
            if (file_exists($default_file_path)) {
                ADebug::checkpoint('ALanguage '.$this->language_details['name'].' loading default language XML file '
                    .$default_file_path);
                $definitions = $this->ReadXmlFile($default_file_path);
            } else {
                if ($mode != 'silent') {
                    $error = new AError('Missing default English definition XML file for '.$filename.' !');
                    $error->toLog()->toDebug();
                }
            }
        }

        //skip if not required and language file does not exist for silent mode.
        if (empty($definitions) && $mode != 'silent') {
            $error = new AError('Could not load language '.$filename.' from file "'.$file_path.'"!');
            $error->toLog()->toDebug();
        }

        return $definitions;
    }

    /**
     *  Call to get specific definition value for RT(block).
     *
     * @param string $key
     * @param string $filename
     *
     * @return null
     */
    protected function getLanguageValue($key, $filename)
    {
        if (empty ($filename) || empty ($key)) {
            return null;
        }

        return $this->entries[$filename][$key];
    }

    /**
     * Call to get specific definition value back traced in all available RTs(blocks)
     *
     * @param string $key
     * @param string $caller_file
     * @param string $caller_file_line
     * @param bool $silent
     *
     * @return null|string
     * @throws AException
     * @throws ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function getLastLanguageValue(
        $key,
        $caller_file = '',
        $caller_file_line = '',
        $silent = false
    ){
        if (empty ($key)) {
            return null;
        }

        //look in all blocks for last available translation based on list or all
        if (isset ($this->current_languages_scope) && sizeof($this->current_languages_scope) > 0) {
            $rev_language_blocks = array_reverse($this->current_languages_scope);
        } else {
            $rev_language_blocks = array_reverse(array_keys($this->entries));
        }

        $lang_value = '';
        foreach ($rev_language_blocks as $block) {
            $lang_value = $this->getLanguageValue($key, $block);
            if (isset($lang_value)) {
                break;
            }
        }

        // if value empty - write message based on the setting
        if (empty($lang_value) && $this->registry->get('config')->get('warn_lang_text_missing')) {
            $rt = $this->registry->get('request')->get['rt'];
            if (!$silent) {
                $this->registry->get('messages')->saveWarning(
                    'Language definition "'.$key.'" is missing for "'
                    .$this->available_languages[$this->code]['name'].'"',
                    'AbanteCart engine cannot find value of language definition with key "'.$key.'" in '.$caller_file
                    .' line '.$caller_file_line.($rt ? ' (rt='.$rt.')' : '')
                    .'.  Please add it in #admin#rt=localisation/language_definitions or run '
                    .'language translate process in #admin#rt=localisation/language'
                );
            }
        }

        return $lang_value;
    }

    /**
     * @param array $data
     *
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    protected function writeMissingDefinition($data)
    {
        $update_data = [];
        if ($this->is_admin) {
            $this->loader->model('localisation/language_definitions');
            $model = $this->registry->get('model_localisation_language_definitions');
            $model->addLanguageDefinition($data);
        } else {
            foreach ($data as $key => $val) {
                $update_data[$this->db->escape($key)] = $this->db->escape($val);
            }

            if (!$this->isDefinitionInDb($update_data)) {
                $sql = "INSERT INTO ".$this->db->table_name("language_definitions")."
                        (`".implode("`, `", array_keys($update_data))."`)
                        VALUES ('".implode("', '", $update_data)."') ";
                $this->db->query($sql);
                $this->cache->flush();
            }
        }
        if ($this->registry->get('config')->get('warn_lang_text_missing')) {
            $this->registry->get('messages')->saveNotice(
                'Missing language definition "'.$data['language_key']
                .'" was loaded for "'.$this->available_languages[$this->code]['name'].'" language',
                'Missing language definition with key "'.$data['language_key'].'" for block "'.$data['block']
                .'" was automatically added. Please check this '
                .'at #admin#rt=localisation/language_definitions to see or change value.'
            );
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    protected function isDefinitionInDb($data)
    {
        $sql = "SELECT *
                 FROM ".$this->db->table_name("language_definitions")."
                 WHERE language_id = '".$data['language_id']."'
                       AND `block` = '".$data['block']."'
                       AND `section` =  '".$data['section']."'
                       AND `language_key` =  '".$data['language_key']."'
                       AND `language_value` =  '".$data['language_value']."'";
        $exist = $this->db->query($sql);

        return ($exist->num_rows ? true : false);
    }
}