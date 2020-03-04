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

namespace abc\core\helper;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\core\lib\AConfig;
use abc\core\lib\ADataEncryption;
use abc\core\lib\AEncryption;
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AImage;
use abc\core\lib\JobManager;
use abc\core\lib\ASession;
use abc\core\lib\Atargz;
use abc\core\lib\AWarning;
use abc\models\order\Order;
use DateTime;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use PharData;
use wapmorgan\UnifiedArchive\UnifiedArchive;


/**
 * Class AHelperUtils
 *
 * @package abc\core
 * @deprecated
 */
class AHelperUtils extends AHelper
{
    /**
     * @param string $func_name
     *
     * @return bool
     */
    public static function isFunctionAvailable($func_name)
    {
        return function_exists($func_name);
    }

    /*
     * prepare prices and other floats for database writing,, based on locale settings of number formatting
     * */
    /**
     * @param string|float $value
     * @param string       $decimal_point
     *
     * @return float
     */
    public static function preformatFloat($value, $decimal_point = '.')
    {
        if ($decimal_point != '.') {
            $value = str_replace('.', '~', $value);
            $value = str_replace($decimal_point, '.', $value);
        }

        return (float)preg_replace('/[^0-9\-\.]/', '', $value);
    }

    /*
     * prepare integer for database writing
     * */
    /**
     * @param string $value
     *
     * @return int
     */
    public static function preformatInteger($value)
    {
        return (int)preg_replace('/[^0-9\-]/', '', $value);
    }

    /**
     * prepare string for text id
     *
     * @param string $value
     *
     * @return string
     */
    public static function preformatTextID($value)
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", "", $value));
    }

    /**
     * format money float based on locale
     *
     * @since 1.1.8
     *
     * @param $value
     * @param $mode (no_round => show number with real decimal, hide_zero_decimal => remove zeros from decimal part)
     *
     * @return string
     */

    public static function moneyDisplayFormat($value, $mode = 'no_round')
    {
        $registry = Registry::getInstance();

        $decimal_point = $registry->get('language')->get('decimal_point');
        $decimal_point = !$decimal_point ? '.' : $decimal_point;

        $thousand_point = $registry->get('language')->get('thousand_point');
        $thousand_point = !$thousand_point ? '' : $thousand_point;

        $currency = $registry->get('currency')->getCurrency();
        $decimal_place = (int)$currency['decimal_place'];
        $decimal_place = !$decimal_place ? 2 : $decimal_place;

        // detect if need to show raw number for decimal points
        // In admin, this is regardless of currency format. Need to show real number
        if ($mode == 'no_round' && $value != round($value, $decimal_place)) {
            //count if we have more decimal than currency configuration
            $decim_portion = explode('.', $value);
            if ($decimal_place < strlen($decim_portion[1])) {
                $decimal_place = strlen($decim_portion[1]);
            }
        }

        //if only zeros after decimal point - hide zeros
        if ($mode == 'hide_zero_decimal' && round($value) == round($value, $decimal_place)) {
            $decimal_place = 0;
        }

        return number_format((float)$value, $decimal_place, $decimal_point, $thousand_point);
    }

    /**
     * check that argument variable has value (even 0 is a value)
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function has_value($value)
    {
        if ($value !== (array)$value && $value !== '' && $value !== null) {
            return true;
        } else {
            if ($value === (array)$value && count($value) > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * check that argument variable has value (even 0 is a value)
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function is_serialized($value)
    {
        if (gettype($value) !== 'string') {
            return false;
        }
        $test_data = @unserialize($value);
        if ($value === 'b:0;' || $test_data !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * check that argument array is multidimensional
     *
     * @param array $array
     *
     * @return bool
     */
    public static function is_multi($array)
    {
        if ($array === (array)$array && count($array) != count($array, COUNT_RECURSIVE)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function convert input text to alpha numeric string for SEO URL use
     * if optional parameter object_key_name
     * (product, category, content etc) given function will return unique SEO keyword
     *
     * @param        $string_value
     * @param string $object_key_name
     * @param int $object_id
     *
     * @return string
     * @throws Exception
     */
    public static function SEOEncode($string_value, $object_key_name = '', $object_id = 0)
    {
        $seo_key = html_entity_decode($string_value, ENT_QUOTES, ABC::env('APP_CHARSET'));
        $seo_key = preg_replace('/[^\pL\p{Zs}0-9\s\-_]+/u', '', $seo_key);
        $seo_key = trim(mb_strtolower($seo_key));
        $seo_key = str_replace(' ', ABC::env('SEO_URL_SEPARATOR'), $seo_key);
        if (!$object_key_name) {
            return $seo_key;
        } else {
            //if $object_key_name given - check is seo-key unique and return unique
            return self::getUniqueSeoKeyword($seo_key, $object_key_name, $object_id);
        }
    }

    /**
     * @param        $seo_key
     * @param string $object_key_name
     * @param int $object_id
     *
     * @return string
     * @throws Exception
     */
    public static function getUniqueSeoKeyword($seo_key, $object_key_name = '', $object_id = 0)
    {
        $object_id = (int)$object_id;
        $registry = Registry::getInstance();
        $db = $registry->get('db');
        $sql = "SELECT `keyword`
            FROM ".$db->table_name('url_aliases')."
            WHERE `keyword` LIKE '".$db->escape($seo_key)."%'";
        if ($object_id) {
            // exclude keyword of given object (product, category, content etc)
            $sql .= " AND query<>'".$db->escape($object_key_name)."=".$object_id."'";
        }

        $result = $db->query($sql);
        if ($result->num_rows) {
            $keywords = [];
            foreach ($result->rows as $row) {
                $keywords[] = $row['keyword'];
            }

            $i = 0;
            while (in_array($seo_key, $keywords) && $i < 20) {
                $seo_key = $seo_key.ABC::env('SEO_URL_SEPARATOR').($object_id ? $object_id : $i);
                $i++;
            }
        }

        return $seo_key;
    }

    /**
     * Echo array with readable formal. Useful in debugging of array data.
     *
     * @param array $array_data
     */
    public static function echoArray($array_data)
    {
        $wrapper = '<div class="debug_alert alert alert-info alert-dismissible"'
            .' role="alert"><button type="button" class="close" data-dismiss="alert">'
            .'<span aria-hidden="true">&times;</span></button>';
        echo $wrapper;
        echo "<pre>";
        print_r($array_data);
        echo '</pre>';
        echo '</div>';
    }

    /**
     * Returns list of files from directory with subdirectories
     *
     * @param        $dir
     * @param string $file_ext
     *
     * @return array
     */
    public static function getFilesInDir($dir, $file_ext = '')
    {
        if (!is_dir($dir)) {
            return [];
        }
        $dir = rtrim($dir, DS);
        $result = [];

        foreach (glob($dir.DS."*") as $f) {
            if (is_dir($f)) { // if is directory
                $result = array_merge($result, self::getFilesInDir($f, $file_ext));
            } else {
                if ($file_ext && substr($f, -3) != $file_ext) {
                    continue;
                }
                $result[] = $f;
            }
        }

        return $result;
    }

    /**
     * @param $from
     * @param $to
     *
     * @return string
     */
    public static function getRelativePath($from, $to)
    {
        $from = explode(DS, $from);
        $to = explode(DS, $to);
        foreach ($from as $depth => $dir) {
            if (isset($to[$depth])) {
                if ($dir === $to[$depth]) {
                    unset($to[$depth], $from[$depth]);
                } else {
                    break;
                }
            }
        }
        $result = implode(DS, $to);

        return $result;
    }

    /**
     * @param $rel_file - relative file path
     * @param $src_dir  - source directory path
     * @param $dest_dir - destination directory path
     *
     * @return array
     */
    public static function CopyFileRelative($rel_file, $src_dir, $dest_dir)
    {
        $src_file = $src_dir.$rel_file;
        $dest_file = $dest_dir.$rel_file;
        if (!is_file($src_file)) {
            return [
                'result'  => false,
                'message' => __METHOD__.': Error: source file '.$src_file.' not found during copying',
            ];
        }
        $output = ['result' => true];

        //create nested dirs

        $file_dir = dirname($dest_file);
        if ($file_dir !== '') {
            $output = self::MakeNestedDirs($file_dir);
        }
        //copy file
        if ($output['result']) {
            $error = '';
            $result = copy($src_file, $dest_file);
            if (!$result) {
                $error = __METHOD__.': Error: source file '.$src_file.' copying error.';
            }

            return [
                'result'  => $output,
                'message' => $error,
            ];
        }

        return [
            'result'  => $output['result'],
            'message' => $output['message'],
        ];
    }

    /**
     * @param string $dir_full_path
     * @param int    $perms
     *
     * @return array
     */
    public static function MakeNestedDirs($dir_full_path, $perms = 0775)
    {
        $dirs = explode(DS, $dir_full_path);
        $dir = '';
        $output = ['result' => true];
        foreach ($dirs as $part) {
            $dir .= $part.DS;
            if (!is_dir($dir) && strlen($dir)) {
                $result = @mkdir($dir, $perms);
                if (!$result) {
                    return [
                        'result'  => false,
                        'message' => __METHOD__.': Cannot to create directory '.$dir,
                    ];
                }
                //because umask. test on aws-hosts
                chmod($dir, $perms);
            }
        }
        return $output;
    }

    /**
     * Wrapper of native mkdir() function.
     * Written because some host have issues with newly created directory permissions related to umask
     *
     * @param $dir_full_path
     * @param int $perms
     *
     * @return bool
     */
    public static function mkDir($dir_full_path, $perms = 0775)
    {
        $result = self::MakeNestedDirs($dir_full_path, $perms);
        return $result['result'];
    }

    /**
     * @param string $dir
     *
     * @return array|bool
     */
    public static function RemoveDirRecursively($dir = '')
    {
        //block calls from storefront
        if (!ABC::env('IS_ADMIN') || $dir == '..'.DS || $dir == DS || $dir == '.'.DS) {
            return false;
        }

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $obj) {
                if ($obj != "." && $obj != "..") {
                    @chmod($dir.DS.$obj, 0777);
                    $err = is_dir($dir.DS.$obj) ? self::RemoveDirRecursively($dir.DS.$obj) : @unlink($dir.DS.$obj);
                    if (!$err) {
                        $error_text = __METHOD__.": Error: Can't to delete file or directory: '".$dir.DS.$obj."'.";

                        return [
                            'result'  => false,
                            'message' => $error_text,
                        ];
                    }
                }
            }
            reset($objects);
            $result = @rmdir($dir);

            return ['result' => $result];
        } else {
            return [
                'result'  => false,
                'message' => __METHOD__.': Cannot remove '.$dir.'. It is not directory!',
            ];
        }
    }

    /**
     * Custom function for version compare between store version and extensions
     *    NOTE: Function will return false if major versions do not match.
     *
     * @param string $version1
     * @param string $version2
     * @param string $operator
     *
     * @return bool|mixed
     */
    public static function versionCompare($version1, $version2, $operator)
    {
        $version1 = explode('.', preg_replace('/[^0-9\.]/', '', $version1));
        $version2 = explode('.', preg_replace('/[^0-9\.]/', '', $version2));
        $i = 0;
        while ($i < 3) {
            if (isset($version1[$i])) {
                $version1[$i] = (int)$version1[$i];
            } else {
                $version1[$i] = ($i == 2 && isset($version2[$i])) ? (int)$version2[$i] : 99;
            }
            if (isset($version2[$i])) {
                $version2[$i] = (int)$version2[$i];
            } else {
                $version2[$i] = ($i == 2 && isset($version1[$i])) ? (int)$version1[$i] : 99;;
            }
            $i++;
        }

        if ($version1[1] > $version2[1]) {
            //not compatible, if major version is higher
            return false;
        }

        $version1 = implode('.', $version1);
        $version2 = implode('.', $version2);

        return version_compare($version1, $version2, $operator);
    }

    /**
     * @param int $error
     *
     * @return string
     */
    public static function getTextUploadError($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                $error_txt = 'The uploaded file exceeds the upload_max_filesize directive in php.ini (now '
                    .ini_get('upload_max_filesize').')';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_txt = 'The uploaded file exceeds the MAX_FILE_SIZE'
                    .' directive that was specified in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_txt = 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_txt = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_txt = 'Missing a php temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_txt = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_txt = 'File upload stopped by php-extension';
                break;
            default:
                $error_txt = 'Some problem happen with file upload. Check error log for more information';
        }

        return $error_txt;
    }

    /*
     * DATETIME functions
     */

    /*
    *  Convert PHP date format to datepicker date format.
    *  AbanteCart base date format on language setting date_format_short that is PHP date function format
    *  Convert to datepicker format
    *  References:
    *  http://docs.jquery.com/UI/Datepicker/formatDate
    *  http://php.net/manual/en/function.date.php
    */
    /**
     * @param string $date_format
     *
     * @return string
     */
    public static function format4Datepicker($date_format)
    {
        $new_format = $date_format;
        $new_format = preg_replace('/d/', 'dd', $new_format);
        $new_format = preg_replace('/j/', 'd', $new_format);
        $new_format = preg_replace('/l/', 'DD', $new_format);
        $new_format = preg_replace('/z/', 'o', $new_format);
        $new_format = preg_replace('/m/', 'mm', $new_format);
        $new_format = preg_replace('/n/', 'm', $new_format);
        $new_format = preg_replace('/F/', 'MM', $new_format);
        $new_format = preg_replace('/Y/', 'yy', $new_format);

        return $new_format;
    }

    /**
     * Function to format date in database format (ISO) to int format
     *
     * @param string $string_date
     *
     * @return int
     */
    public static function dateISO2Int($string_date)
    {
        $string_date = trim($string_date);
        $is_datetime = strlen($string_date) > 10 ? true : false;

        return self::dateFromFormat($string_date, ($is_datetime ? 'Y-m-d H:i:s' : 'Y-m-d'));
    }

    /**
     * Function to format date from int to database format (ISO)
     *
     * @param int $int_date
     *
     * @return false|string
     */
    public static function dateInt2ISO($int_date)
    {
        return date('Y-m-d H:i:s', $int_date);
    }

    /**
     * Function to format date from format in the display (language based) to database format (ISO)
     * Param: date in specified format, format based on PHP date function (optional)
     * Default format is taken from current language date_format_short setting
     *
     * @param string $string_date
     * @param string $format
     *
     * @return false|string
     */
    public static function dateDisplay2ISO($string_date, $format = '')
    {

        if (empty($format)) {
            $registry = Registry::getInstance();
            $format = $registry->get('language')->get('date_format_short');
        }

        if ($string_date) {
            return self::dateInt2ISO(self::dateFromFormat($string_date, $format));
        } else {
            return '';
        }
    }

    /**
     * Function to format date from database format (ISO) into the display (language based) format
     * Param: iso date, format based on PHP date function (optional)
     * Default format is taken from current language date_format_short setting
     *
     * @param string $iso_date
     * @param string $format
     *
     * @return false|string
     */
    public static function dateISO2Display($iso_date, $format = '')
    {

        if (empty($format)) {
            $registry = Registry::getInstance();
            $format = $registry->get('language')->get('date_format_short');
        }
        $empties = ['0000-00-00', '0000-00-00 00:00:00', '1970-01-01', '1970-01-01 00:00:00'];
        if ($iso_date && !in_array($iso_date, $empties)) {
            return date($format, self::dateISO2Int($iso_date));
        } else {
            return '';
        }

    }

    /**
     * Function to format date from integer into the display (language based) format
     * Param: int date, format based on PHP date function (optional)
     * Default format is taken from current language date_format_short setting
     *
     * @param int    $int_date
     * @param string $format
     *
     * @return false|string
     */
    public static function dateInt2Display($int_date, $format = '')
    {

        if (empty($format)) {
            $registry = Registry::getInstance();
            $format = $registry->get('language')->get('date_format_short');
        }

        if ($int_date) {
            return date($format, $int_date);
        } else {
            return '';
        }

    }

    /**
     * Function to show Now date (local time) in the display (language based) format
     * Param: format based on PHP date function (optional)
     * Default format is taken from current language date_format_short setting
     *
     * @param string $format
     *
     * @return false|string
     */
    public static function dateNowDisplay($format = '')
    {
        if (empty($format)) {
            $registry = Registry::getInstance();
            $format = $registry->get('language')->get('date_format_short');
        }

        return date($format);
    }

    /**
     * @param             $string_date
     * @param             $date_format
     * @param null|string $timezone
     *
     * @return int|null
     */
    public static function dateFromFormat($string_date, $date_format, $timezone = null)
    {
        $date = new DateTime();
        $timezone = is_null($timezone) ? $date->getTimezone() : $timezone;
        if (empty($date_format)) {
            return null;
        }
        $string_date = empty($string_date) ? date($date_format) : $string_date;

        $iso_date = DateTime::createFromFormat($date_format, $string_date, $timezone);
        $result = $iso_date ? $iso_date->getTimestamp() : null;

        return $result;
    }

    /**TODO: is really needed??
     *
     * @param $date
     * @param $format
     *
     * @return array|bool
     */
    public static function strptime($date, $format)
    {
        if (function_exists("\strptime")) {
            return strptime($date, $format);
        }

        //strptime function with solution for windows
        $masks = [
            '%d' => '(?P<d>[0-9]{2})',
            '%m' => '(?P<m>[0-9]{2})',
            '%Y' => '(?P<Y>[0-9]{4})',
            '%H' => '(?P<H>[0-9]{2})',
            '%M' => '(?P<M>[0-9]{2})',
            '%S' => '(?P<S>[0-9]{2})',
        ];

        $regexp = "#".strtr(preg_quote($format), $masks)."#";
        if (!preg_match($regexp, $date, $out)) {
            return false;
        }

        $ret = [
            "tm_sec"  => (int)$out['S'],
            "tm_min"  => (int)$out['M'],
            "tm_hour" => (int)$out['H'],
            "tm_mday" => (int)$out['d'],
            "tm_mon"  => $out['m'] ? $out['m'] - 1 : 0,
            "tm_year" => $out['Y'] > 1900 ? $out['Y'] - 1900 : 0,
        ];

        return $ret;
    }

    /**
     * @param string $extension_txt_id
     *
     * @return \SimpleXMLElement | false
     * @throws \ReflectionException
     */
    public static function getExtensionConfigXml($extension_txt_id)
    {
        $registry = Registry::getInstance();
        $result = $registry->get($extension_txt_id.'_configXML');

        if (!is_null($result)) {
            return $result;
        }

        $extension_txt_id = str_replace('../', '', $extension_txt_id);
        $filename = ABC::env('DIR_APP_EXTENSIONS').$extension_txt_id.'/config.xml';
        if (!is_file($filename) || !is_readable($filename)) {
            $ext_configs = false;
        } else {
            /**
             * @var $ext_configs \SimpleXMLElement|false
             */
            $ext_configs = @simplexml_load_file($filename);
        }

        if ($ext_configs === false) {
            $err_text = 'Error: cannot to load config.xml of extension '.$extension_txt_id.'.';
            $error = new AError($err_text);
            $error->toLog()->toDebug();
            foreach (libxml_get_errors() as $error) {
                $err = new AError($error->message);
                $err->toLog()->toDebug();
            }

            return false;
        }

        /**
         * DOMDocument of extension config
         *
         * @var DOMDocument $base_dom
         */
        $base_dom = new DOMDocument();
        $base_dom->load($filename);
        $xpath = new DOMXpath($base_dom);
        /**
         * @var  \DOMNodeList $firstNode
         */
        $firstNode = $base_dom->getElementsByTagName('settings');
        // check is "settings" entity exists
        if (is_null($firstNode->item(0))) {
            /**
             * @var  \DOMNode $node
             */
            $node = $base_dom->createElement("settings");
            $base_dom->appendChild($node);
        } else {
            /**
             * @var  \DOMElement $fst
             */
            $fst = $base_dom->getElementsByTagName('settings')->item(0);
            /**
             * @var  \DOMNode $firstNode
             */
            $firstNode = $fst->getElementsByTagName('item')->item(0);
        }

        $xml_files = [
            'top'    => [
                ABC::env('DIR_CORE').'extension'.DS.'default'.DS.'config_top.xml',
                ABC::env('DIR_CORE').'extension'.DS.(string)$ext_configs->type.DS.'config_top.xml',
            ],
            'bottom' => [
                ABC::env('DIR_CORE').'extension'.DS.'default'.DS.'config_bottom.xml',
                ABC::env('DIR_CORE').'extension'.DS.(string)$ext_configs->type.DS.'config_bottom.xml',
            ],
        ];

        // then loop for all additional xml-config-files
        foreach ($xml_files as $place => $files) {
            foreach ($files as $filename) {
                if (file_exists($filename)) {
                    $additional_config = @simplexml_load_file($filename);
                    //if error - writes all
                    if ($additional_config === false) {
                        foreach (libxml_get_errors() as $error) {
                            $err = new AError($error->message);
                            $err->toLog()->toDebug();
                        }
                    }
                    // loop by all settings items
                    foreach ($additional_config->settings->item as $setting_item) {
                        /**
                         * @var  \SimpleXmlElement $setting_item
                         */
                        $attr = $setting_item->attributes();
                        $item_id = $extension_txt_id.'_'.$attr['id'];
                        $is_exists = $ext_configs->xpath('/extension/settings/item[@id=\''.$item_id.'\']');
                        if (!$is_exists) {
                            // remove item that was appended on previous cycle from additional xml (override)
                            $qry = "/extension/settings/item[@id='".$item_id."']";
                            $existed = $xpath->query($qry);
                            if (!is_null($existed)) {
                                foreach ($existed as $node) {
                                    $node->parentNode->removeChild($node);
                                }
                            }
                            // rename id for settings item
                            $setting_item['id'] = $item_id;
                            //converts simpleXMLElement node to DOMDocument node for inserting
                            $item_dom_node = dom_import_simplexml($setting_item);
                            $item_dom_node = $base_dom->importNode($item_dom_node, true);
                            $setting_node = $base_dom->getElementsByTagName('settings')->item(0);
                            if ($place == 'top' && !is_null($firstNode)) {
                                $setting_node->insertBefore($item_dom_node, $firstNode);
                            } else {
                                $setting_node->appendChild($item_dom_node);
                            }
                        }
                    }
                }
            }
        }

        //remove all disabled items from list
        $qry = '/extension/settings/item[disabled="true"]';
        $existed = $xpath->query($qry);
        if (!is_null($existed)) {
            foreach ($existed as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        $result = simplexml_import_dom($base_dom);
        $registry->set($extension_txt_id.'_configXML', $result);

        return $result;
    }

    /**
     * Function for starting new storefront session for control panel user
     * NOTE: do not try to save into session any data after this function call!
     * Also function returns false on POST-requests!
     *
     * @param       $user_id int - control panel user_id
     * @param array $data    data for writing into new session storage
     *
     * @return bool
     */
    public static function startStorefrontSession($user_id, $data = [])
    {
        //NOTE: do not allow create sf-session via POST-request.
        // Related to language-switcher and enabled maintenance mode(see usages)
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            return false;
        }
        $data = (array)$data;
        $data['merchant'] = (int)$user_id;
        if (!$data['merchant']) {
            return false;
        }
        session_write_close();
        $session = new ASession(ABC::env('UNIQUE_ID')
            ? 'AC_SF_'.strtoupper(substr(ABC::env('UNIQUE_ID'), 0, 10))
            : 'AC_SF_PHPSESSID'
        );
        foreach ($data as $k => $v) {
            $session->data[$k] = $v;
        }
        session_write_close();

        return true;
    }

    /**
     * Function to built array with sort_order equally incremented
     *
     * @param array  $array to build sort order for
     * @param int    $min   - minimal sort order number (start)
     * @param int    $max   - maximum sort order number (end)
     * @param string $sort_direction
     *
     * @return array with sort order added.
     */
    public static function build_sort_order($array, $min, $max, $sort_direction = 'asc')
    {
        if (empty($array)) {
            return [];
        }

        //if no min or max, set interval to 10
        $return_arr = [];
        if ($max > 0) {
            $divider = 1;
            if (count($array) > 1) {
                $divider = (count($array) - 1);
            }
            $increment = ($max - $min) / $divider;
        } else {
            $increment = 10;
            $min = 10;
            $max = sizeof($array) * 10;
        }
        $prior_sort = -1;
        if ($sort_direction == 'asc') {
            foreach ($array as $id) {
                if ($prior_sort < 0) {
                    $return_arr[$id] = $min;
                } else {
                    $return_arr[$id] = round($prior_sort + $increment, 0);
                }
                $prior_sort = $return_arr[$id];
            }
        } else {
            if ($sort_direction == 'desc') {
                $prior_sort = $max + $increment;
                foreach ($array as $id) {
                    $return_arr[$id] = abs(round($prior_sort - $increment, 0));
                    $prior_sort = $return_arr[$id];
                }
            }
        }

        return $return_arr;
    }

    /**
     * Function to test if array is associative array
     *
     * @param array $test_array
     *
     * @return bool
     */
    public static function is_assoc($test_array)
    {
        return is_array($test_array) && array_diff_key($test_array, array_keys(array_keys($test_array)));
    }

    /**
     * Return project base
     *
     * @return string
     */
    public static function project_base()
    {
        $base = 'PGEgaHJlZj0iaHR0cDovL3d3dy5hYmFudGVjYXJ0LmNvbSIgdGFyZ2V0PSJfYWJhbnRlY2FydCI';
        $base .= 'gdGl0bGU9IklkZWFsIE9wZW5Tb3VyY2UgRS1jb21tZXJjZSBTb2x1dGlvbiI+QWJhbnRlQ2FydDwvYT4=';

        return base64_decode($base);
    }

    /**
     * Validate if string is HTML
     *
     * @param string $test_string
     *
     * @return bool
     */
    public static function is_html($test_string)
    {
        if ($test_string != strip_tags($test_string)) {
            return true;
        }

        return false;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string     $email The email address
     * @param int|string $s     Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string     $d     Default image set to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string     $r     Maximum rating (inclusive) [ g | pg | r | x ]
     *
     * @return String containing either just a URL or a complete image tag
     */
    public static function getGravatar($email = '', $s = 80, $d = 'mm', $r = 'g')
    {
        if (empty($email)) {
            return null;
        }
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=".$s."&d=".$d."&r=".$r;

        return $url;
    }

    public static function compressTarGZ($tar_filename, $tar_dir, $compress_level = 5)
    {
        if (!$tar_filename || !$tar_dir) {
            return false;
        }
        $compress_level = ($compress_level < 1 || $compress_level > 9) ? 5 : $compress_level;
        $exit_code = 0;
        if (pathinfo($tar_filename, PATHINFO_EXTENSION) == 'gz') {
            $filename = rtrim($tar_filename, '.gz');
        } else {
            $filename = $tar_filename.'.tar.gz';
        }
        $tar = rtrim($tar_filename, '.gz');
        //remove archive if exists
        if (is_file($tar_filename)) {
            unlink($tar_filename);
        }
        if (is_file($filename)) {
            unlink($filename);
        }
        if (is_file($tar)) {
            unlink($tar);
        }

        if (class_exists('\PharData')) {
            try {
                $a = new PharData($tar);
                //creates tar-file
                $a->buildFromDirectory($tar_dir);
                // remove tar-file after zipping
                if (file_exists($tar)) {
                    self::gzip($tar, $compress_level);
                    unlink($tar);
                }
            } catch (\PharException $e) {
                $error = new AError('Tar GZ compressing error: '.$e->getMessage());
                $error->toLog()->toDebug();
                $exit_code = 1;
            }
        } else {
            //class pharData does not exists.
            //set mark to use targz-lib
            $exit_code = 1;
        }

        if ($exit_code) {
            $registry = Registry::getInstance();
            $registry->get('load')->library('targz');
            $targz = new Atargz();

            return $targz->makeTar($tar_dir.$tar_filename, $filename, $compress_level);
        } else {
            return true;
        }
    }

    /**
     * @param string      $src
     * @param int         $level
     * @param string|bool $dst
     *
     * @return bool
     */
    public static function gzip($src, $level = 5, $dst = false)
    {
        if (!$src) {
            return false;
        }

        if ($dst == false) {
            $dst = $src.".gz";
        }
        if (file_exists($src)) {
            $src_handle = fopen($src, "r");
            if (!file_exists($dst)) {
                $dst_handle = gzopen($dst, "w$level");
                while (!feof($src_handle)) {
                    $chunk = fread($src_handle, 2048);
                    gzwrite($dst_handle, $chunk);
                }
                fclose($src_handle);
                gzclose($dst_handle);

                return true;
            } else {
                error_log($dst." already exists");
            }
        } else {
            error_log($src." doesn't exist");
        }

        return false;
    }

    /**
     * Generate random word
     *
     * @param $length int  - {word length}
     *
     * @return string
     */
    public static function randomWord($length = 4)
    {
        $new_code_length = 0;
        $new_code = '';
        while ($new_code_length < $length) {
            $x = 1;
            $y = 3;
            $part = rand($x, $y);
            if ($part == 1) {// Numbers
                $a = 48;
                $b = 57;
            } elseif ($part == 2) {// UpperCase
                $a = 65;
                $b = 90;
            } else {
                // if part==3 LowerCase
                $a = 97;
                $b = 122;
            }
            $code_part = chr(rand($a, $b));
            $new_code_length = $new_code_length + 1;
            $new_code = $new_code.$code_part;
        }

        return $new_code;
    }

    /**
     * Generate random token
     * Note: Starting PHP7 random_bytes() can be used
     *
     * @param $chars int  - {token length}
     *
     * @return string
     */
    public static function genToken($chars = 32)
    {
        $token = '';
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $max = strlen($codeAlphabet) - 1;
        for ($i = 0; $i < $chars; $i++) {
            $token .= $codeAlphabet[mt_rand(0, $max)];
        }

        return $token;
    }

    /**
     * TODO: in the future
     *
     * @param $zip_filename
     * @param $zip_dir
     */
    public static function compressZIP($zip_filename, $zip_dir)
    {
    }

    /**
     * @param string $filename
     *
     * @return mixed|string
     */
    public static function getMimeType($filename)
    {
        $filename = (string)$filename;
        $mime_types = [
            'txt'  => 'text/plain',
            'htm'  => 'text/html',
            'html' => 'text/html',
            'php'  => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'swf'  => 'application/x-shockwave-flash',
            'flv'  => 'video/x-flv',

            // images
            'png'  => 'image/png',
            'jpe'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif'  => 'image/tiff',
            'svg'  => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip'  => 'application/zip',
            'gz'   => 'application/gzip',
            'rar'  => 'application/x-rar-compressed',
            'exe'  => 'application/x-msdownload',
            'msi'  => 'application/x-msdownload',
            'cab'  => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3'  => 'audio/mpeg',
            'qt'   => 'video/quicktime',
            'mov'  => 'video/quicktime',

            // adobe
            'pdf'  => 'application/pdf',
            'psd'  => 'image/vnd.adobe.photoshop',
            'ai'   => 'application/postscript',
            'eps'  => 'application/postscript',
            'ps'   => 'application/postscript',

            // ms office
            'doc'  => 'application/msword',
            'rtf'  => 'application/rtf',
            'xls'  => 'application/vnd.ms-excel',
            'ppt'  => 'application/vnd.ms-powerpoint',

            // open office
            'odt'  => 'application/vnd.oasis.opendocument.text',
            'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        $pieces = explode('.', $filename);
        $ext = strtolower(array_pop($pieces));

        if (self::has_value($mime_types[$ext])) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            $mimetype = !$mimetype ? 'application/octet-stream' : $mimetype;

            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }

    /**
     * function detect is maximum execution time can be changed
     *
     * @return bool
     */
    public static function canChangeExecTime()
    {
        $old_set = ini_get('max_execution_time');
        set_time_limit('1234');
        if (ini_get('max_execution_time') == 1234) {
            return false;
        } else {
            set_time_limit($old_set);

            return true;
        }
    }

    /**
     * @return int|string
     */
    public static function getMemoryLimitInBytes()
    {
        $size_str = ini_get('memory_limit');
        switch (substr($size_str, -1)) {
            case 'M':
            case 'm':
                return (int)$size_str * 1048576;
            case 'K':
            case 'k':
                return (int)$size_str * 1024;
            case 'G':
            case 'g':
                return (int)$size_str * 1073741824;
            default:
                return $size_str;
        }
    }

    /**
     * @param string $validate_url
     *
     * @return bool
     */
    public static function is_valid_url($validate_url)
    {
        if (filter_var($validate_url, FILTER_VALIDATE_URL) === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get valid URL path considering *.php
     *
     * @param string $url
     *
     * @return string
     */
    public static function get_url_path($url)
    {
        $url_path1 = parse_url($url, PHP_URL_PATH);
        //do we have path with php in the string?
        // Treat case: /abantecart120/index.php/storefront/view/resources/images/18/6c/index.php
        $pos = stripos($url_path1, '.php');
        if ($pos) {
            //we have .php files specified.
            $filtered_url = substr($url_path1, 0, $pos + 4);

            return rtrim(dirname($filtered_url), '/.\\').'/';
        } else {
            return rtrim($url_path1, '/.\\').'/';
        }
    }

    /*
     * Return formatted execution back stack
     *
     * @param $depth int/string  - depth of the trace back ('full' to get complete stack)
     * @return string
    */
    public static function genExecTrace($depth = 5)
    {
        $e = new Exception();
        $trace = explode("\n", $e->getTraceAsString());
        array_pop($trace); // remove call to this method
        if ($depth == 'full') {
            $length = count($trace);
        } else {
            $length = $depth;
        }
        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = ' - '.substr($trace[$i], strpos($trace[$i], ' '));
        }

        return "Execution stack: \t".implode("\n\t", $result);
    }

    /**
     * Validate if directory exists and writable
     *
     * @param string $dir
     *
     * @return bool
     */
    public static function is_writable_dir($dir)
    {
        if (empty($dir)) {
            return false;
        } else {
            if (is_dir($dir) && is_writable($dir)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Create (single level) dir if does not exists and/or make dir writable
     *
     * @param string $dir
     *
     * @return bool
     */
    public static function make_writable_dir($dir)
    {
        if (empty($dir)) {
            return false;
        } else {
            if (self::is_writable_dir($dir)) {
                return true;
            } else {
                if (is_dir($dir)) {
                    //Try to make directory writable
                    chmod($dir, 0777);

                    return self::is_writable_dir($dir);
                } else {
                    //Try to create directory
                    mkdir($dir, 0777);
                    chmod($dir, 0777);

                    return self::is_writable_dir($dir);
                }
            }
        }
    }

    /**
     * Create (multiple level) dir if does not exists and/or make all missing writable
     *
     * @param string $path
     *
     * @return bool
     */
    public static function make_writable_path($path)
    {
        if (empty($path)) {
            return false;
        } else {
            if (self::is_writable_dir($path)) {
                return true;
            } else {
                //recurse if parent directory does not exists
                $parent = dirname($path);
                if (strlen($parent) > 1 && !file_exists($parent)) {
                    self::make_writable_path($parent);
                }
                mkdir($path, 0777, true);
                chmod($path, 0777);

                return true;
            }
        }
    }

    /**
     * Function to show readable file size
     *
     * @param     $bytes
     * @param int $decimals
     *
     * @return string
     */
    public static function human_filesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$sz[$factor];
    }

    /**
     * Function returns image dimensions
     *
     * @param $filename
     *
     * @return array|bool
     * @throws \ReflectionException
     */
    public static function get_image_size($filename)
    {
        if (file_exists($filename) && ($info = getimagesize($filename))) {
            return [
                'width'  => $info[0],
                'height' => $info[1],
                'mime'   => $info['mime'],
            ];
        }
        if ($filename) {
            $error = new  AError(
                'Error: Cannot get image size of file '.$filename.'. File not found or it\'s not an image!'
            );
            $error->toLog()->toDebug();
        }

        return [];
    }

    /**
     * Function to resize image if needed and put to new location
     * NOTE: Resource Library handles resize by itself
     *
     * @param string $orig_image (full path)
     * @param string $new_image (relative path start from env DIR_IMAGES)
     * @param int $width
     * @param int $height
     * @param int $quality
     *
     * @return string / path to new image
     * @throws \ReflectionException
     */
    public static function check_resize_image($orig_image, $new_image, $width, $height, $quality)
    {
        if (!is_file($orig_image) || empty($new_image)) {
            return null;
        }

        //if new file not yet present, check directory
        if (!file_exists(ABC::env('DIR_IMAGES').$new_image)) {
            $path = '';
            $directories = explode('/', dirname(str_replace('../', '', $new_image)));
            foreach ($directories as $directory) {
                $path = $path.DS.$directory;
                //do we have directory?
                if (!file_exists(ABC::env('DIR_IMAGES').$path)) {
                    // Make sure the index file is there
                    $indexFile = ABC::env('DIR_IMAGES').$path.DS.'index.php';
                    $old = umask(0);
                    $result = mkdir(ABC::env('DIR_IMAGES').$path, 0775);
                    umask($old);
                    if ($result) {
                        file_put_contents($indexFile, "<?php die('Restricted Access!'); ?>");
                        chmod($indexFile, 664);
                    }else{
                        $error = new AWarning(
                            'Cannot to create directory '
                            .ABC::env('DIR_IMAGES').$path.'. Please check permissions for '.ABC::env('DIR_IMAGES')
                        );
                        $error->toLog();
                    }
                }
            }


        }

        if (!file_exists(ABC::env('DIR_IMAGES').$new_image)
            || (filemtime($orig_image) > filemtime(ABC::env('DIR_IMAGES').$new_image))
        ){
            $image = new AImage($orig_image);
            $result = $image->resizeAndSave(ABC::env('DIR_IMAGES').$new_image,
                $width,
                $height,
                [
                    'quality' => $quality,
                ]);
            unset($image);
            if (!$result) {
                return null;
            }
        }

        return $new_image;
    }

    /**
     * Method returns instance of class. If class not found - returns default
     *
     * @param string $class_name - full class name (with namespace)
     * @param array  $args
     * @param string $default_class_name
     * @param array  $default_args
     *
     * @return object|false
     * @throws AException
     */
    public static function getInstance($class_name, $args = [], $default_class_name = '', $default_args = [])
    {
        $instance = false;
        if (!$class_name) {
            $class_name = $default_class_name;
        }
        if (!$class_name) {
            return false;
        }
        if (!$default_args) {
            $default_args = $args;
        }

        $classes = [$class_name => $args];
        if ($class_name != $default_class_name) {
            $classes[$default_class_name] = $default_args;
        }

        foreach ($classes as $class => $arguments) {
            //check if class loaded & try to load file
            if (!class_exists($class)) {
                $rel_path = self::getFileNameByClass($class);
                $abs_path = ABC::env('DIR_ROOT').$rel_path;

                if (is_file($abs_path)) {
                    include_once $abs_path;
                }
            }

            if (class_exists($class)) {
                try {
                    $reflection = new \ReflectionClass($class);
                    $instance = $reflection->newInstanceArgs($arguments);
                } catch (\ReflectionException $e) {
                    Registry::getInstance()->get('log')->write(
                        'AHelperUtils Error: '.$e->getMessage().' '.$e->getLine()
                    );
                }
            }

            if (is_object($instance)) {
                break;
            }
        }

        if (!$instance) {
            throw new AException('Class '.$class_name.' not found in config/*.classmap.php file', 1000);
        }
        return $instance;
    }

    /**
     * Function returns relative path of class based on full class name.
     * Example: on "\abc\core\lib\ALanguageManager" will return "/abc/lib/language_manager.php"
     *
     * @param string $class_name - full class name
     *
     * @return string
     */
    public static function getFileNameByClass($class_name)
    {
        $dirname = dirname(str_replace('\\', DS, $class_name));
        $dirname = ltrim($dirname, DS);
        $basename = basename(str_replace('\\', DS, $class_name));
        //add spaces to classname based on capital letters.
        $basename = preg_replace('/[A-Z]/', ' $0', $basename);
        $split = explode(' ', $basename);
        $split = array_map('strtolower', $split);
        unset($split[array_search('', $split)], $split[array_search('a', $split)]);

        $rel_path = ABC::env('DIR_ROOT').$dirname.DS.implode('_', $split).'.php';
        if (!is_file($rel_path)) {
            $rel_path = $dirname.DS.str_replace(' ', '', ucwords(implode(' ', $split))).'.php';
        }
        return $rel_path;
    }

    public static function extractArchive($archive_filename, $dest_directory)
    {
        $archive = UnifiedArchive::open($archive_filename);
        if (is_null($archive)) {
            return false;
        }
        $files = $archive->getFileNames();

        //check is archive tar.gz
        if (sizeof($files) == 1 && strtolower(pathinfo($files[0], PATHINFO_EXTENSION)) == 'tar') {
            $archive->extractNode($dest_directory, '/');
            $archive = UnifiedArchive::open(dirname($archive_filename).'/'.$files[0]);
            if (is_null($archive)) {
                //remove destination folder first
                //run pathinfo twice for tar.gz. files
                try {
                    $phar = new PharData($archive_filename);
                    $phar->extractTo($dest_directory, null, true);
                    return true;
                } catch (Exception $e) {
                    $error = new AError(__FUNCTION__.': '.$e->getMessage());
                    $error->toLog()->toDebug();
                    return false;
                }
            }
        }
        return (bool)$archive->extractNode($dest_directory, '/');
    }

    /**
     * @param array  $data
     * @param string $handler_alias
     *
     * @return array
     * @throws AException
     */
    public static function createJob(array $data, $handler_alias = 'JobManager')
    {

        $class_name = ABC::getFullClassName($handler_alias);
        if (!$class_name) {
            $output = [
                'job_id' => false,
                'errors' => [
                    'Handler alias "'.$handler_alias.'"" not registered in config/classmap',
                ],
            ];
        } else {
            /**
             * @var $handler JobManager
             */
            $handler = self::getInstance($class_name, ['registry' => Registry::getInstance()]);
            $result = $handler->addJob($data);
            $output = ['job_id' => $result, 'errors' => $handler->errors];
        }

        return $output;
    }

    /**
     * Function returns array with user_type, user name and user id for database audit log
     * This data will be set as database global variables and used by database triggers
     */
    public static function recognizeUser()
    {

        if (php_sapi_name() == 'cli') {
            $user_id = function_exists('posix_geteuid') ? posix_geteuid() : '1000';
            $output = [
                'user_type' => 0,
                'user_type_name' => 'cli',
                'user_id'   => $user_id,
                'user_name' => (function_exists('posix_getpwuid') ? posix_getpwuid($user_id)['name'] : 'system user'),
            ];
        } elseif (ABC::env('IS_ADMIN')) {
            if (!class_exists(Registry::class) || !Registry::user()) {
                return [];
            }
            $registry = Registry::getInstance();
            $user_id = $registry->get('user')->getId();
            $output = [
                'user_type' => 1,
                'user_type_name' => 'user',
                'user_id'   => $user_id,
                'user_name' => ($user_id ? $registry->get('user')->getUserName() : 'unknown admin'),
            ];
        } else {
            if (!class_exists(Registry::class)) {
                return [];
            }
            $user_id = Registry::customer() ? Registry::customer()->getId() : 0;
            $user_name = Registry::customer() ? Registry::customer()->getLoginName() : 'guest';
            $output = [
                'user_type' => 2,
                'user_type_name' => 'customer',
                'user_id'   => $user_id,
                'user_name' => $user_name
            ];
        }

        return $output;
    }

    /**
     * Function put abc-user info into sql-server variables
     * Used by triggers of audit-log
     *
     * @return array|bool
     */
    public static function setDBUserVars()
    {
        if (!class_exists(Registry::class)) {
            return [];
        }
        $user_info = self::recognizeUser();
        if (!$user_info || !$user_info['user_name']) {
            return false;
        }
        $registry = Registry::getInstance();
        $db = $registry->get('db');
        if (!$db) {
            return false;
        }

        switch (ABC::env('DB_CURRENT_DRIVER')) {
            case 'mysql':
                try {
                    $orm = $db->getORM();
                    $orm::select(
                        $orm::raw("SET @GLOBAL.abc_user_id = '".$user_info['user_id']."';")
                    );
                    $orm::select(
                        $orm::raw("SET @GLOBAL.abc_user_name = '".$user_info['user_name']."';")
                    );
                    $orm::select(
                        $orm::raw("SET @GLOBAL.abc_user_type = '".$user_info['user_type']."';")
                    );
                } catch (\Exception $e) {

                }
                return true;
        }
        return false;
    }

    /**
     * @param string $event_alias
     * @param array $args
     *
     * @return array|null
     * @throws AException
     */
    public static function event(string $event_alias, $args = [])
    {
        $registry = Registry::getInstance();
        /**
         * @var Dispatcher $event_dispatcher
         */
        $event_dispatcher = $registry->get('events');
        if (is_object($event_dispatcher)) {
            return $event_dispatcher->dispatch($event_alias, $args);
        } else {
            throw new AException('Event Dispatcher not found in Registry!', AC_ERR_CLASS_CLASS_NOT_EXIST);
        }
    }


    public static function df($var, $filename = 'debug.txt') {
        $backtrace = debug_backtrace();
        $backtracePath = [];
        foreach($backtrace as $k => $bt)
        {
            if($k > 1)
                break;
            $backtracePath[] = substr($bt['file'], strlen($_SERVER['DOCUMENT_ROOT'])) . ':' . $bt['line'];
        }

        $data = func_get_args();
        if(count($data) == 0)
            return;
        elseif(count($data) == 1)
            $data = current($data);

        if(!is_string($data) && !is_numeric($data) && !is_object($data))
            $data = var_export($data, 1);

     //   if (is_object($data)) {
       //     $data = ' Variable is Object, use DD like functions! ';
       // }

        file_put_contents(
            $filename,
            "\n--------------------------" . date('Y-m-d H:i:s ') . microtime()
            . "-----------------------\n Backtrace: " . implode(' → ', $backtracePath) . "\n"
            . $data, FILE_APPEND
        );
    }

    public static function dirIsEmpty($directory)
    {
        if(!is_dir($directory) || !is_readable($directory)){
            return false;
        }
        $content = glob(rtrim($directory,DS).DS.'*',GLOB_NOSORT);

        return ($content ? false : true);
    }

    public static function isCamelCase($className)
    {
        return (bool)preg_match('/^([A-Z][a-z0-9]+)+$/', $className);
    }

    /**
     * Function changes and cleans data base on entity codes, such as language_code, sku etc
     *
     * @param $string
     * @param int $width
     * @param bool $addEllipses
     *
     * @return array
     */
   /*
   NOT TESTED YET!!!
   public static function prepareDataForImport(array $data)
    {
        $registry = Registry::getInstance();
        $available_languages = $registry->get('language')->getAvailableLanguages();

        $language_list = [];
        foreach($available_languages as $lang){
            $language_list[$lang['code']] = $lang['language_id'];
        }

        if(isset($data['language_code']) && isset($language_list[$data['language_code']])){
            $output = $data;
            $output['language_id'] = $language_list[$data['language_code']];
            unset($output['language_code']);
        }elseif( isset($data['language_code']) && !isset($language_list[$data['language_code']]) ){
            $output = [];
        }else{
            $output = $data;
        }

        //go deep
        foreach($output as $k => &$item){
            if(!is_array($item)){ continue; }
            $item = self::prepareDataForImport($item);
        }
        return $output;
    }*/

    public static function stringTruncate($string, $width = 50, $addEllipses = false) {
        $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
        $parts_count = count($parts);

        $ellipses = false;

        $length = 0;
        $last_part = 0;
        for (; $last_part < $parts_count; ++$last_part) {
            $length += strlen($parts[$last_part]);
            if ($length > $width) {
                $ellipses = true;
                break;
            }
        }
        $result = implode(array_slice($parts, 0, $last_part));
        if ($ellipses && $addEllipses) {
            $result .= ' ...';
        }
        return $result;
    }

    /**
     * @param string $input
     * @param string $separator
     * @param bool $capitalizeFirstChar
     *
     * @return string
     */
    public static function camelize(string $input, $separator = '_', $capitalizeFirstChar = false)
    {
        $string = ucwords($input, $separator);
        if(!$capitalizeFirstChar){
            $string = lcfirst($string);
        }
        return str_replace($separator, '', $string);
    }

    /**
     * @return string
     */
    public static function genRequestId()
    {
        return  sprintf(
            "%08x",
            abs(crc32(self::getRemoteIP() . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT']))
        );
    }

    /**
     * @return mixed
     */
    public static function getRemoteIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * @param array $data
     * @param string $field
     * @param mixed $value
     *
     * @return array
     * @throws \abc\core\lib\AException
     */
    public static function filterByEncryptedField($data, $field, $value)
    {
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();
        if (!count($data)) {
            return [];
        }
        if (!self::has_value($field) || !self::has_value($value)) {
            return $data;
        }
        $result_rows = [];
        foreach ($data as $result) {
            if ($dcrypt->active) {
                $f_value = $dcrypt->decrypt_field($result[$field], $result['key_id']);
            } else {
                $f_value = $result[$field];
            }
            if (!(strpos(strtolower($f_value), strtolower($value)) === false)) {
                $result_rows[] = $result;
            }
        }
        return $result_rows;
    }

    public static function parseOrderToken( $ot )
    {
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();
        /**
         * @var AConfig $config
         */
        $config = Registry::config();
        if ( ! $ot || ! $config->get( 'config_guest_checkout' ) ) {
            return [];
        }

        //try to decrypt order token
        /**
         * @var AEncryption $enc
         */
        $enc = ABC::getObjectByAlias('AEncryption', [$config->get('encryption_key')]);
        $decrypted = $enc->decrypt( (string)$ot );
        list( $order_id, $email ) = explode( '::', $decrypted );

        $order_id = (int)$order_id;
        if ( ! $decrypted || ! $order_id || ! $email ) {
            return [];
        }

        $order = Order::find($order_id);
        $order_email = $order->email;
        if($dcrypt->active){
            $order_email = $dcrypt->decrypt_field($order_email, $order->key_id);
        }

        //compare emails
        if ( $order_email != $email ) {
            return [];
        }

        return [$order_id, $email];
    }

    /**
     * @param string $key - language definition key
     * @param string $block - language definition block
     * @param string $default_text - text in case when text by key not found
     * @param string $section - can be "storefront" or "admin" or empty(auto)
     *
     * @return null|string
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public static function lng(string $key, $block= '', $default_text = '', $section = ''){
        $registry = Registry::getInstance();
        if( $section ){
            $section = $section == 'admin' ? 1 : 0;
            $language = new ALanguage($registry, $registry::language()->getLanguageCode(), $section);

        }else{
            $language = Registry::language();
        }

        $text = $language->get($key, $block);
        //if text not found - set default
        if($text == $key){
            $text = $default_text;
        }
        return $text;
    }

    public static function SimplifyValidationErrors($array, &$errors){
        foreach($array as $rule => $msgArr){
            $errors[$rule] = implode(' ', $msgArr);
        }
    }

    public static function getAppErrorText()
    {
        $language = Registry::language();
        if (!ABC::env('IS_ADMIN')) {
            return 'Application Error!';
        }
        if ($language) {
            return sprintf($language->get('error_system'), Registry::html()->getSecureURL('tool/error_log'));
        } else {
            return 'Application Error! Please check error log for details.';
        }

    }

    /**
     * Return true if $table (short name) already joined
     *
     * @param $query
     * @param $table
     *
     * @return bool
     */
    public static function isJoined($query, $table)
    {
        $joins = new Collection($query->getQuery()->joins);
        return $joins->pluck('table')->contains($table);
    }
}
