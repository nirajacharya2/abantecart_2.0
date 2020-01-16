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
use abc\core\view\AView;
use abc\core\lib\{
    AConfig, AWarning
};
use H;

/**
 * @property array $data
 * @property array $error
 * @property \abc\models\admin\ModelToolUpdater $model_tool_updater
 * @property \abc\models\admin\ModelSettingStore $model_setting_store
 * @property \abc\models\admin\ModelCatalogDownload $model_catalog_download
 * @property \abc\models\admin\ModelCatalogProduct | \abc\models\storefront\ModelCatalogProduct $model_catalog_product
 * @property \abc\models\admin\ModelCatalogManufacturer | \abc\models\storefront\ModelCatalogManufacturer $model_catalog_manufacturer
 * @property \abc\models\admin\ModelLocalisationStockStatus $model_localisation_stock_status
 * @property \abc\models\admin\ModelLocalisationTaxClass $model_localisation_tax_class
 * @property \abc\models\admin\ModelLocalisationWeightClass $model_localisation_weight_class
 * @property \abc\models\admin\ModelLocalisationLengthClass $model_localisation_length_class
 * @property \abc\models\admin\ModelToolImage | \abc\models\storefront\ModelToolImage $model_tool_image
 * @property \abc\models\admin\ModelSaleCustomerGroup $model_sale_customer_group
 * @property \abc\models\admin\ModelCatalogReview $model_catalog_review
 * @property \abc\models\admin\ModelSettingExtension $model_setting_extension
 * @property \abc\models\admin\ModelUserUserGroup $model_user_user_group
 * @property \abc\models\admin\ModelSettingSetting $model_setting_setting
 * @property \abc\models\admin\ModelUserUser $model_user_user
 * @property \abc\models\admin\ModelLocalisationCountry | \abc\models\storefront\ModelLocalisationCountry $model_localisation_country
 * @property \abc\models\admin\ModelLocalisationZone $model_localisation_zone
 * @property \abc\models\admin\ModelLocalisationLocation $model_localisation_location
 * @property \abc\models\admin\ModelLocalisationLanguage $model_localisation_language
 * @property \abc\models\admin\ModelLocalisationLanguageDefinitions $model_localisation_language_definitions
 * @property \abc\models\admin\ModelReportViewed $model_report_viewed
 * @property \abc\models\admin\ModelSaleCoupon $model_sale_coupon
 * @property \abc\models\admin\ModelSaleContact $model_sale_contact
 * @property \abc\models\admin\ModelToolBackup $model_tool_backup
 * @property \abc\models\admin\ModelToolGlobalSearch $model_tool_global_search
 * @property \abc\models\admin\ModelToolMigration $model_tool_migration
 * @property \abc\models\admin\ModelToolDatasetsManager $model_tool_dataset_manager
 * @property \abc\models\admin\ModelToolInstallUpgradeHistory $model_tool_install_upgrade_history
 * @property \abc\models\admin\ModelToolMessageManager $model_tool_message_manager
 * @property \abc\models\admin\ModelReportPurchased $model_report_purchased
 * @property \abc\models\admin\ModelReportSale $model_report_sale
 * @property \abc\models\admin\ModelToolPackageInstaller $model_tool_package_installer
 * @property \abc\models\storefront\ModelToolSeoUrl $model_tool_seo_url
 * @property \abc\models\storefront\ModelCheckoutExtension $model_checkout_extension
 * @property \abc\models\admin\ModelToolTableRelationships $model_tool_table_relationships
 * @property \abc\models\admin\ModelToolBackup $model_tools_backup
 * @property \abc\models\admin\ModelCatalogContent | \abc\models\storefront\ModelCatalogContent $model_catalog_content
 * @property \abc\models\admin\ModelToolDatasetsManager $model_tool_datasets_manager
 * @property \abc\core\lib\AConfig $config
 * @property \abc\core\lib\ADB $db
 * @property \abc\core\lib\AbcCache $cache
 * @property \abc\core\lib\ALanguageManager $language
 * @property AResource $resource
 * @property \abc\core\engine\ALoader $load
 * @property \abc\core\engine\ARouter $router
 * @property AHtml $html
 * @property \abc\core\lib\ARequest $request
 * @property \abc\core\lib\AResponse $response
 * @property \abc\core\lib\ASession $session
 * @property ExtensionsApi $extensions
 * @property \abc\core\lib\AExtensionManager $extension_manager
 * @property ALayout $layout
 * @property \abc\core\lib\ACurrency $currency
 * @property \abc\core\lib\ACart $cart
 * @property \abc\core\lib\ATax $tax
 * @property \abc\core\lib\AUser $user
 * @property \abc\core\lib\ALog $log
 * @property \abc\core\lib\AMessage $messages
 * @property \abc\core\lib\ACustomer $customer
 * @property \abc\core\lib\ADocument $document
 * @property ADispatcher $dispatcher
 * @property \abc\core\lib\ADataEncryption $dcrypt
 * @property \abc\models\admin\ModelToolFileUploads $model_tool_file_uploads
 * @property \abc\core\lib\ADownload $download
 * @property \abc\core\lib\AOrderStatus $order_status
 * @property \abc\core\lib\AIMManager $im
 * @property \abc\core\lib\CSRFToken $csrftoken
 * @property \abc\core\lib\Checkout | \abc\core\lib\CheckoutBase $checkout
 */
abstract class AController
{
    /**
     * @var ADispatcher
     */
    public $dispatcher;
    /**
     * @var AView
     */
    public $view;
    public $data = [];
    /**
     * @var Registry
     */
    protected $registry;
    protected $instance_id;
    protected $controller;
    protected $parent_controller;
    protected $children = [];
    protected $block_details = [];
    /**
     * @var AConfig
     */
    protected $config;
    protected $languages = [];
    protected $html_cache_key;

    /**
     * @param \abc\core\engine\Registry $registry
     * @param int $instance_id
     * @param string $controller
     * @param string|AController $parent_controller
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \abc\core\lib\AException
     */
    public function __construct($registry, $instance_id, $controller, $parent_controller = '')
    {
        $this->registry = $registry;
        $this->instance_id = $instance_id;
        $this->controller = $controller;
        $this->parent_controller = $parent_controller;

        //Instance of view for the controller
        $this->view = new AView($this->registry, $instance_id);
        $this->config = $this->registry->get('config');
        if ($this->language) {
            //add main language to languages references and map to view
            $this->loadLanguage($this->language->language_details['filename']);
            //try to map controller language to view
            $this->loadLanguage($this->controller, "silent");
        }
        //Load default model for current controller instance. Ignore if no model found  mode = silent
        $this->loadModel($this->controller, "silent");

        if ($this->layout) {
            //Load Controller template and pass to view. This can be reset in controller as well
            $this->view->setTemplate($this->layout->getBlockTemplate($this->instance_id));
            //Load Children from layout if any. 'instance_id', 'controller', 'block_text_id', 'template'
            $this->block_details = $this->layout->getBlockDetails($this->instance_id);
            $this->children = $this->layout->getChildren($this->instance_id);
        }

        //set embed mode if passed
        if ($this->request->get['embed_mode']) {
            /**
             * @var AConfig $config
             */
            $config = $this->registry->get('config');
            $config->set('embed_mode', true);
        }
    }

    public function __destruct()
    {
        if (isset($this->language)) {
            //clean up the scope
            $this->language->setLanguageScope([]);
        }
        $this->clear();
    }

    /**
     * Function to enable caching for this page/block
     *
     * @return true/false
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function html_cache()
    {
        //check is HTML cache is enabled and it is storefront
        if (!$this->config->get('config_html_cache') || ABC::env('IS_ADMIN')) {
            return false;
        }
        //build HTML cache key if not yet built for this controller.
        if (!$this->html_cache_key) {
            $this->html_cache_key = $this->buildHTMLCacheKey();
        }

        //check if can load HTML files and stop
        return $this->view->checkHTMLCache($this->html_cache_key);
    }

    //function to get html cache key
    public function buildHTMLCacheKey($allowed_params = [], $values = [], $controller = '')
    {
        //build HTML cache key
        //build cache string based on allowed params
        $cache_params = [];
        if (is_array($allowed_params) && $allowed_params) {
            sort($allowed_params);
            foreach ($allowed_params as $key) {
                if (H::has_value($values[$key])) {
                    $cache_params[$key] = $values[$key];
                }
            }
        }
        //build unique key based on params
        $param_string = md5($this->cache->paramsToString($cache_params));
        //build HTML cache path
        $cache_state_vars = [
            'template'      => $this->config->get('config_storefront_template'),
            'store_id'      => $this->config->get('config_store_id'),
            'language_id'   => $this->language->getLanguageID(),
            'currency_code' => $this->currency->getCode(),
            //in case with shared ssl-domain
            'https'         => (ABC::env('HTTPS') ? 1 : 0),
        ];
        if (is_object($this->customer)) {
            $cache_state_vars['customer_group_id'] = $this->customer->getCustomerGroupId();
        }
        if (!$controller) {
            $controller = $this->controller;
        }
        //NOTE: Blocks are cached based on unique instanced ID
        $this->html_cache_key = 'html_cache.'.str_replace('/', '.', $controller).".".implode('.',
                $cache_state_vars)."_".$this->instance_id;
        //add specific params to the key
        if ($param_string) {
            $this->html_cache_key .= "_".$param_string;
        }
        //pass html_cache_key to view for future use
        $this->view->setCacheKey($this->html_cache_key);

        return $this->html_cache_key;
    }

    //function to get html cache key
    public function getHTMLCacheKey()
    {
        return $this->html_cache_key;
    }

    //Get cache key values for provided controller
    public function getCacheKeyValues($controller)
    {
        //check if requested controller allows HTML caching
        //use dispatcher to get class and details
        $ds = new ADispatcher($controller, ["instance_id" => "0"]);
        $rt_class = $ds->getClass();
        $rt_file = $ds->getFile();
        $rt_method = $ds->getMethod();
        if (!empty($rt_file) && !empty($rt_class) && !empty($rt_method)) {
            require_once($rt_file);
            if (class_exists($rt_class)) {
                $static_method = $rt_method.'_cache_keys';
                if (method_exists($rt_class, $static_method)) {
                    //finally get keys and build a cache key
                    $cache_keys = call_user_func($rt_class.'::'.$static_method);
                    return $cache_keys;
                }
            }
        }

        return false;
    }

    //Quick access to controller name or rt
    public function rt()
    {
        return $this->controller;
    }

    // Clear function is public in case controller needs to be cleaned explicitly
    public function clear()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $val) {
            $this->$key = null;
        }
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    //Load language and store to view
    public function loadLanguage($rt, $mode = '')
    {
        if (empty ($rt) || !method_exists($this->language, 'load')) {
            return null;
        }
        // strip off pages or response
        $rt = preg_replace('/^(api|pages|responses)\//', '', $rt);
        $this->languages[] = $rt;
        //load all translations to the view
        $this->view->batchAssign($this->language->load($rt, $mode));
    }

    public function loadModel($rt, $mode = '')
    {
        if (empty ($rt) || !method_exists($this->load, 'model')) {
            return null;
        }
        // strip off pages or response
        $rt = preg_replace('/^(pages|responses)\//', '', $rt);

        return $this->load->model($rt, $mode);
    }

    // Dispatch new controller to be ran
    protected function dispatch($dispatch_rt, $args = [''])
    {
        return new ADispatcher($dispatch_rt, $args);
    }

    public function getInstance()
    {
        return $this->instance_id;
    }

    public function getChildren()
    {
        //Check if we have children in layout
        return $this->children;
    }

    public function resetChildren()
    {
        $this->children = [];
        return $this->children;
    }

    public function setChildren($children)
    {
        $this->children = $children;
    }

    public function getChildrenBlocks()
    {
        $blocks = [];
        // Look into all blocks that are loaded from layout database or have position set for them
        // Hardcoded children with blocks require manual inclusion to the templates.
        foreach ($this->children as $block) {
            if (!empty($block['position'])) {
                //assign count based on position (currently div. by 10)
                if ((int)$block['position'] % 10 == 0) {
                    $blocks[(int)($block['position'] / 10 - 1)] = $block['block_txt_id'].'_'.(int)$block['instance_id'];
                } else {
                    array_push($blocks, $block['block_txt_id'].'_'.$block['instance_id']);
                }
            }
        }
        return $blocks;
    }

    // Add Child controller to be processed

    /**
     * @param string $new_controller
     * @param string $block_text_id
     * @param string $new_template
     * @param string $template_position
     */
    public function addChild($new_controller, $block_text_id, $new_template = '', $template_position = '')
    {
        // append child to the controller children list
        $new_block = [];
        $new_block['parent_instance_id'] = $this->instance_id;
        $new_block['instance_id'] = $block_text_id.$this->instance_id;
        $new_block['block_id'] = $block_text_id;
        $new_block['controller'] = $new_controller;
        $new_block['block_txt_id'] = $block_text_id;
        $new_block['template'] = $new_template;
        // This it to position element to the placeholder.
        // If not set element will not be displayed in place holder.
        // To use manual inclusion to parent template ignore this parameter
        $new_block['position'] = $template_position;
        array_push($this->children, $new_block);
    }

    /**
     * @param string $template
     */
    public function processTemplate($template = '')
    {
        //is this an embed mode? Special templates needs to be loaded
        if (is_object($this->registry->get('config')) && $this->registry->get('config')->get('embed_mode') == true) {
            //get template if it was set earlier
            if (empty($template)) {
                $template = $this->view->getTemplate();
            }
            //only substitute the template for page templates
            if (substr($template, 0, 6) == 'pages/' && substr($template, 0, 6) != 'embed/') {
                //load special headers for embed as no page/layout needed
                $this->addChild('responses/embed/head', 'head');
                $this->addChild('responses/embed/footer', 'footer');
                $template = preg_replace('/pages\//', 'embed/', $template);
            }
        }

        if (!empty($template)) {
            $this->view->setTemplate($template);
        }
        $this->view->assign('block_details', $this->block_details);
        $this->view->assign("children_blocks", $this->getChildrenBlocks());
        $this->view->enableOutput();
    }

    public function finalize()
    {
        //Render the controller output in view

        // template debug
        if ($this->config) {
            if ($this->config->get('storefront_template_debug')) {
                // storefront enabling
                if (!ABC::env('IS_ADMIN') && !isset($this->session->data['tmpl_debug'])
                    && isset($this->request->get['tmpl_debug'])) {
                    $this->session->data['tmpl_debug'] = isset($this->request->get['tmpl_debug']);
                }

                if ((isset($this->session->data['tmpl_debug'])
                        && isset($this->request->get['tmpl_debug']))
                    && ($this->session->data['tmpl_debug'] == $this->request->get['tmpl_debug'])
                ) {

                    $block_details = $this->layout->getBlockDetails($this->instance_id);
                    $excluded_blocks = ['common/head'];

                    if (!empty($this->instance_id) && (string)$this->instance_id != '0'
                        && !in_array($block_details['controller'],
                            $excluded_blocks)
                    ) {
                        if (!empty($this->parent_controller)) {
                            //build block template file path based on primary template used
                            //template path is based on parent block 'template_dir'
                            $tmp_dir = $this->parent_controller->view->data['template_dir']."/";
                            $block_tpl_file = $tmp_dir.$this->view->getTemplate();
                            $prt_block_tpl_file = $tmp_dir.$this->parent_controller->view->getTemplate();
                            $args = [
                                'block_id'          => $this->instance_id,
                                'block_controller'  => $this->dispatcher->getFile(),
                                'block_tpl'         => $block_tpl_file,
                                'parent_id'         => $this->parent_controller->instance_id,
                                'parent_controller' => $this->parent_controller->dispatcher->getFile(),
                                'parent_tpl'        => $prt_block_tpl_file,
                            ];
                            $debug_wrapper = $this->dispatch('common/template_debug',
                                ['instance_id' => $this->instance_id, 'details' => $args]);
                            $debug_output = $debug_wrapper->dispatchGetOutput();
                            $output = trim($this->view->getOutput());
                            if (!empty($output)) {
                                $output = '<span class="block_tmpl_wrapper">'.$output.$debug_output.'</span>';
                            }
                            $this->view->setOutput($output);
                        }
                    }

                }
            } else {
                unset($this->session->data['tmpl_debug']);
            }
        }
        $this->view->render();
    }

    /**
     * Set of functions to access parent controller and exchange information
     *
     * @param string $parent_controller_name
     * @param string $variable
     * @param mixed $value
     */
    public function addToParentByName($parent_controller_name, $variable, $value)
    {
        if ($parent_controller_name == $this->instance_id) {
            $this->view->append($variable, $value);
        } else {
            if (!empty ($this->parent_controller)) {
                $this->parent_controller->AddToParentByName($parent_controller_name, $variable, $value);
            } else {
                $wrn =
                    new AWarning('Call to unknown parent controller '.$parent_controller_name.' in '.get_class($this));
                $wrn->toDebug();
            }
        }
    }

    /**
     * Add value to direct parent
     *
     * @param string $variable
     * @param mixed $value
     */
    public function addToParent($variable, $value)
    {
        if (!empty ($this->parent_controller)) {
            $this->parent_controller->view->append($variable, $value);
        } else {
            $warning = new AWarning('Parent controller called does not exist in '.get_class($this));
            $warning->toDebug();
        }
    }

    public function can_access()
    {
        if (!ABC::env('IS_ADMIN')) {
            return null;
        }

        //Future stronger security permissions validation
        //validate session token and login
        // Dispatch to login if failed
        // validate access rights for current controller or parent with $parent_controller->can_access()
        // If both have no access rights dispatch to no rights page

        // NOTEs: Need to skip for some common controllers.
        // Need to include this validation in constructor and break out of it if failed.
    }

    /**
     * Generate the URL to external help
     *
     * @param string $sub_key
     *
     * @return null|string
     */
    public function gen_help_url($sub_key = '')
    {
        if ($this->config->get('config_help_links') != 1) {
            return null;
        }
        if (!empty($sub_key)) {
            $main_key = $sub_key;
        } else {
            $main_key = str_replace('/', '_', $this->controller);
        }
        $url = "http://docs.abantecart.com/tag/".$main_key;
        return $url;
    }

}
