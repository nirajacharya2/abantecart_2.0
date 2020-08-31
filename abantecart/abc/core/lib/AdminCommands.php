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
use abc\core\lib\contracts\AdminCommandsInterface;

/**
 * Class AdminCommands
 *
 * @package abc\core\lib
 * @property \abc\core\lib\ALanguageManager $language
 * @property \abc\core\engine\ALoader       $load
 * @property \abc\core\engine\AHtml         $html
 */
class AdminCommands implements AdminCommandsInterface
{
    protected $registry;
    public $errors = 0;
    public $commands = [];
    public $action_list = [
        'category'     => 'catalog/category/insert',
        'product'      => 'catalog/product/insert',
        'brand'        => 'catalog/manufacturer/insert',
        'manufacturer' => 'catalog/manufacturer/insert',
        'download'     => 'catalog/download',
        'review'       => 'catalog/review/insert',
        'attribute'    => 'catalog/attribute/insert',
        'customer'     => 'sale/customer/insert',
        'coupon'       => 'sale/coupon/insert',
        'discount'     => 'sale/coupon/insert',
        'block'        => 'design/blocks/insert',
        'menu'         => 'design/menu/insert',
        'content'      => 'design/content/insert',
        'page'         => 'design/content/insert',
        'banner'       => 'extension/banner_manager/insert',
        'store'        => 'setting/store/insert',
        'language'     => 'localisation/languages/insert',
        'currency'     => 'localisation/currency/insert',
        'location'     => 'localisation/location/insert',
        'tax'          => 'localisation/tax_class/insert',
    ];

    public function __construct()
    {
        if (!ABC::env('IS_ADMIN')) {
            throw new AException('Error: permission denied to access class AdminCommands', AC_ERR_LOAD);
        }
        $this->registry = Registry::getInstance();

        $text_data = $this->language->getASet('common/action_commands');
        $keys = preg_grep("/^command.*/", array_keys($text_data));
        foreach ($keys as $key) {
            //set CamelCase key
            $key = \H::camelize($key,'_');
            $this->commands[$key] = $text_data[$key];
        }
        unset($text_data);
        unset($keys);
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    /**
     * @param string $keyword
     *
     * @return array
     */
    public function getCommands($keyword)
    {
        if (!$keyword) {
            return [];
        }
        $result = [];
        //search for possible commands
        foreach ($this->commands as $key => $command) {
            $variations = explode(',', $command);
            //loop for command in the term
            foreach ($variations as $test) {
                $test = trim($test);
                //check exact match first
                if (strtolower($test) == strtolower($keyword)) {
                    $result['command'] = $test;
                    $result['key'] = $key;
                    $result['request'] = '';
                }
                preg_match("/^$test\s+(.*)/iu", $keyword, $matches);
                if (count($matches)) {
                    $result['command'] = $test;
                    $result['key'] = $key;
                    $result['request'] = $matches[1];
                    //no break. Take last matching command
                }
            }
        }

        if (!$result) {
            //nothing found
            return [];
        } else {
            //call method to perform action on the request in the command
            $function = "_".$result['key'];
            if (method_exists($this, $function)) {
                //filter duplicates and empty
                $result['found_actions'] = $this->filterResult($this->$function($result['request']));
            } else {
                //no right method to process found
                return [];
            }
        }

        return $result;
    }

    protected function commandOpen($request)
    {
        //some menu text
        $this->load->language('common/header');

        //return format (array): url =>, title =>, confirmation => (true, false)
        $result = [];
        //remove junk words
        $request = preg_replace('/menu|tab|page/', '', $request);
        $request = trim($request);
        //look for page in the menu matching
        $menu = new AMenu('admin');
        $menu_arr = $menu->getMenuItems();
        if (count($menu_arr)) {
            foreach ($menu_arr as $section_menu) {
                $sub_res = [];
                if (is_array($section_menu)) {
                    foreach ($section_menu as $menu) {
                        //load language for prospect controller
                        //Check that filename has proper name with no other special characters.
                        if (preg_match("/[\W]+/", str_replace('/', '_', $menu['item_url']))) {
                            $title = $this->language->get($menu['item_text']);
                        } else {
                            $this->load->language($menu['item_url'], 'silent');
                            $title =
                                $this->language->get($menu['item_text'])." / ".$this->language->get('heading_title');
                        }
                        if (preg_match("/$request/iu", $title)) {
                            $sub_res["title"] = $title;
                            $sub_res["url"] = $this->html->getSecureURL($menu['item_url']);
                            $sub_res["confirmation"] = false;
                            $result[] = $sub_res;
                        }
                    }
                }
            }
        }

        return $result;
    }

    protected function commandFind($request)
    {
        //return format (array): url =>, title =>, confirmation => (true, false)
        $result = [];
        $request = trim($request);

        //future!!! check for second level request and do specific area search

        //return global search result
        $result[0]["url"] = $this->html->getSecureURL('tool/global_search', '&search='.$request);
        $result[0]["confirmation"] = false;

        return $result;
    }

    protected function commandClearCache()
    {
        $result = [];
        $result[0]["url"] = $this->html->getSecureURL('tool/cache/delete', '&clear_all=all');
        $result[0]["confirmation"] = true;
        return $result;
    }

    protected function commandViewLog()
    {
        $result = [];
        $result[0]["url"] = $this->html->getSecureURL('tool/error_log');
        return $result;
    }

    protected function commandClearLog()
    {
        $result = [];
        $result[0]["url"] = $this->html->getSecureURL('tool/error_log/clearlog');
        $result[0]["confirmation"] = true;
        return $result;
    }

    protected function commandViewProduct($request)
    {
        $result = [];
        $request = trim($request);

        if (is_numeric($request)) {
            $result[0]["url"] = $this->html->getSecureURL('catalog/product/update', '&product_id='.$request);
        } else {
            $result[0]["url"] = $this->html->getSecureURL('catalog/product');
        }
        return $result;
    }

    protected function commandViewOrder($request)
    {
        $result = [];
        $request = trim($request);

        if (is_numeric($request)) {
            $result[0]["url"] = $this->html->getSecureURL('sale/order/details', '&order_id='.$request);
        } else {
            $result[0]["url"] = $this->html->getSecureURL('sale/order');
        }
        return $result;
    }

    protected function commandCreateNew($request)
    {
        //return format (array): url =>, title =>, confirmation => (true, false)
        $result = [];
        $request = trim($request);
        foreach ($this->action_list as $key => $rt) {
            if (preg_match("/$request/iu", $this->language->get($key))) {
                $sub_res["title"] = $key;
                $sub_res["url"] = $this->html->getSecureURL($rt);
                $sub_res["confirmation"] = false;
                $result[] = $sub_res;
            }
        }
        return $result;
    }

    protected function filterResult($data)
    {
        if (empty($data)) {
            return [];
        }
        $ret = [];

        foreach ($data as $record) {
            if (!empty($record["url"]) && preg_match("/rt=/", $record["url"])) {
                //check if already present in result
                $skip = false;
                foreach ($ret as $value) {
                    if ($value["url"] == $record["url"]) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $ret[] = $record;
                }
            }
        }

        return $ret;
    }

}

