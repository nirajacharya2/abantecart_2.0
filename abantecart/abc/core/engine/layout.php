<?php

/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

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
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\models\layout\BlockDescription;
use abc\models\layout\BlockTemplate;
use abc\models\layout\Layout;
use abc\models\layout\Page;
use abc\models\QueryBuilder;
use abc\modules\traits\LayoutTrait;
use Exception;
use H;

class ALayout
{
    use LayoutTrait;

    /** @var Registry */
    protected $registry;
    public $blocks = [];
    /** @var string */
    private $templateTextId;
    /** @var int */
    public $page_id;

    const DEFAULT_PAGE_LAYOUT_TYPE = 0;
    const OTHER_PAGE_LAYOUT_TYPE = 1;

    /**
     * @param string|null $template_id
     */
    public function __construct(?string $template_id = 'default')
    {
        $this->templateTextId = $template_id;
        $this->page_id = '';
    }

    /**
     * @param string $controller
     *
     * @return int
     * @throws AException
     */
    public function buildPageData($controller)
    {

        //for Maintenance mode
        if (Registry::config()->get('config_maintenance')) {
            $aUser = ABC::getObjectByAlias('AUser', [Registry::getInstance()]);
            Registry::getInstance()->set('user', $aUser);
            if (!$aUser->isLogged()) {
                $controller = 'pages/index/maintenance';
            }
        }

        // Locate and set page information. This needs to be called once per page
        $unique_page = [];

        // find page records for given controller
        $key_param = $this->getKeyParamByController($controller);
        $key_value = $key_param ? Registry::request()->get[$key_param] : null;
        // for nested categories
        if ($key_param == 'path' && $key_value && is_int(strpos($key_value, '_'))) {
            $key_value = (int)substr($key_value, strrpos($key_value, '_') + 1);
        }

        $key_param = !$key_value ? null : $key_param;

        $pages = $this->getPages($controller, $key_param, $key_value);
        if (!$pages) {
            //if no specific page found try to get page for group
            $new_path = preg_replace('/\/\w*$/', '', $controller);
            $pages = $this->getPages($new_path);
        }

        if (!$pages) {
            //if no specific page found load generic
            $pages = $this->getPages('generic');

        } else {
            /* if specific pages with key_param presents...
             in any case first row will be that what we need (see sql "order by" in getPages method) */
            $unique_page = $pages[0];
        }
        // look for key_param and key_value in the request
        /*
        Steps to perform
        1. Based on rt (controller) select all rows from pages table where controller = "$controller"
        2. Based on $key_param = key_param from pages table for given $controller
            locate this $key_param value from CGI input.
        You will have $key_param and $key_value pair.
        NOTE: key_param will be unique per controller. More optimized
             select can be used to get key_param from pages table.
        3. Locate id from pages table based on $key_param and $key_value pair
             where controller = "$controller"
                and key_param = $key_param
                and key_value = $this->request->get[$key_param];
        NOTE: Do select only if value present.
        4. If locate page id use the layout.
        */

        $page = $unique_page ?: $pages[0];
        $this->page_id = $page['page_id'];
        //if no page found set default page id 1
        $layoutType = self::OTHER_PAGE_LAYOUT_TYPE;
        if (!$this->page_id) {
            $this->page_id = 1;
        }
        if ($this->page_id == 1) {
            //for generic page need to load default layout type
            $layoutType = self::DEFAULT_PAGE_LAYOUT_TYPE;
        }

        //Get the page layout
        $layouts = $this->getLayouts($this->templateTextId, $this->page_id, $layoutType);
        if (sizeof($layouts) == 0) {
            //No page specific layout found, load default layout
            $layouts = $this->getDefaultLayout();
            if (sizeof($layouts) == 0) {
                // ????? How to terminate ????
                throw new AException(
                    'No layout found for page_id/controller ' . $this->page_id . '::' . $page['controller'] . '! '
                    . H::genExecTrace('full'),
                    AC_ERR_LOAD_LAYOUT
                );
            }
        }

        $layout = $layouts[0];
        $layout_id = $layout['layout_id'];

        // Get all blocks for the page;
        $blocks = $this->getAlllayoutBlocks($layout_id);
        $this->blocks = $blocks;
        return $this->page_id;
    }

    /**
     * @param string $controller
     * @param string $keyParameter
     * @param string $keyValue
     *
     * @return array|null
     */
    public function getPages($controller = '', $keyParameter = '', $keyValue = '')
    {
        $query = Page::select('pages.*');
        if ($controller) {
            $query->where('pages.controller', '=', $controller)
                ->leftJoin('pages_layouts', 'pages_layouts.page_id', '=', 'pages.page_id')
                ->leftJoin('layouts', 'pages_layouts.layout_id', '=', 'layouts.layout_id');

            if (!empty ($keyParameter)) {
                if (!empty ($keyValue)) {
                    // so if we have key_param key_value pair we select
                    // pages with controller and with or without key_param
                    $query->where('layouts.template_id', '=', $this->templateTextId)
                        ->where(
                            function ($subQuery) use ($keyParameter, $keyValue) {
                                /** @var QueryBuilder $subQuery */
                                $subQuery->where('pages.key_param', '=', '')
                                    ->orWhere(
                                        function ($subSubQuery) use ($keyParameter, $keyValue) {
                                            /** @var QueryBuilder $subSubQuery */
                                            $subSubQuery->where(
                                                [
                                                    'pages.key_param' => $keyParameter,
                                                    'pages.key_value' => $keyValue,
                                                ]
                                            );
                                        }
                                    );
                            }
                        );
                } else { //write to log this stuff. it's abnormal situation
                    $request = Registry::request();
                    $message = "Error: Error in data of page with controller: '" . $controller . "'. "
                        . "Please check for key_value present where key_param was set.\n";
                    $message .= "Requested URL: "
                        . $request->server['REQUEST_SCHEME'] . '://'
                        . $request->server['HTTP_HOST']
                        . $request->server['REQUEST_URI'] . "\n";
                    $message .= "Referer URL: " . $request->server['HTTP_REFERER'];
                    $message .= "Page Key Parameter: " . $keyParameter;
                    $error = new AError ($message);
                    $error->toLog()->toDebug();
                }
            }
        }
        return $query->orderBy('pages.key_param', 'desc')
            ->orderBy('pages.key_value', 'desc')
            ->orderBy('pages.page_id')
            ->useCache('layout')->get()?->toArray();
    }

    /**
     * @param string $route
     *
     * @return string
     */
    public function getKeyParamByController($route = '')
    {
        return match ($route) {
            'pages/product/product' => 'product_id',
            'pages/product/manufacturer' => 'manufacturer_id',
            'pages/product/category' => 'path',
            'pages/content/content' => 'content_id',
            default => '',
        };
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getDefaultLayout()
    {
        return Layout::where(
            [
                'template_id' => 'default',
                'layout_type' => 0
            ]
        )->orderBy('layout_id')
            ->useCache('layout')
            ->get()?->toArray();
    }


    /**
     * @param int $instance_id
     *
     * @return array
     */
    public function getChildren($instance_id = null)
    {
        $children = [];
        //DO NOT SEEK CHILDREN WHEN instance_id NOT SET
        //need to prevent lop of calls $dispatcher->getDispatchOutput($controller)
        /** @see AController::children */
        if (!isset($instance_id)) {
            return [];
        }
        // Look into all blocks and locate all children
        foreach ($this->blocks as $block) {
            if ((int)$block['parent_instance_id'] == $instance_id) {
                $children[] = $block;
            }
        }
        return $children;
    }

    /**
     * @param $child_instance_id
     *
     * @return array
     */
    public function getBlockDetails($child_instance_id)
    {
        //Select block details by controller
        foreach ($this->blocks as $block) {
            if ($block['instance_id'] == $child_instance_id) {
                return $block;
            }
        }
        return [];
    }

    /**
     * @param int $instance_id
     * @param string $new_child
     * @param string $block_txt_id
     * @param string $template
     */
    public function addChildFirst($instance_id, $new_child, $block_txt_id, $template)
    {
        $new_block = [];
        $new_block['parent_instance_id'] = $instance_id;
        $new_block['instance_id'] = $block_txt_id . $instance_id;
        $new_block['block_id'] = $block_txt_id;
        $new_block['controller'] = $new_child;
        $new_block['block_txt_id'] = $block_txt_id;
        $new_block['template'] = $template;
        array_unshift($this->blocks, $new_block);
    }

    /**
     * @param int $instance_id
     * @param string $new_child
     * @param string $block_txt_id
     * @param string $template
     */
    public function addChild($instance_id, $new_child, $block_txt_id, $template)
    {
        $this->blocks[] = [
            'parent_instance_id' => $instance_id,
            'block_id'           => $block_txt_id,
            'controller'         => $new_child,
            'block_txt_id'       => $block_txt_id,
            'template'           => $template,
            'instance_id'        => $block_txt_id . $instance_id
        ];
    }

    /**
     * @param int $instance_id
     *
     * @return string
     * @throws Exception
     */
    public function getBlockTemplate($instance_id)
    {
        //Select block and parent id by controller
        $block_id = '';
        $parent_block_id = '';
        $parent_instance_id = '';
        $template = '';

        //locate block id
        foreach ($this->blocks as $block) {
            if ($block['instance_id'] == $instance_id) {
                $block_id = $block['block_id'];
                $parent_instance_id = $block['parent_instance_id'];
                $template = !empty($block['template']) ? $block['template'] : '';
                break;
            }
        }

        //Check if we do not have template set yet in the code
        if ($template) {
            return $template;
        }
        //locate true parent_block id. Not to confuse with parent_instance_id
        foreach ($this->blocks as $block) {
            if ($block['instance_id'] == $parent_instance_id) {
                $parent_block_id = $block['block_id'];
                break;
            }
        }
        if (!empty($block_id) && !empty($parent_block_id)) {
            $template = (string)BlockTemplate::where('block_id', '=', $block_id)
                ->whereIn('parent_block_id', [$parent_block_id, 0])
                ->orderBy('parent_block_id', 'desc')
                ->useCache('layout')->first()?->template;
        }
        return $template;
    }

    /**
     * @param int $custom_block_id
     *
     * @return array
     * @throws Exception
     */
    public function getBlockDescriptions($custom_block_id = 0)
    {
        if (!(int)$custom_block_id) {
            return [];
        }
        $output = [];

        $result = BlockDescription::select(['block_descriptions.*', 'block_layouts.status'])
            ->leftJoin('block_layouts', 'block_layouts.custom_block_id', '=', 'block_descriptions.custom_block_id')
            ->where('block_descriptions.custom_block_id', '=', $custom_block_id)
            ->useCache('layout')
            ->get()?->toArray();
        if ($result) {
            foreach ($result as $row) {
                $output[$row['language_id']] = $row;
            }
        }
        return $output;
    }
}