<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

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
use abc\core\lib\ADocument;
use abc\core\lib\ALanguageManager;
use abc\core\view\AView;
use abc\core\lib\ADebug;
use abc\core\lib\ARequest;
use abc\core\lib\AException;
use abc\models\admin\ModelLocalisationCountry;
use abc\models\admin\ModelLocalisationLanguage;
use abc\models\admin\ModelSettingStore;
use abc\models\storefront\ModelLocalisationZone;
use abc\models\storefront\ModelToolSeoUrl;
use Exception;
use H;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class AHtml
 * @method SelectboxHtmlElement buildSelectBox(array $data)
 * @method MultiSelectboxHtmlElement buildMultiSelectBox(array $data)
 * @method HiddenHtmlElement buildHidden(array $data)
 * @method MultivalueHtmlElement buildMultivalue(array $data)
 * @method MultivalueListHtmlElement buildMultivalueList(array $data)
 * @method SubmitHtmlElement buildSubmit(array $data)
 * @method InputHtmlElement buildInput(array $data)
 * @method PasswordHtmlElement buildPassword(array $data)
 * @method TextareaHtmlElement buildTextarea(array $data)
 * @method TextEditorHtmlElement buildTextEditor(array $data)
 * @method CheckboxHtmlElement buildCheckbox(array $data)
 * @method CheckboxGroupHtmlElement buildCheckboxGroup(array $data)
 * @method FileHtmlElement buildFile(array $data)
 * @method RadioHtmlElement buildRadio(array $data)
 * @method ButtonHtmlElement buildButton(array $data)
 * @method RatingHtmlElement buildRating(array $data)
 * @method CaptchaHtmlElement buildCaptcha(array $data)
 * @method ReCaptchaHtmlElement buildReCaptcha(array $data)
 * @method PasswordSetHtmlElement buildPasswordSet(array $data)
 * @method ResourceHtmlElement buildResource(array $data)
 * @method ResourceImageHtmlElement buildResourceImage(array $data)
 * @method DateHtmlElement buildDate(array $data)
 * @method EmailHtmlElement buildEmail(array $data)
 * @method NumberHtmlElement buildNumber(array $data)
 * @method PhoneHtmlElement buildPhone(array $data)
 * @method IPaddressHtmlElement buildIPaddress(array $data)
 * @method CountriesHtmlElement buildCountries(array $data)
 * @method ZonesHtmlElement buildZones(array $data)
 * @method PaginationHtmlElement buildPagination(array $data)
 * @method ModalHtmlElement buildModal(array $data)
 * @method LabelHtmlElement buildLabel(array $data)
 *
 * @property ModelToolSeoUrl $model_tool_seo_url
 * @property ALanguage | ALanguageManager $language
 */
class AHtml
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var array
     */
    protected $args = [];
    /**
     * @var ARequest
     */
    protected $request;

    /**
     * @param Registry $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->request = $this->registry->get('request');
    }

    /**
     * Magic method for html-element classes calls
     *
     * @param $function_name
     * @param $args
     *
     * @return null|string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function __call($function_name, $args)
    {
        $class_name = __NAMESPACE__ . "\\" . ltrim($function_name, 'build') . "HtmlElement";
        if (class_exists($class_name)) {
            /** @var SelectboxHtmlElement|HiddenHtmlElement $item */
            $item = new $class_name($args[0]);
            if (method_exists($item, 'getHtml')) {
                return $item->getHtml();
            } else {
                unset($item);
            }
        }

        return null;
    }

    /**
     * PR Build sub URL
     *
     * @param string $rt
     * @param string $params
     * @param string $mode - can be 'storefront' or empty
     *
     * @return string
     */
    private function buildURL($rt, $params = '', $mode = '')
    {
        $subUrl = '';
        //#PR Add admin path if we are in admin
        if (ABC::env('IS_ADMIN') && $mode != 'storefront') {
            $subUrl .= '&s=' . ABC::env('ADMIN_SECRET');
        }
        //add template if present
        if (!empty($this->request->get['sf'])) {
            $subUrl .= '&sf=' . $this->request->get['sf'];
        }

        //if in embed mode add response prefix
        if (Registry::config()?->get('embed_mode')) {
            $subUrl .= '&embed_mode=1';
            if (!str_starts_with($rt, 'r/')) {
                $rt = 'r/' . $rt;
            }
        }
        return '?' . ($rt ? 'rt=' . $rt : '') . $params . $subUrl;
    }

    /**
     * Get non-secure home URL.
     *
     * @return string
     *
     * Note: Non-secure URL is base on store_url setting. If this setting is using https URL, all URLs will be secure
     * @throws AException|ReflectionException|InvalidArgumentException
     */
    public function getHomeURL()
    {
        //for embed mode get home link with getURL
        if (Registry::config()->get('embed_mode')) {
            return $this->getURL('index/home');
        } else {
            //get config_url first
            $home_url = $this->registry->get('config')->get('config_url');
            if (!$home_url) {
                $home_url = ABC::env('HTTP_SERVER')
                    ? ABC::env('HTTP_SERVER')
                    : 'http://' . ABC::env('REAL_HOST') . H::get_url_path($_SERVER['PHP_SELF']);
            }

            return $home_url;
        }
    }

    /**
     * Get non-secure URL. Read note below.
     *
     * @param string $rt
     * @param string $params
     * @param string $encode
     *
     * @return string
     *
     * Note: non-secure URL is base on store_url setting. If this setting is using https URL, all URLs will be secure
     */
    public function getNonSecureURL($rt, $params = '', $encode = '')
    {
        return $this->getURL($rt, $params, $encode, true);
    }

    /**
     * Get URL with auto-detection for protocol
     *
     * @param string $rt
     * @param string $params
     * @param string $encode
     * @param bool $non_secure - force to be non-secure
     *
     * @return string
     */
    public function getURL($rt, $params = '', $encode = '', $non_secure = false)
    {
        //detect if request is using HTTPS
        if ($non_secure === false && ABC::env('HTTPS')) {
            $server = ABC::env('HTTPS_SERVER')
                ? ABC::env('HTTPS_SERVER')
                : 'https://' . ABC::env('REAL_HOST') . H::get_url_path($_SERVER['PHP_SELF']);
        } else {
            //to prevent garbage session need to check constant ABC::env('HTTP_SERVER')
            $server = ABC::env('HTTP_SERVER')
                ? ABC::env('HTTP_SERVER')
                : 'http://' . ABC::env('REAL_HOST') . H::get_url_path($_SERVER['PHP_SELF']);
        }

        if (Registry::config()?->get('storefront_template_debug')
            && isset($this->request->get['tmpl_debug'])
        ) {
            $params .= '&tmpl_debug=' . $this->request->get['tmpl_debug'];
        }
        // add session id for cross-domain transition in secure mode
        if (Registry::config()->get('config_shared_session') && ABC::env('HTTPS')) {
            $params .= '&session_id=' . session_id();
        }

        //add token for embed mode with forbidden 3d-party cookies
        if (Registry::session()->data['session_mode'] == 'embed_token') {
            $params .= '&' . ABC::env('EMBED_TOKEN_NAME') . '=' . session_id();
        }
        return $server . ABC::env('INDEX_FILE') . $this->url_encode($this->buildURL($rt, $params), $encode);
    }

    /**
     * Build secure URL with session token
     *
     * @param string $rt
     * @param string $params
     * @param string $encode
     *
     * @param string $mode - can be empty for auto-mode or 'storefront'
     *
     * @return string
     * @throws AException
     * @throws ReflectionException|InvalidArgumentException
     */
    public function getSecureURL($rt, $params = '', $encode = '', $mode = '')
    {
        $session = $this->registry->get('session');
        $config = $this->registry->get('config');
        // add session id for cross-domain transition in non-secure mode
        if ($config->get('config_shared_session') && !ABC::env('HTTPS')) {
            $params .= '&session_id=' . session_id();
        }

        $subUrl = $this->buildURL($rt, $params, $mode);

        if ($mode != 'storefront' && (ABC::env('IS_ADMIN') || ABC::env('IS_API'))) {
            //Add session token for admin and API
            if (isset($session->data['token']) && $session->data['token']) {
                $subUrl .= '&token=' . $this->registry->get('session')->data['token'];
            }
        }

        //add token for embed mode with forbidden 3d-party cookies
        if ($session->data['session_mode'] == 'embed_token') {
            $subUrl .= '&' . ABC::env('EMBED_TOKEN_NAME') . '=' . session_id();
        }

        if ($config->get('storefront_template_debug') && isset($this->request->get['tmpl_debug'])) {
            $subUrl .= '&tmpl_debug=' . $this->request->get['tmpl_debug'];
        }

        return ABC::env('HTTPS_SERVER') . ABC::env('INDEX_FILE') . $this->url_encode($subUrl, $encode);
    }

    /**
     * Build non-secure SEO URL
     *
     * @param string $rt
     * @param string $params
     * @param string $encode
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws AException
     */
    public function getSEOURL($rt, $params = '', $encode = '')
    {
        //skip SEO for embed mode
        if (Registry::config()?->get('embed_mode')) {
            return $this->getCatalogURL($rt, $params);
        }
        /**
         * @var ModelToolSeoUrl $model
         */
        $model = $this->registry->get('load')->model('tool/seo_url', 'storefront');
        //#PR Generate SEO URL based on standard URL
        //NOTE: SEO URL is non-secure url
        //and can be generated only for storefront-side
        return $this->url_encode($model->rewrite($this->getCatalogURL($rt, $params)), $encode);
    }

    /**
     * Build secure SEO URL
     *
     * @param string $rt
     * @param string $params
     * @param string $encode
     *
     * @return string
     * @throws AException|ReflectionException|InvalidArgumentException
     */
    public function getSecureSEOURL($rt, $params = '', $encode = '')
    {
        //add token for embed mode with forbidden 3d-party cookies
        if (Registry::session()->data['session_mode'] == 'embed_token') {
            $params .= '&' . ABC::env('EMBED_TOKEN_NAME') . '=' . session_id();
        }
        //#PR Generate SEO URL based on standard URL
        /** @var ModelToolSeoUrl $model */
        $model = Registry::load()?->model('tool/seo_url', 'storefront');

        return $this->url_encode($model->rewrite($this->getSecureURL($rt, $params)), $encode);
    }

    /**
     * This builds URL to the catalog to be used in admin
     *
     * @param string $rt
     * @param string $params
     * @param string $encode
     * @param bool $ssl
     *
     * @return string
     */
    public function getCatalogURL($rt, $params = '', $encode = '', $ssl = false)
    {
        //add token for embed mode with forbidden 3d-party cookies
        if (Registry::session()?->data['session_mode'] == 'embed_token') {
            $params .= '&' . ABC::env('EMBED_TOKEN_NAME') . '=' . session_id();
        }
        $subUrl = '?' . ($rt ? 'rt=' . $rt : '') . $params;

        if (Registry::config()?->get('config_ssl') == 2) {
            $ssl = true;
        }
        $http = $ssl ? ABC::env('HTTPS_SERVER') : ABC::env('HTTP_SERVER');
        return $http . ABC::env('INDEX_FILE') . $this->url_encode($subUrl, $encode);
    }

    /**
     * encode URL for & to be &amp
     *
     * @param string $url
     * @param bool $encode
     *
     * @return string
     */
    public function url_encode($url, $encode = false)
    {
        if ($encode) {
            return str_replace('&', '&amp;', $url);
        } else {
            return $url;
        }
    }

    /**
     * Current URL built based on get params with ability to exclude params
     *
     * @param $filter_params array - array of vars to filter
     *
     * @return string - url without unwanted filter parameters
     */
    public function currentURL($filter_params = [])
    {
        $params_arr = $this->request->get;
        //detect if there is RT in the params.
        $rt = 'index/home';
        if (H::has_value($params_arr['rt'])) {
            $rt = $params_arr['rt'];
            $filter_params[] = 'rt';
        }
        if (H::has_value($params_arr['s'])) {
            $filter_params[] = 's';
        }
        $URI = '&' . $this->buildURI($params_arr, $filter_params);

        return $this->getURL($rt, $URI);
    }

    /**
     * URI encrypt parameters in URI
     *
     * @param $uri
     *
     * @return string - url without unwanted filter parameters
     * @internal param array $filter_params - array of vars to filter
     */
    public function encryptURI($uri)
    {
        $encrypted = base64_encode($uri);
        if (strlen($encrypted) <= 250) {
            return '__e=' . $encrypted;
        } else {
            return $uri;
        }
    }

    /**
     * Build URI from array provided
     *
     * @param $params_arr    array - data array to process
     * @param $filter_params array - array of vars to filter
     *
     * @return string - url without unwanted filter parameters
     */
    public function buildURI($params_arr, $filter_params = [])
    {

        foreach ($filter_params as $rv) {
            unset($params_arr[$rv]);
        }

        return urldecode(http_build_query($params_arr, '', '&'));
    }

    /**
     * Filter query parameters from url.
     *
     * @param $url           string - url to process
     * @param $filter_params string|array - single var or array of vars
     *
     * @return string - url without unwanted filter query parameters
     */
    public function filterQueryParams($url, $filter_params = [])
    {
        list($url_part, $q_part) = explode('?', $url);
        parse_str($q_part, $q_vars);
        //build array if passed as string
        if (!is_array($filter_params)) {
            $filter_params = [$filter_params];
        }
        foreach ($filter_params as $rv) {
            unset($q_vars[$rv]);
        }
        foreach ($q_vars as $key => $val) {
            $q_vars[$key] = $this->request->clean($val);
        }

        $new_qs = urldecode(http_build_query($q_vars, '', '&'));

        return $url_part . '?' . $new_qs;
    }

    /**
     * remove get parameters from url.
     *
     * @param $url - url to process
     * @param $remove_vars
     *
     * @return string - url without unwanted get parameters
     * @internal   param array|string $vars - single var or array of vars
     * @deprecated since 1.1.4! Use filterQueryParams() instead
     *
     */
    public function removeQueryVar($url, $remove_vars)
    {
        return $this->filterQueryParams($url, $remove_vars);
    }

    /**
     * function returns text error or empty
     *
     * @param string $query
     * @param string $keyword
     *
     * @return string
     * @throws Exception|InvalidArgumentException
     */
    public function isSEOKeywordExists($query, $keyword = '')
    {
        if (!$keyword) {
            return '';
        }
        $seo_key = H::SEOEncode($keyword);

        $db = Registry::db();

        $sql = "SELECT *
                FROM " . $db->table_name('url_aliases') . "
                WHERE query<>'" . $db->escape($query) . "' AND keyword='" . $db->escape($seo_key) . "'";
        $result = $db->query($sql);
        if ($result->num_rows) {
            $url = ABC::env('HTTP_CATALOG') . $seo_key;

            return sprintf(Registry::language()->get('error_seo_keyword'), $url, $seo_key);
        }

        return '';
    }

    /**
     * create html code based on passed data
     *
     * @param  $data - array with element data
     *               sample
     *               $data = array(
     *               'type' => 'input' //(hidden, textarea, selectbox, file...)
     *               'name' => 'input name'
     *               'value' => 'input value' // could be an array for select
     *               'style' => 'my-form'
     *               'form' => 'form id' // needed for unique element ID     *
     *               );
     *
     * @return object
     * @throws AException
     */
    public function buildElement($data)
    {
        return HtmlElementFactory::create($data);
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws AException|ReflectionException
     */
    public function getStoreSwitcher()
    {
        $view = new AView(Registry::getInstance(), 0);
        //check if store_id is passed or in the session
        $store_id = Registry::config()?->get('config_store_id');
        //set store selector
        $stores = [];
        $hidden = [];
        $stores[0] = ['name' => Registry::language()?->get('text_default')];
        /** @var ModelSettingStore $model */
        $model = Registry::load()?->model('setting/store');
        //if loaded not default store - hide store switcher
        $default_store_settings = $model->getStore(0);
        if (Registry::config()?->get('config_url') != $default_store_settings['config_url']) {
            return '';
        }
        $result_stores = $model->getStores();
        if (sizeof($result_stores) > 0) {
            foreach ($result_stores as $rs) {
                $stores[$rs['store_id']] = [
                    'name'     => $rs['alias'] ?: $rs['name'],
                    'store_id' => $rs['store_id']
                ];
            }
            foreach (Registry::request()?->get as $name => $value) {
                if ($name == 'store_id') {
                    continue;
                }
                $hidden[$name] = $value;
            }
            $view->assign('all_stores', $stores);
            $view->assign('current_store', $stores[$store_id]['name']);
            $view->assign('hiddens', $hidden);
            $view->assign('text_select_store', Registry::language()?->get('text_select_store'));

            return $view->fetch('form/store_switcher.tpl');
        } else {
            return '';
        }
    }

    /**
     * @return string
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function getContentLanguageSwitcher()
    {
        $view = new AView(Registry::getInstance(), 0);
        /** @var ModelLocalisationLanguage $mdl */
        $mdl = Registry::load()?->model('localisation/language');
        $results = $mdl->getLanguages();
        $template['languages'] = [];

        foreach ($results as $result) {
            if ($result['status']) {
                $template['languages'][] = [
                    'name'  => $result['name'],
                    'code'  => $result['code'],
                    'image' => $result['image']
                ];
            }
        }
        if (sizeof($template['languages']) > 1) {
            //selected in selectbox
            $template['language_code'] = Registry::language()?->getContentLanguageCode();
            foreach (Registry::request()?->get as $name => $value) {
                if ($name == 'content_language_code') {
                    continue;
                }
                $template['hiddens'][$name] = $value;
            }
        } else {
            $template['languages'] = [];
        }
        $view->batchAssign($template);

        return $view->fetch('form/language_switcher.tpl');
    }

    /**
     * @return string
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function getContentLanguageFlags()
    {
        $registry = $this->registry;
        $view = new AView($registry, 0);
        /** @var ModelLocalisationLanguage $mdl */
        $mdl = $registry->get('load')->model('localisation/language');
        $results = $mdl->getLanguages();
        $template['languages'] = [];

        foreach ($results as $result) {
            if ($result['status']) {
                $template['languages'][] = [
                    'name'  => $result['name'],
                    'code'  => $result['code'],
                    'image' => $result['image']
                ];
            }
        }
        if (sizeof($template['languages']) > 1) {
            //selected in selectbox
            $template['language_code'] = Registry::language()?->getContentLanguageCode();
            foreach ($registry->get('request')->get as $name => $value) {
                if ($name == 'content_language_code') {
                    continue;
                }
                $template['hiddens'][$name] = $value;
            }
        } else {
            $template['languages'] = [];
        }
        $view->batchAssign($template);

        return $view->fetch('form/language_flags.tpl');
    }

    /**
     * @param        $html - text that might contain internal links #admin# or #storefront#
     *                          $mode  - 'href' create complete a tag or default just replace URL
     * @param string $type - can be 'message' to convert url into <a> tag or empty
     * @param bool $for_admin - force mode for converting links
     *                              to admin side from storefront scope (see AIM-class e.t.c.)
     *
     * @return string - html code with parsed internal URLs
     * @throws AException
     * @throws ReflectionException|InvalidArgumentException
     */
    public function convertLinks($html, $type = '', $for_admin = false)
    {
        $is_admin = ABC::env('IS_ADMIN') === true || $for_admin;
        $route_sections = $is_admin ? ["admin", "storefront"] : ["storefront"];
        foreach ($route_sections as $rt_type) {
            preg_match_all(
                '/(#' . $rt_type . '#rt=){1}[a-z0-9\/_\-?&=%#]{1,255}(\b|\")/',
                $html,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            if ($matches) {
                foreach ($matches[0] as $match) {
                    $href = str_replace('?', '&', $match[0]);

                    if ($rt_type == 'admin') {
                        if ($for_admin && ABC::env('IS_ADMIN') !== true) {
                            $href .= '&s=' . ABC::env('ADMIN_SECRET');
                        }
                        $new_href = str_replace('#admin#', $this->getSecureURL('') . '&', $href);
                    } else {
                        $new_href = str_replace('#storefront#', $this->getCatalogURL('') . '&', $href);
                    }
                    $new_href = str_replace(['&amp;', '&&', '&?'], '&', $new_href);
                    $new_href = str_replace('?&', '?', $new_href);
                    $new_href = str_replace('&', '&amp;', $new_href);

                    switch ($type) {
                        case 'message':
                            $new_href = '<a href="' . $new_href . '" target="_blank">#link-text#</a>';
                            break;
                        default:
                            break;
                    }

                    $html = str_replace($match[0], $new_href, $html);
                }
            }
        }

        return $html;
    }

}

/**
 * Class HtmlElementFactory
 */
class HtmlElementFactory
{
    static private $available_elements =
        [
            'I' => [
                'type'   => 'input',
                'method' => 'buildInput',
                'class'  => 'InputHtmlElement'
            ],
            'T' => [
                'type'   => 'textarea',
                'method' => 'buildTextarea',
                'class'  => 'TextareaHtmlElement'
            ],
            'S' => [
                'type'   => 'selectbox',
                'method' => 'buildSelectbox',
                'class'  => 'SelectboxHtmlElement'
            ],
            'M' => [
                'type'   => 'multiselectbox',
                'method' => 'buildMultiselectbox',
                'class'  => 'MultiSelectboxHtmlElement'
            ],
            'R' => [
                'type'   => 'radio',
                'method' => 'buildRadio',
                'class'  => 'RadioHtmlElement'
            ],
            'C' => [
                'type'   => 'checkbox',
                'method' => 'buildCheckbox',
                'class'  => 'CheckboxHtmlElement'
            ],
            'G' => [
                'type'   => 'checkboxgroup',
                'method' => 'buildCheckboxgroup',
                'class'  => 'CheckboxgroupHtmlElement'
            ],
            'U' => [
                'type'   => 'file',
                'method' => 'buildFile',
                'class'  => 'FileHtmlElement'
            ],
            'K' => [
                'type'   => 'captcha',
                'method' => 'buildCaptcha',
                'class'  => 'CaptchaHtmlElement',
            ],
            'J' => [
                'type'   => 'recaptcha',
                'method' => 'buildReCaptcha',
                'class'  => 'ReCaptchaHtmlElement',
            ],
            'H' => [
                'type'   => 'hidden',
                'method' => 'buildHidden',
                'class'  => 'HiddenHtmlElement'
            ],
            'P' => [
                'type'   => 'multivalue',
                'method' => 'buildMultivalue',
                'class'  => 'MultivalueHtmlElement'
            ],
            'L' => [
                'type'   => 'multivaluelist',
                'method' => 'buildMultivalueList',
                'class'  => 'MultivalueListHtmlElement'
            ],
            'D' => [
                'type'   => 'date',
                'method' => 'buildDateInput',
                'class'  => 'DateInputHtmlElement'
            ],
            'E' => [
                'type'   => 'email',
                'method' => 'buildEmail',
                'class'  => 'EmailHtmlElement'
            ],
            'N' => [
                'type'   => 'number',
                'method' => 'buildNumber',
                'class'  => 'NumberHtmlElement'
            ],
            'F' => [
                'type'   => 'phone',
                'method' => 'buildPhone',
                'class'  => 'PhoneHtmlElement'
            ],
            'A' => [
                'type'   => 'IPaddress',
                'method' => 'buildIPaddress',
                'class'  => 'IPaddressHtmlElement'
            ],
            'O' => [
                'type'   => 'countries',
                'method' => 'buildCountries',
                'class'  => 'CountriesHtmlElement'
            ],
            'Z' => [
                'type'   => 'zones',
                'method' => 'buildZones',
                'class'  => 'ZonesHtmlElement'
            ],
            'B' => [
                'type'   => 'label',
                'method' => 'buildLabel',
                'class'  => 'LabelHtmlElement'
            ],

        ];

    static private $elements_with_options =
        [
            'S',
            'M',
            'R',
            'G',
            'O',
            'Z',
        ];
    static private $multivalue_elements =
        [
            'M',
            'R',
            'G',
        ];
    static private $elements_with_placeholder =
        [
            'S',
            'I',
            'M',
            'O',
            'Z',
            'F',
            'N',
            'E',
            'D',
            'U',
            'T'
        ];

    /**
     *  return array of HTML elements supported
     *  array key - code of element
     *  [
     *   type - element type
     *   method - method in html class to get element html
     *   class - element class
     *  ]
     *
     * @static
     * @return array
     */
    static function getAvailableElements()
    {
        return self::$available_elements;
    }

    /**
     * return array of elements indexes for elements which has options
     *
     * @static
     * @return array
     */
    static function getElementsWithOptions()
    {
        return self::$elements_with_options;
    }

    /**
     * return array of elements indexes for elements which has options
     *
     * @static
     * @return array
     */
    static function getElementsWithPlaceholder()
    {
        return self::$elements_with_placeholder;
    }

    /**
     * return array of elements indexes for elements which has options
     *
     * @static
     * @return array
     */
    static function getMultivalueElements()
    {
        return self::$multivalue_elements;
    }

    /**
     * return element type
     *
     * @static
     *
     * @param $code - element code ( from $available_elements )
     *
     * @return null | string
     */
    static function getElementType($code)
    {
        if (!array_key_exists($code, self::$available_elements)) {
            return null;
        }

        return self::$available_elements[$code]['type'];
    }

    /**
     * @param $data
     *
     * @return HiddenHtmlElement | MultivalueListHtmlElement | MultivalueHtmlElement | SubmitHtmlElement
     *          | InputHtmlElement | PasswordHtmlElement | PaginationHtmlElement | TextareaHtmlElement
     *          | SelectboxHtmlElement | MultiSelectboxHtmlElement | CheckboxHtmlElement | CheckboxGroupHtmlElement
     *          | FileHtmlElement | RadioHtmlElement | ButtonHtmlElement | FormHtmlElement | RatingHtmlElement
     *          | CaptchaHtmlElement | ReCaptchaHtmlElement | PasswordSetHtmlElement | ResourceHtmlElement
     *          | ResourceImageHtmlElement | DateHtmlElement | EmailHtmlElement | NumberHtmlElement
     *          | PhoneHtmlElement | IPaddressHtmlElement | CountriesHtmlElement | ZonesHtmlElement | ModalHtmlElement
     * @throws AException
     */
    static function create($data)
    {

        $class = 'abc\core\engine\\' . ucfirst($data['type'] . 'HtmlElement');
        if (!class_exists($class)) {
            throw new AException(
                'Error: Could not load HTML element ' . $data['type'] . '!',
                AC_ERR_LOAD
            );
        }

        return new $class($data);
    }
}

/**
 * @abstract
 * Class HtmlElement
 * @property mixed $value
 * @property array $options
 * @property array $disabled_options
 * @property bool $required
 */
abstract class HtmlElement
{
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var AView
     */
    protected $view;
    /**
     * @var string
     */
    public $element_id;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var ALanguage $language
     */
    protected $language;

    /**
     * @param array $data
     *
     * @throws AException
     */
    function __construct($data)
    {
        if (!isset($data['value'])) {
            $data['value'] = '';
        }
        if (isset($data['required']) && $data['required'] == 1) {
            $data['required'] = 'Y';
        }
        if (isset($data['attr'])) {
            $data['attr'] = ' ' . htmlspecialchars_decode($data['attr']) . ' ';
        }

        $this->registry = Registry::getInstance();

        $this->language = Registry::language();
        $this->view = new AView(Registry::getInstance(), 0);
        $this->data = $data;
        $this->element_id = H::preformatTextID($data['name']);
        if ($data['form']) {
            $this->element_id = $data['form'] . '_' . $this->element_id;
        }
    }

    /**
     * @param string $name
     *
     * @return null|string
     */
    public function __get($name)
    {

        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $javascript = '';
        if ($this->data['javascript']) {
            $javascript = $this->data['javascript'];
        }

        return $javascript . $this->getHtml();
    }

    /**
     * @return null
     */
    public function getHtml()
    {
        return null;
    }

    protected function validateOptions()
    {
        $this->disabled_options = (array)$this->disabled_options;
        //check case when all options are disabled
        $all_disabled = true;
        if ($this->options) {
            foreach ($this->options as $id => $text) {
                if (!in_array($id, $this->disabled_options)) {
                    $all_disabled = false;
                    break;
                }
            }
        }
        //if all disabled and options presents (for select-chosen element or empty)
        if ($all_disabled && $this->options) {
            if (in_array($this->data['type'], ['selectbox', 'multiselectbox'])) {
                $this->options = ['' => '------'] + $this->options;
            }
            $this->value = 0;
            if ($this->required) {
                $url = ABC::env('HTTPS_SERVER');
                $query_string = $this->registry->get('request')->server['QUERY_STRING'];
                if (!str_contains($query_string, '_route_=')) {
                    $url .= '?';
                } else {
                    $query_string = str_replace('_route_=', '', $query_string);
                }
                $url .= $query_string;
                $this->registry->get('messages')->saveWarning(
                    'Form Field #' . $this->element_id . ' Issue',
                    'Abnormal situation. All options of required field "' . $this->data['name'] . '" are disabled.'
                    . ' URL: <a href="' . $url . '">' . $url . "</a>"
                );
            }
        }
    }

}

/**
 * Class HiddenHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property string $attr
 */
class HiddenHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'id'    => $this->element_id,
                'name'  => $this->name,
                'value' => $this->value,
                'attr'  => $this->attr,
            ]
        );

        return $this->view->fetch('form/hidden.tpl');
    }
}

/**
 * Class MultivalueListHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property array $values
 * @property array $postvars
 * @property string $content_url
 * @property string $edit_url
 * @property string $form
 * @property string $multivalue_hidden_id
 * @property string $return_to
 * @property string $with_sorting
 * @property array $text
 */
class MultivalueListHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getHtml()
    {
        $data = [
            'id'                   => $this->element_id,
            'name'                 => $this->name,
            'values'               => $this->values,
            'content_url'          => $this->content_url,
            'edit_url'             => $this->edit_url,
            'postvars'             => $this->postvars,
            'form_name'            => $this->form,
            'multivalue_hidden_id' => $this->multivalue_hidden_id,
            'return_to'            => ($this->return_to
                ?: $this->form . '_' . $this->multivalue_hidden_id . '_item_count'),
            'with_sorting'         => $this->with_sorting
        ];

        $data['text']['delete'] = $this->text['delete'] ?: 'delete';
        $data['text']['delete_confirm'] = $this->text['delete_confirm'] ?: 'Confirm to delete?';
        $data['text']['column_action'] = Registry::language()?->get('column_action');
        $data['text']['column_sort_order'] = Registry::language()?->get('text_sort_order');
        $this->view->batchAssign($data);

        return $this->view->fetch('form/multivalue_list.tpl');
    }
}

/**
 * Class MultivalueHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $selected_name
 * @property string $title
 * @property string $selected
 * @property string $content_url
 * @property string $form
 * @property string $return_to
 * @property string $no_save
 * @property array $text
 * @property int $popup_height
 * @property int $popup_width
 * @property array $js
 * @property array $postvars
 */
class MultivalueHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {
        $data = [
            'id'            => $this->element_id,
            'name'          => $this->name,
            'selected_name' => ($this->selected_name ?: 'selected[]'),
            'title'         => $this->title,
            'selected'      => $this->selected,
            'content_url'   => $this->content_url,
            'postvars'      => ($this->postvars ? json_encode($this->postvars) : ''),
            'form_name'     => $this->form,
            'return_to'     => ($this->return_to ?: $this->element_id . '_item_count'),
            'no_save'       => (isset($this->no_save) ? (bool)$this->no_save : false),
            'popup_height'  => ((int)$this->popup_height ?: 620),
            'popup_width'   => ((int)$this->popup_width ?: 800),
            // custom triggers for dialog events (custom functions calls)
            'js'            => [
                'apply'  => $this->js['apply'],
                'cancel' => $this->js['cancel'],
            ]
        ];

        $data['text_selected'] = $this->text['selected'];
        $data['text_edit'] = $this->text['edit'] ?: 'Add / Edit';
        $data['text_apply'] = $this->text['apply'] ?: 'apply';
        $data['text_save'] = $this->text['save'] ?: 'save';
        $data['text_reset'] = $this->text['reset'] ?: 'reset';

        $this->view->batchAssign($data);

        return $this->view->fetch('form/multivalue_hidden.tpl');
    }
}

/**
 * Class SubmitHtmlElement
 *
 * @property string $element_id
 * @property string $form,
 * @property string $name
 * @property string $value
 * @property string $attr
 * @property string $style
 * @property string $icon
 */
class SubmitHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'id'    => $this->element_id,
                'form'  => $this->form,
                'name'  => $this->name,
                'value' => $this->value,
                'attr'  => $this->attr,
                'style' => $this->style,
                'icon'  => $this->icon,
            ]
        );

        return $this->view->fetch('form/submit.tpl');
    }
}

/**
 * Class InputHtmlElement
 *
 * @property string $element_id
 * @property string $default
 * @property string $value
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $placeholder
 * @property string $regexp_pattern
 * @property string $error_text
 * @property string $help_url
 * @property bool $multilingual
 */
class InputHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {

        if (!isset($this->default)) {
            $this->default = '';
        }
        if ($this->value == '' && !empty($this->default)) {
            $this->value = $this->default;
        }

        $this->view->batchAssign(
            [
                'name'           => $this->name,
                'id'             => $this->element_id,
                'type'           => 'text',
                'value'          => str_replace('"', '&quot;', $this->value),
                'default'        => $this->default,
                'attr'           => $this->attr,
                'required'       => $this->required,
                'style'          => $this->style,
                'placeholder'    => $this->placeholder,
                'regexp_pattern' => trim($this->regexp_pattern, '/'),
                'error_text'     => $this->error_text,
            ]
        );
        if (count(Registry::language()?->getActiveLanguages()) > 1) {
            $this->view->assign('multilingual', $this->multilingual);
        }
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/input.tpl');
    }
}

/**
 * Class PasswordHtmlElement
 *
 * @property string $element_id
 * @property string $default
 * @property string $value
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $placeholder
 * @property string $regexp_pattern
 * @property string $error_text
 */
class PasswordHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {

        if (!isset($this->default)) {
            $this->default = '';
        }
        if ($this->value == '' && !empty($this->default)) {
            $this->value = $this->default;
        }
        if (!$this->required && $this->value) {
            $value = str_repeat('*', 10);
        } else {
            $value = '';
        }

        $this->view->batchAssign(
            [
                'name'           => $this->name,
                'id'             => $this->element_id,
                'type'           => 'password',
                'value'          => $value,
                'has_value'      => ($this->value) ? 'Y' : 'N',
                'attr'           => $this->attr,
                'required'       => $this->required,
                'style'          => $this->style,
                'placeholder'    => $this->placeholder,
                'regexp_pattern' => trim($this->regexp_pattern, '/'),
                'error_text'     => $this->error_text,
            ]
        );

        return $this->view->fetch('form/input.tpl');
    }
}

/**
 * Class TextareaHtmlElement
 *
 * @property string $element_id
 * @property string $value
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $placeholder
 * @property string $label_text
 * @property bool $multilingual
 * @property string $help_url
 */
class TextareaHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'name'        => $this->name,
                'id'          => $this->element_id,
                'value'       => $this->value,
                'ovalue'      => htmlentities($this->value, ENT_QUOTES, ABC::env('APP_CHARSET')),
                'attr'        => $this->attr,
                'required'    => $this->required,
                'style'       => $this->style,
                'placeholder' => $this->placeholder,
                'label_text'  => $this->label_text
            ]
        );
        if (count(Registry::language()?->getActiveLanguages()) > 1) {
            $this->view->assign('multilingual', $this->multilingual);
        }
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/textarea.tpl');
    }
}

/**
 * Class TextEditorHtmlElement
 *
 * @property string $element_id
 * @property string $value
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $placeholder
 * @property string $base_url - need for inserting pictures into html for emails
 * @property bool $multilingual
 */
class TextEditorHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'name'        => $this->name,
                'id'          => $this->element_id,
                'value'       => $this->value,
                'ovalue'      => htmlentities($this->value, ENT_QUOTES, ABC::env('APP_CHARSET')),
                'attr'        => $this->attr,
                'required'    => $this->required,
                'style'       => $this->style,
                'placeholder' => $this->placeholder,
                'base_url'    => $this->base_url
            ]
        );
        if (is_object($this->language)) {
            if (count($this->language->getActiveLanguages()) > 1) {
                $this->view->assign('multilingual', $this->multilingual);
            }
            $text = [];
            $text['language_code'] = $this->language->getContentLanguageCode();
            $text['tab_text'] = $this->language->get('tab_text');
            $text['tab_visual'] = $this->language->get('tab_visual');
            $text['button_add_media'] = $this->language->get('button_add_media');

            $this->view->batchAssign($text);
        }

        return $this->view->fetch('form/text_editor.tpl');
    }
}

/**
 * Class SelectboxHtmlElement
 *
 * @property string $element_id
 * @property string $value
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $placeholder
 * @property array $options
 * @property array $disabled_options
 * @property bool $disabled
 * @property string $ajax_url
 * @property string $search_mode
 * @property string $help_url
 * @property array $extra
 *
 */
class SelectboxHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getHtml()
    {
        if ($this->value === null) {
            $this->value = [];
        }

        if (!is_array($this->value)) {
            $this->value = [(string)$this->value => (string)$this->value];
        }

        $this->options = !$this->options ? [] : (array)$this->options;
        foreach ($this->options as &$opt) {
            $opt = (string)$opt;
        }
        unset($opt);
        $this->disabled_options = (array)$this->disabled_options;
        $disabled = [];
        foreach ((array)$this->disabled_options as $id) {
            $disabled[] = (string)$id;
        }
        $this->disabled_options = $disabled;

        $text_continue_typing = $text_looking_for = '';
        if (is_object($this->language)) {
            $text_continue_typing = $this->language->get('text_continue_typing', '', true);
            $text_looking_for = $this->language->get('text_looking_for', '', true);
        }

        $text_continue_typing = !$text_continue_typing || $text_continue_typing == 'text_continue_typing' ? 'Continue typing ...' : $text_continue_typing;
        $text_looking_for = !$text_looking_for || $text_looking_for == 'text_looking_for' ? 'Looking for' : $text_looking_for;

        $this->validateOptions();

        $this->view->batchAssign(
            [
                'name'                 => $this->name,
                'id'                   => $this->element_id,
                'value'                => $this->value,
                'options'              => $this->options,
                'disabled'             => $this->disabled,
                'disabled_options'     => $this->disabled_options,
                'attr'                 => $this->attr,
                'required'             => $this->required,
                'style'                => $this->style,
                'placeholder'          => $this->placeholder,
                'ajax_url'             => $this->ajax_url, //if mode of data load is ajax based
                'search_mode'          => $this->search_mode,
                'text_continue_typing' => $text_continue_typing,
                'text_looking_for'     => $text_looking_for,
                'extra'                => $this->extra,
            ]
        );

        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }
        if (str_contains($this->style, 'chosen')) {
            $this->view->batchAssign(
                [
                    'ajax_url'             => $this->ajax_url, //if mode of data load is ajax based
                    'text_continue_typing' => $text_continue_typing,
                    'text_looking_for'     => $text_looking_for,
                ]
            );
            $return = $this->view->fetch('form/chosen_select.tpl');
        } else {
            $return = $this->view->fetch('form/selectbox.tpl');
        }

        return $return;
    }
}

/**
 * Class MultiSelectboxHtmlElement
 *
 * @property string $element_id
 * @property array|mixed $value
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $placeholder
 * @property array $options
 * @property array $disabled_options
 * @property string $filter_params - some additional parameters
 * @property string $ajax_url
 * @property string|array $option_attr
 * @property string $help_url
 * @property bool $disabled
 * @property array $extra
 */
class MultiSelectboxHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getHtml()
    {

        if (!is_array($this->value)) {
            $this->value = [$this->value => $this->value];
        }
        $this->validateOptions();
        $this->view->batchAssign(
            [
                'name'             => $this->name,
                'id'               => $this->element_id,
                'value'            => $this->value,
                'options'          => $this->options,
                'disabled_options' => $this->disabled_options,
                'disabled'         => $this->disabled,
                'attr'             => $this->attr . ' multiple="multiple" ',
                'required'         => $this->required,
                'style'            => $this->style,
                'placeholder'      => $this->placeholder,
                'filter_params'    => $this->filter_params,
                'extra'            => $this->extra,
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        if (str_contains($this->style, 'chosen')) {

            $option_attr = $this->option_attr && !is_array($this->option_attr)
                ? [$this->option_attr]
                : $this->option_attr;
            $option_attr = $option_attr ?: [];
            $this->view->batchAssign(
                [
                    'ajax_url'             => $this->ajax_url,
                    //if mode of data load is ajax based
                    'option_attr'          => $option_attr,
                    //list of custom html5 attributes for options of selectbox
                    'text_continue_typing' => Registry::language()?->get('text_continue_typing', '', true),
                    'text_looking_for'     => Registry::language()?->get('text_looking_for', '', true),
                ]
            );
            $return = $this->view->fetch('form/chosen_select.tpl');
        } else {
            $return = $this->view->fetch('form/selectbox.tpl');
        }

        return $return;
    }
}

/**
 * Class CheckboxHtmlElement
 *
 * @property string|int $value
 * @property bool $checked
 * @property string $element_id
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $label_text
 * @property string $help_url
 */
class CheckboxHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getHtml()
    {
        if (str_contains($this->style, 'btn_switch')) { //for switch button NOTE: value is binary (1 or 0)!!!
            $checked = is_null($this->checked) && $this->value ? true : (bool)$this->checked;
            $this->value = $checked ? 1 : 0;
            $tpl = 'form/switch.tpl';
        } else {//for generic checkbox NOTE: in this case value must be any and goes to tpl as-is
            $checked = !is_null($this->checked) ? $this->checked : false;
            $tpl = 'form/checkbox.tpl';
        }

        $text_on = $text_off = '';
        if (is_object($this->language)) {
            $text_on = $this->language->get('text_on', '', true);
            $text_off = $this->language->get('text_off', '', true);
        }

        $text_on = !$text_on || $text_on == 'text_on' ? 'ON' : $text_on;
        $text_off = !$text_off || $text_off == 'text_off' ? 'OFF' : $text_off;

        $this->view->batchAssign(
            [
                'name'       => $this->name,
                'id'         => $this->element_id,
                'value'      => $this->value,
                'attr'       => $this->attr,
                'required'   => $this->required,
                'label_text' => $this->label_text,
                'checked'    => $checked,
                'style'      => $this->style,
                'text_on'    => $text_on,
                'text_off'   => $text_off,
            ]);
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch($tpl);
    }
}

/**
 * Class CheckboxGroupHtmlElement
 *
 * @property string|int $value
 * @property array $options
 * @property array $disabled_options
 * @property string $element_id
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property bool $scrollbox
 * @property string $help_url
 * @property string $placeholder
 */
class CheckboxGroupHtmlElement extends HtmlElement
{

    public function getHtml()
    {
        $this->value = $this->value === null || $this->value === '' ? [] : $this->value;
        $this->value = !is_array($this->value) ? [$this->value => $this->value] : $this->value;
        $this->validateOptions();
        if (isset($this->options) && is_array($this->options)) {
            $option_keys = array_keys($this->options);
            foreach ($this->value as $value) {
                if (!in_array($value, $option_keys)) {
                    $this->options += [$value => 'unknown'];
                }
            }
        }

        $this->view->batchAssign(
            [
                'name'             => $this->name,
                'id'               => $this->element_id,
                'value'            => $this->value,
                'options'          => $this->options,
                'disabled_options' => $this->disabled_options,
                'attr'             => $this->attr . ' multiple="multiple" ',
                'required'         => $this->required,
                'scrollbox'        => $this->scrollbox,
                'style'            => $this->style,
                'placeholder'      => $this->placeholder,
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        if (str_contains($this->style, 'chosen')) {
            $return = $this->view->fetch('form/chosen_select.tpl');
        } else {
            $return = $this->view->fetch('form/checkboxgroup.tpl');
        }

        return $return;
    }
}

/**
 * Class FileHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property string $help_url
 * @property string $placeholder
 */
class FileHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getHtml()
    {

        $this->view->batchAssign(
            [
                'name'         => $this->name,
                'id'           => $this->element_id,
                'attr'         => $this->attr,
                'required'     => $this->required,
                'style'        => $this->style,
                'default_text' => $this->language->get('text_click_browse_file'),
                'text_browse'  => $this->language->get('text_browse'),
                'placeholder'  => $this->placeholder,
            ]
        );

        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/file.tpl');
    }
}

/**
 * Class RadioHtmlElement
 *
 * @property string $value
 * @property string $element_id
 * @property string $name
 * @property string $attr
 * @property string $required
 * @property string $style
 * @property array $options
 * @property array $disabled_options
 * @property array $disabled
 * @property string $help_url
 */
class RadioHtmlElement extends HtmlElement
{

    public function getHtml()
    {
        //if no option provided, default to value
        if (empty($this->options) && H::has_value($this->value)) {
            $this->options = [$this->value => $this->value];
        }
        $this->validateOptions();
        $this->view->batchAssign(
            [
                'name'             => $this->name,
                'id'               => $this->element_id,
                'value'            => $this->value,
                'options'          => $this->options,
                'disabled_options' => $this->disabled_options,
                'disabled'         => $this->disabled,
                'attr'             => $this->attr,
                'required'         => $this->required,
                'style'            => $this->style,
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/radio.tpl');
    }
}

/**
 * Class ButtonHtmlElement
 *
 * @property string $element_id
 * @property string $text
 * @property string $title
 * @property string $attr
 * @property string $href
 * @property string $style
 * @property string $href_class
 * @property string $icon
 * @property string $target
 */
class ButtonHtmlElement extends HtmlElement
{

    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'text'       => $this->text,
                'title'      => $this->title,
                'id'         => $this->element_id,
                'attr'       => $this->attr,
                'style'      => $this->style,
                'href'       => $this->href,
                'href_class' => $this->href_class,
                'icon'       => $this->icon,
                'target'     => $this->target
            ]
        );

        return $this->view->fetch('form/button.tpl');
    }
}

/**
 * Class FormHtmlElement
 *
 * @property string $name
 * @property string $action
 * @property string $attr
 * @property string $method
 * @property string $style
 * @property string $enctype
 * @property bool $csrf
 */
class FormHtmlElement extends HtmlElement
{

    public function getHtml()
    {
        $this->method = empty($this->method) ? 'post' : $this->method;
        $this->enctype = empty($this->enctype) ? 'multipart/form-data' : $this->enctype;
        $this->view->batchAssign(
            [
                'id'      => $this->name,
                'name'    => $this->name,
                'action'  => $this->action,
                'method'  => $this->method,
                'attr'    => $this->attr,
                'style'   => $this->style,
                'enctype' => $this->enctype
            ]
        );
        //add CSRF token
        if ($this->csrf === true) {
            $csrftoken = $this->registry->get('csrftoken');
            $this->view->batchAssign(
                [
                    'csrfinstance' => $csrftoken->setInstance(),
                    'csrftoken'    => $csrftoken->setToken()
                ]
            );
        }

        return $this->view->fetch('form/form_open.tpl') . $this->view->fetch('form/form_csrf.tpl');
    }
}

/**
 * Class RatingHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property array $options
 * @property bool $required
 */
class RatingHtmlElement extends HtmlElement
{

    function __construct($data)
    {
        parent::__construct($data);
        if (!$this->registry->has('star-rating')) {
            /** @var $doc ADocument */
            $doc = $this->registry->get('document');
            $doc->addScript($this->view->templateResource('assets/js/jquery/star-rating/jquery.MetaData.js'));
            $doc->addScript($this->view->templateResource('assets/js/jquery/star-rating/jquery.rating.pack.js'));

            $doc->addStyle([
                'href'  => $this->view->templateResource('assets/js/jquery/star-rating/jquery.rating.css'),
                'rel'   => 'stylesheet',
                'media' => 'screen',
            ]);

            $this->registry->set('star-rating', 1);
        }
    }

    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'name'     => $this->name,
                'id'       => $this->element_id,
                'value'    => $this->value,
                'options'  => $this->options,
                'style'    => 'star',
                'required' => $this->required,
            ]
        );

        return $this->view->fetch('form/rating.tpl');
    }
}

/**
 * Class CaptchaHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property string $placeholder
 * @property Registry $registry
 */
class CaptchaHtmlElement extends HtmlElement
{

    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'name'        => $this->name,
                'id'          => $this->element_id,
                //TODO: remove deprecated attribute aform_field_type
                'attr'        => 'aform_field_type="captcha" ' . $this->attr . ' data-aform-field-type="captcha"',
                'style'       => $this->style,
                'required'    => $this->required,
                'captcha_url' => $this->registry->get('html')->getURL('common/captcha'),
                'placeholder' => $this->placeholder
            ]
        );

        return $this->view->fetch('form/captcha.tpl');
    }
}

/**
 * Class ReCaptchaHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $attr
 * @property string $language_code
 * @property string $recaptcha_site_key
 */
class ReCaptchaHtmlElement extends HtmlElement
{
    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'name'               => $this->name,
                'id'                 => $this->element_id,
                'attr'               => $this->attr . ' data-aform-field-type="captcha"',
                'language_code'      => $this->language_code,
                'recaptcha_site_key' => $this->recaptcha_site_key
            ]
        );

        return $this->view->fetch('form/recaptcha.tpl');
    }
}

/**
 * Class PasswordsetHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property string $placeholder
 */
class PasswordsetHtmlElement extends HtmlElement
{

    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'name'                  => $this->name,
                'id'                    => $this->element_id,
                'value'                 => $this->value,
                'attr'                  => $this->attr,
                'style'                 => $this->style,
                'required'              => $this->required,
                'text_confirm_password' => $this->language->get('text_confirm_password'),
                'placeholder'           => $this->placeholder,
            ]
        );

        return $this->view->fetch('form/passwordset.tpl');
    }
}

/**
 * Class ResourceHtmlElement
 *
 * @property string $rl_type - image or audio or pdf etc
 * @property string $element_id
 * @property string $name
 * @property string $resource_path
 * @property int $resource_id
 * @property string $object_name
 * @property string $object_id
 * @property bool $hide    - sign to hide image preview
 * @property string $placeholder
 */
class ResourceHtmlElement extends HtmlElement
{

    function __construct($data)
    {
        parent::__construct($data);
    }

    public function getHtml()
    {
        if (empty($this->rl_type)) {
            throw new AException(
                'Error: Could not load HTML element of resource library. Resource type not given!',
                AC_ERR_LOAD
            );
        }
        $data = [
            'id'            => $this->element_id,
            'wrapper_id'    => $this->element_id . '_wrapper',
            'name'          => $this->name,
            'resource_path' => $this->resource_path,
            'resource_id'   => $this->resource_id,
            'object_name'   => $this->object_name,
            'object_id'     => $this->object_id,
            'rl_type'       => $this->rl_type,
            'hide'          => (bool)$this->hide
        ];
        if (!$data['resource_id'] && $data['resource_path']) {
            $path = ltrim($data['resource_path'], $data['rl_type'] . '/');
            $r = new AResource($data['rl_type']);
            $data['resource_id'] = $r->getIdFromHexPath($path);
        }
        if ($data['resource_id'] && !$data['resource_path']) {
            $r = new AResource($data['rl_type']);
            $info = $r->getResource($data['resource_id']);
            if ($info['resource_path']) {
                $data['resource_path'] = $data['rl_type'] . '/' . $info['resource_path'];
            } else {
                //for code-resources
                $data['resource_path'] = $data['resource_id'];
            }
        }

        $this->view->batchAssign($data);

        return $this->view->fetch('form/resource.tpl');
    }
}

/**
 * Class ResourceImageHtmlElement
 *
 * @property int $width
 * @property int $height
 * @property string $attr
 * @property string $url
 */
class ResourceImageHtmlElement extends HtmlElement
{

    function __construct($data)
    {
        parent::__construct($data);
    }

    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'url'    => $this->url,
                'width'  => $this->width,
                'height' => $this->height,
                'attr'   => $this->attr
            ]
        );

        return $this->view->fetch('common/resource_image.tpl');
    }

}

/**
 * Class DateHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property string $default
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property string $dateformat
 * @property string $highlight
 * @property string $placeholder
 * @property array $extra
 */
class DateHtmlElement extends HtmlElement
{

    function __construct($data)
    {
        parent::__construct($data);
        if (!$this->registry->has('date-field')) {

            $doc = $this->registry->get('document');

            $doc->addStyle([
                'href'  => 'vendor/components/jqueryui/themes/ui-lightness/jquery-ui.min.css',
                'rel'   => 'stylesheet',
                'media' => 'screen',
            ]);
            $this->registry->set('date-field', 1);
        }
    }

    /**
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getHtml()
    {

        if (!isset($this->default)) {
            $this->default = '';
        }
        if ($this->value == '' && !empty($this->default)) {
            $this->value = $this->default;
        }
        $this->element_id = preg_replace('/[\[+\]]/', '_', $this->element_id);
        $this->view->batchAssign(
            [
                'name'        => $this->name,
                'id'          => $this->element_id,
                'type'        => 'text',
                'value'       => str_replace('"', '&quot;', $this->value),
                'default'     => $this->default,
                //TODO: remove deprecated attribute aform_field_type
                'attr'        => 'aform_field_type="date" ' . $this->attr . ' data-aform-field-type="captcha"',
                'required'    => $this->required,
                'style'       => $this->style,
                'dateformat'  => $this->dateformat ?: H::format4Datepicker($this->language->get('date_format_short')),
                'highlight'   => $this->highlight,
                'extra'       => $this->extra,
                'placeholder' => $this->placeholder
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/date.tpl');
    }
}

/**
 * Class EmailHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property string $default
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property string $placeholder
 * @property string $regexp_pattern
 * @property string $error_text
 * @property string $help_url
 */
class EmailHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {
        if (!isset($this->default)) {
            $this->default = '';
        }
        if ($this->value == '' && !empty($this->default)) {
            $this->value = $this->default;
        }
        $this->view->batchAssign(
            [
                'name'           => $this->name,
                'id'             => $this->element_id,
                'type'           => 'text',
                'value'          => str_replace('"', '&quot;', $this->value),
                'default'        => $this->default,
                //TODO: remove deprecated attribute aform_field_type
                'attr'           => 'aform_field_type="email" ' . $this->attr . ' data-aform-field-type="captcha"',
                'required'       => $this->required,
                'style'          => $this->style,
                'placeholder'    => $this->placeholder,
                'regexp_pattern' => trim($this->regexp_pattern, '/'),
                'error_text'     => $this->error_text
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/input.tpl');
    }
}

/**
 * Class NumberHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property string $default
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property string $placeholder
 * @property string $regexp_pattern
 * @property string $error_text
 * @property string $help_url
 */
class NumberHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {

        if (!isset($this->default)) {
            $this->default = '';
        }
        if ($this->value == '' && !empty($this->default)) {
            $this->value = $this->default;
        }
        $this->view->batchAssign(
            [
                'name'           => $this->name,
                'id'             => $this->element_id,
                'type'           => 'number',
                'value'          => str_replace('"', '&quot;', $this->value),
                'default'        => $this->default,
                //TODO: remove deprecated attribute aform_field_type
                'attr'           => 'aform_field_type="number" ' . $this->attr . ' data-aform-field-type="captcha"',
                'required'       => $this->required,
                'style'          => $this->style,
                'placeholder'    => $this->placeholder,
                'regexp_pattern' => trim($this->regexp_pattern, '/'),
                'error_text'     => $this->error_text
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/input.tpl');
    }
}

/**
 * Class PhoneHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property string $default
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property string $placeholder
 * @property string $regexp_pattern
 * @property string $error_text
 * @property string $help_url
 */
class PhoneHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {

        if (!isset($this->default)) {
            $this->default = '';
        }
        if ($this->value == '' && !empty($this->default)) {
            $this->value = $this->default;
        }

        $this->view->batchAssign(
            [
                'name'           => $this->name,
                'id'             => $this->element_id,
                'type'           => 'tel',
                'value'          => str_replace('"', '&quot;', $this->value),
                'default'        => $this->default,
                'attr'           => $this->attr,
                'required'       => $this->required,
                'style'          => $this->style,
                'placeholder'    => $this->placeholder,
                'regexp_pattern' => trim($this->regexp_pattern, '/'),
                'error_text'     => $this->error_text
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/phone.tpl');
    }
}

/**
 * Class IPaddressHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $attr
 */
class IPaddressHtmlElement extends HtmlElement
{

    public function getHtml()
    {
        $this->view->batchAssign(
            [
                'id'    => $this->element_id,
                'name'  => $this->name,
                'value' => $this->registry->get('request')->getRemoteIP(),
                //TODO: remove deprecated attribute aform_field_type
                'attr'  => 'aform_field_type="ipaddress" ' . $this->attr . ' data-aform-field-type="captcha"',
            ]
        );

        return $this->view->fetch('form/hidden.tpl');
    }
}

/**
 * Class CountriesHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property array $options
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property string $placeholder
 * @property string $help_url
 */
class CountriesHtmlElement extends HtmlElement
{

    public function __construct($data)
    {
        parent::__construct($data);
        $this->registry->get('load')->model('localisation/country');
        $results = $this->registry->get('model_localisation_country')->getCountries();
        $this->options = [];
        foreach ($results as $c) {
            $this->options[$c['name']] = $c['name'];
        }
    }

    public function getHtml()
    {

        if (!is_array($this->value)) {
            $this->value = [$this->value => (string)$this->value];
        }
        $this->options = !$this->options ? [] : $this->options;
        $this->view->batchAssign(
            [
                'name'        => $this->name,
                'id'          => $this->element_id,
                'value'       => $this->value,
                'options'     => $this->options,
                'attr'        => $this->attr,
                'required'    => $this->required,
                'style'       => $this->style,
                'placeholder' => $this->placeholder
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/selectbox.tpl');
    }
}

/**
 * Class ZonesHtmlElement
 *
 * @property string $element_id
 * @property string $name
 * @property string $value
 * @property string $submit_mode
 * @property int|array $default_value
 * @property string $zone_label
 * @property string $zone_field_name
 * @property string $default_zone_field_name
 * @property string $default_zone_name
 * @property array $default_zone_value
 * @property string $zone_name
 * @property array $options
 * @property array $zone_options
 * @property array|string|int $zone_value
 * @property string $style
 * @property string $attr
 * @property bool $required
 * @property bool $zone_required
 * @property string $placeholder
 * @property string $help_url
 */
class ZonesHtmlElement extends HtmlElement
{
    public function __construct($data)
    {
        parent::__construct($data);
        /** @var ModelLocalisationCountry $mdl */
        $mdl = $this->registry->get('load')->model('localisation/country');
        $results = $mdl->getCountries();
        $this->options = [];
        $this->zone_options = [];
        $this->default_zone_field_name = 'zone_id';
        $config_country_id = $this->registry->get('config')->get('config_country_id');
        foreach ($results as $c) {
            if ($c['country_id'] == $config_country_id) {
                $this->default_value = $this->submit_mode == 'id' ? [$config_country_id] : [$c['name'] => $c['name']];
            }
            if ($this->submit_mode == 'id') {
                $this->options[$c['country_id']] = $c['name'];
            } else {
                $this->options[$c['name']] = $c['name'];
            }
        }
    }

    public function getHtml()
    {
        if (!is_array($this->value)) {
            if (!$this->value) {
                $this->value = [];
            } else {
                $this->value = [$this->value => (string)$this->value];
            }
        }

        $this->zone_name = !$this->zone_name ? '' : urlencode($this->zone_name);
        $this->default_zone_value = [];
        $this->options = !$this->options ? [] : $this->options;
        $this->element_id = preg_replace('/[\[+\]]/', '_', $this->element_id);

        $html = new AHtml($this->registry);

        if ($this->submit_mode == 'id') {
            $url = $html->getSecureURL('common/zone');
        } else {
            $url = $html->getSecureURL('common/zone/names');
        }

        /** @var ModelLocalisationZone $zoneMdl */
        $zoneMdl = $this->registry->get('load')->model('localisation/zone');

        $config_country_id = $this->registry->get('config')->get('config_country_id');
        if ($this->submit_mode == 'id') {
            $id = $this->value ? key($this->value) : $config_country_id;
            $results = $zoneMdl->getZonesByCountryId($id);
        } else {
            if ($this->value) {
                $name = current($this->value);
            } else {
                /** @var ModelLocalisationCountry $countryMdl */
                $countryMdl = $this->registry->get('load')->model('localisation/country');
                $temp = $countryMdl->getCountry($config_country_id);
                $name = $temp['name'];
            }
            $results = $zoneMdl->getZonesByCountryName($name);
        }

        if (!is_array($this->zone_value)) {
            $this->zone_value = $this->zone_value ? [(string)$this->zone_value => (string)$this->zone_value] : [];
        }
        $config_zone_id = $this->registry->get('config')->get('config_zone_id');
        foreach ($results as $result) {
            // default zone_id is zone of shop
            if ($result['zone_id'] == $config_zone_id) {
                $this->default_zone_value = $this->submit_mode == 'id' ? [$config_zone_id] : [$result['name'] => $result['name']];
                $this->default_zone_name = $result['name'];
            }

            if ($this->submit_mode == 'id') {
                $this->zone_options[$result['zone_id']] = $result['name'];
            } else {
                $this->zone_options[$result['name']] = $result['name'];
            }
        }

        $this->view->batchAssign(
            [
                'name'            => $this->name,
                'id'              => $this->element_id,
                'value'           => $this->value ?: $this->default_value,
                'options'         => $this->options,
                'attr'            => $this->attr,
                'style'           => $this->style,
                'url'             => $url,
                'placeholder'     => $this->placeholder,
                'zone_label'      => $this->zone_label ?: $this->language->t('entry_zone', 'Region/State:'),
                'zone_field_name' => $this->zone_field_name ?: $this->default_zone_field_name,
                'zone_name'       => $this->zone_name ?: $this->default_zone_name,
                'zone_value'      => (array)($this->zone_value ?: $this->default_zone_value),
                'zone_options'    => $this->zone_options,
                'submit_mode'     => $this->submit_mode,

                'required'      => $this->required,
                'zone_required' => $this->zone_required ?? $this->required,
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/countries_zones.tpl');
    }

}

/*
* Build pagination HTML element based on the template.
* Supported v 1.1.5+
*/

class PaginationHtmlElement extends HtmlElement
{
    public $sts = [];

    /**
     * @param array $data
     *
     * @throws AException
     */
    public function __construct($data)
    {
        parent::__construct($data);
        //default settings
        $this->sts['total'] = 0;
        $this->sts['page'] = 1;
        $this->sts['limit'] = 20;
        $this->sts['split'] = 10;
        $this->sts['limits'] = [];
        //max pages to show in pagination
        $this->sts['num_links'] = 10;
        $this->sts['url'] = '';
        $this->sts['text'] = 'Showing {start} to {end} of {total} ({pages} Pages)';
        $this->sts['text_limit'] = 'Per Page';
        $this->sts['text_first'] = '&lt;&lt;';
        $this->sts['text_last'] = '&gt;&gt;';
        $this->sts['text_next'] = '&gt;';
        $this->sts['text_prev'] = '&lt;';
        $this->sts['style_links'] = 'links';
        $this->sts['style_results'] = 'results';
        $this->sts['style_limits'] = 'limits';
        //override default
        foreach ($this->data as $key => $val) {
            if (isset($val)) {
                $this->sts[$key] = $val;
            }
        }
    }

    /**
     * @return string
     * @throws Exception|InvalidArgumentException
     */
    public function getHtml()
    {
        //Build pagination data and display
        /**
         * @var $registry Registry
         */
        $registry = $this->registry;
        $html = new AHtml($registry);
        $s = $this->sts;
        //some more defaults
        if ($s['page'] < 1 || !is_numeric($s['page'])) {
            $s['page'] = 1;
        }
        if (!$s['limit'] || !is_numeric($s['limit'])) {
            $s['limit'] = 10;
        }

        //count limits if needed
        if (!$s['no_perpage'] && !$s['limits']) {
            $s['limits'][0] = $x = ($s['split'] ?: $registry->get('config')->get('config_catalog_limit'));
            while ($x <= 50) {
                $s['limits'][] = $x;
                $x += $s['limits'][0];
            }
        }

        $s['url'] = str_replace('{limit}', $s['limit'], $s['url']);
        $s['total_pages'] = ceil($s['total'] / $s['limit']);

        if ($s['page'] > 1) {
            //not first page
            $this->view->assign('first_url', str_replace('{page}', 1, $s['url']));
            $this->view->assign('prev_url', str_replace('{page}', $s['page'] - 1, $s['url']));
        }

        if ($s['total_pages'] > 1) {
            if ($s['total_pages'] <= $s['num_links']) {
                $s['start'] = 1;
                $s['end'] = $s['total_pages'];
            } else {
                $s['start'] = $s['page'] - floor($s['num_links'] / 2);
                $s['end'] = $s['page'] + floor($s['num_links'] / 2);

                if ($s['start'] < 1) {
                    $s['end'] += abs($s['start']) + 1;
                    $s['start'] = 1;
                }
                if ($s['end'] > $s['total_pages']) {
                    $s['start'] -= ($s['end'] - $s['total_pages']);
                    $s['end'] = $s['total_pages'];
                }
            }
        } else {
            $s['start'] = $s['end'] = 1;
        }

        if ($s['page'] < $s['total_pages']) {
            $this->view->assign('next_url', str_replace('{page}', $s['page'] + 1, $s['url']));
            $this->view->assign('last_url', str_replace('{page}', $s['total_pages'], $s['url']));
        }

        $replace = [
            ($s['total']) ? (($s['page'] - 1) * $s['limit']) + 1 : 0,
            ((($s['page'] - 1) * $s['limit']) > ($s['total'] - $s['limit'])) ? $s['total'] : ((($s['page'] - 1) * $s['limit']) + $s['limit']),
            $s['total'],
            $s['total_pages']
        ];

        if (!$s['no_perpage']) {
            if (!in_array($s['limit'], $s['limits'])) {
                $s['limits'][] = $s['limit'];
                sort($s['limits']);
            }
            $options = [];
            foreach ($s['limits'] as $item) {
                $options[$item] = $item;
            }

            $limit_url = str_replace('{page}', 1, $s['url']);
            $limit_url = str_replace('&amp;limit=' . $s['limit'], '', $limit_url);

            $limit_select = $html->buildSelectbox(
                [
                    'name'    => 'limit',
                    'value'   => $s['limit'],
                    'options' => $options,
                    'style'   => 'input-mini',
                    'attr'    => ' onchange="location=\'' . $limit_url . '&limit=\'+this.value;"',
                ]
            );

            $limit_select = str_replace('&', '&amp;', $limit_select);
            $this->view->assign('limit_select', $limit_select);
        }

        $find = [
            '{start}',
            '{end}',
            '{total}',
            '{pages}',
            '{limit}'
        ];
        $s['text'] = str_replace($find, $replace, $s['text']);
        $this->view->batchAssign($s);
        return $this->view->fetch('form/pagination.tpl');
    }

}

/**
 * Class ModalHtmlElement
 *
 * @property string $id
 * @property string $title
 * @property string $modal_type
 * @property string $content
 * @property string $footer
 * @property string $data_source
 * @property string $js_onshow
 * @property string $js_onload
 * @property string $js_onclose
 */
class ModalHtmlElement extends HtmlElement
{
    /**
     * @param array $data
     *
     * @throws AException
     */
    public function __construct($data)
    {
        parent::__construct($data);

    }

    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {

        $modal_type = $this->modal_type ?: 'lg';

        $this->view->batchAssign(
            [
                'id'          => $this->id,
                'title'       => $this->title,
                'content'     => $this->content,
                'footer'      => $this->footer,
                'modal_type'  => $modal_type,
                // if 'ajax' we clean up modal content after it close
                'data_source' => (string)$this->data_source,
                // js-triggers for modal events
                'js_onshow'   => (string)$this->js_onshow,
                'js_onload'   => ($this->data_source == 'ajax' ? (string)$this->js_onload : ';'),  //if content
                'js_onclose'  => (string)$this->js_onclose,
            ]
        );
        $tpl = 'form/modal.tpl';
        return $this->view->fetch($tpl);
    }

}

/**
 * Class LabelHtmlElement
 * NOTE: only for storefront
 *
 * @property string $element_id
 * @property string $name
 * @property string $text
 * @property string $value
 * @property string $style
 * @property string $attr
 */
class LabelHtmlElement extends HtmlElement
{
    /**
     * @return string
     * @throws Exception
     */
    public function getHtml()
    {
        if (ABC::env('IS_ADMIN') === true) {
            ADebug::error('labelHtmlElement', E_USER_ERROR, 'You cannot to build Label-field from Admin-side!');

            return null;
        }

        $this->view->batchAssign(
            [
                'name'  => $this->name,
                'id'    => $this->element_id,
                'text'  => str_replace('"', '&quot;', ($this->text ?: $this->value)),
                'attr'  => $this->attr,
                'style' => $this->style
            ]
        );
        if (!empty($this->help_url)) {
            $this->view->assign('help_url', $this->help_url);
        }

        return $this->view->fetch('form/label.tpl');
    }
}
