<?php /** @noinspection SqlResolve */

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
use abc\core\lib\AbcCache;
use abc\core\lib\AConfig;
use abc\core\lib\ADB;
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AMessage;
use abc\core\lib\ARequest;
use abc\core\lib\AUser;
use Exception;
use H;
use Psr\SimpleCache\InvalidArgumentException;


/**
 * Class ALayout
 *
 * @property AbcCache $cache
 * @property AUser $user
 * @property AConfig $config
 * @property ADB $db
 * @property AMessage $messages
 * @property ARequest $request
 */
class ALayout
{
    /** @var Registry */
    protected $registry;
    public $blocks = [];
    /** @var string */
    private $tmpl_id;
    /** @var int */
    public $page_id;

    const DEFAULT_PAGE_LAYOUT_TYPE = 0;
    const OTHER_PAGE_LAYOUT_TYPE = 1;

    /**
     * @param $registry Registry
     * @param int $template_id
     */
    public function __construct($registry, $template_id)
    {
        $this->registry = $registry;
        $this->tmpl_id = $template_id;
        $this->page_id = '';
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
     * @param string $controller
     *
     * @return int
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function buildPageData($controller)
    {

        //for Maintenance mode
        if ($this->config->get('config_maintenance')) {
            $this->registry->set('user', ABC::getObjectByAlias('AUser',[$this->registry]));
            if (!$this->user->isLogged()) {
                $controller = 'pages/index/maintenance';
            }
        }

        // Locate and set page information. This needs to be called once per page
        $unique_page = [];

        // find page records for given controller
        $key_param = $this->getKeyParamByController($controller);
        $key_value = $key_param ? $this->request->get[$key_param] : null;
        // for nested categories
        if ($key_param == 'path' && $key_value && is_int(strpos($key_value, '_'))) {
            $key_value = (int)substr($key_value, strrpos($key_value, '_') + 1);
        }

        $key_param = !$key_value ? null : $key_param;

        $pages = $this->getPages($controller, $key_param, $key_value);
        if (empty($pages)) {
            //if no specific page found try to get page for group
            $new_path = preg_replace('/\/\w*$/', '', $controller);
            $pages = $this->getPages($new_path);
        }

        if (empty($pages)) {
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

        $page = !empty($unique_page) ? $unique_page : $pages[0];
        $this->page_id = $page['page_id'];
        //if no page found set default page id 1
        $layoutType = self::OTHER_PAGE_LAYOUT_TYPE;
        if (empty($this->page_id)) {
            $this->page_id = 1;
        }
        if ($this->page_id == 1) {
            //for generic page need to load default layout type
            $layoutType = self::DEFAULT_PAGE_LAYOUT_TYPE;
        }

        //Get the page layout
        $layouts = $this->getLayouts($layoutType);
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
        $blocks = $this->getlayoutBlocks($layout_id);
        $this->blocks = $blocks;
        return $this->page_id;
    }

    /**
     * @param string $controller
     * @param string $key_param
     * @param string $key_value
     *
     * @return array|null
     * @throws InvalidArgumentException
     */
    public function getPages($controller = '', $key_param = '', $key_value = '')
    {
        $store_id = (int)$this->config->get('config_store_id');
        $cache_key = 'layout.pages'
            . (!empty($controller) ? '.' . $controller : '')
            . (!empty($key_param) ? '.' . $key_param : '')
            . (!empty($key_value) ? '.' . $key_value : '');
        $cache_key = preg_replace('/[^a-zA-Z0-9.]/', '', $cache_key) . '.store_' . $store_id;
        $pages = $this->cache->get($cache_key);
        if ($pages !== null) {
            // return cached pages
            return $pages;
        }

        $where = '';
        if (!empty ($controller)) {
            $where .= "WHERE controller = '".$this->db->escape($controller)."' ";
            if (!empty ($key_param)) {
                if (!empty ($key_value)) {
                    // so if we have key_param key_value pair we select
                    // pages with controller and with or without key_param
                    $where .= " AND ( COALESCE( key_param, '' ) = ''
                                      OR
                                        ( key_param = '".$this->db->escape($key_param)."'
                                            AND key_value = '".$this->db->escape($key_value)."' ) )
                                AND template_id = '".$this->tmpl_id."' ";

                } else { //write to log this stuff. it's abnormal situation
                    $message = "Error: Error in data of page with controller: '".$controller."'. "
                              ."Please check for key_value present where key_param was set.\n";
                    $message .= "Requested URL: "
                                .$this->request->server['REQUEST_SCHEME'].'://'
                                .$this->request->server['HTTP_HOST'].$this->request->server['REQUEST_URI']."\n";
                    $message .= "Referer URL: ".$this->request->server['HTTP_REFERER'];
                    $message .= "Page Key Parameter: ".$key_param;
                    $error = new AError ($message);
                    $error->toLog()->toDebug();
                }
            }
        }

        $sql = "SELECT p.page_id, controller, key_param, key_value, p.date_added, p.date_modified "
                ."FROM ".$this->db->table_name("pages")." p "
                ."LEFT JOIN ".$this->db->table_name("pages_layouts")." pl 
                    ON pl.page_id = p.page_id "
                ."LEFT JOIN ".$this->db->table_name("layouts")." l 
                    ON l.layout_id = pl.layout_id "
                .$where
                ."ORDER BY key_param DESC, key_value DESC, p.page_id ASC";
        $query = $this->db->query($sql);
        $pages = $query->rows;
        $this->cache->put($cache_key, $pages);
        return $pages;
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
     * @throws InvalidArgumentException
     */
    public function getDefaultLayout()
    {
        $store_id = (int)$this->config->get('config_store_id');
        $cache_key = 'layout.default.' . $this->tmpl_id;
        $cache_key = preg_replace('/[^a-zA-Z0-9.]/', '', $cache_key) . '.store_' . $store_id;
        $layouts = $this->cache->get($cache_key);

        if ($layouts === null) {
            $where = "WHERE template_id = 'default' AND layout_type = '0' ";

            $sql = "SELECT layout_id, layout_type, layout_name, date_added, date_modified
                    FROM " . $this->db->table_name("layouts") . "
                    " . $where . "
                    ORDER BY layout_id";

            $result = $this->db->query($sql);
            $layouts = $result->rows;

            $this->cache->put($cache_key, $layouts);
        }

        return $layouts;
    }

    /**
     * @param string $layout_type
     *
     * @return array|null
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getLayouts($layout_type = null)
    {
        //No page id, not need to be here
        if (empty($this->page_id)) {
            return null;
        }
        $store_id = (int)$this->config->get('config_store_id');
        $cache_key = 'layout.layouts.' . $this->tmpl_id . '.'
            . $this->page_id . (!empty($layout_type) ? '.' . $layout_type : '');
        $cache_key = preg_replace('/[^a-zA-Z0-9.]/', '', $cache_key) . '.store_' . $store_id;

        $layouts = $this->cache->get($cache_key);
        if ($layouts === null) {
            $where = 'WHERE l.template_id = "'.$this->db->escape($this->tmpl_id).'" ';
            $join = ", ".$this->db->table_name("pages_layouts")." as pl ";
            $where .= " AND pl.page_id = '".(int)$this->page_id."' AND l.layout_id = pl.layout_id ";

            if (!is_null($layout_type)) {
                $where .= empty($layout_type) ? "" : "AND l.layout_type = '".(int)$layout_type."' ";
            }

            $sql = "SELECT "
                ."l.layout_id as layout_id, "
                ."l.layout_type as layout_type, "
                ."l.layout_name as layout_name, "
                ."l.date_added as date_added, "
                ."l.date_modified as date_modified "
                ."FROM "
                .$this->db->table_name("layouts")." as l "
                .$join
                .$where
                ." ORDER BY "
                ."l.layout_id Asc";
            $query = $this->db->query($sql);
            $layouts = $query->rows;
            if ($layouts) {
                $this->cache->put($cache_key, $layouts);
            }
        }
        return $layouts;
    }

    /**
     * @param int $layout_id
     *
     * @return array|null
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function getLayoutBlocks($layout_id)
    {
        if (empty($layout_id)) {
            throw new AException('No layout specified for getLayoutBlocks!'.$layout_id, AC_ERR_LOAD_LAYOUT);
        }
        $store_id = (int)$this->config->get('config_store_id');
        $cache_key = 'layout.blocks.' . $layout_id;
        $cache_key = preg_replace('/[^a-zA-Z0-9.]/', '', $cache_key) . '.store_' . $store_id;
        $blocks = $this->cache->get($cache_key);
        if ($blocks === null) {
            $where = "WHERE bl.layout_id = '".$layout_id."' ";
            $where .= "AND bl.block_id = b.block_id AND bl.status = 1 ";

            $sql = "SELECT "
                ."bl.instance_id as instance_id, "
                ."b.block_id as block_id, "
                ."bl.custom_block_id, "
                ."COALESCE(bl.parent_instance_id,0) as parent_instance_id, "
                ."bl.position as position, "
                ."b.block_txt_id as block_txt_id, "
                ."b.controller as controller "
                ."FROM "
                .$this->db->table_name("blocks")." as b, "
                .$this->db->table_name("block_layouts")." as bl "
                .$where
                ."ORDER BY "
                ."bl.parent_instance_id Asc, bl.position Asc";

            $query = $this->db->query($sql);
            $blocks = $query->rows;
            $this->cache->put($cache_key, $blocks);
        }
        return $blocks;
    }

    /**
     * @param int $instance_id
     *
     * @return array
     */
    public function getChildren($instance_id = 0)
    {
        $children = [];
        // Look into all blocks and locate all children
        foreach ($this->blocks as $block) {
            if ((string)$block['parent_instance_id'] == (string)$instance_id) {
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
        $new_block['instance_id'] = $block_txt_id.$instance_id;
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
        $new_block = [];
        $new_block['parent_instance_id'] = $instance_id;
        $new_block['instance_id'] = $block_txt_id.$instance_id;
        $new_block['block_id'] = $block_txt_id;
        $new_block['controller'] = $new_child;
        $new_block['block_txt_id'] = $block_txt_id;
        $new_block['template'] = $template;
        $this->blocks[] = $new_block;
    }

    /**
     * @param int $instance_id
     *
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getBlockTemplate($instance_id)
    {
        //Select block and parent id by controller
        $block_id = '';
        $parent_block_id = '';
        $parent_instance_id = '';
        $template = '';
        $store_id = (int)$this->config->get('config_store_id');

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

            $cache_key = 'layout.block.template.' . $block_id . '.' . $parent_block_id;
            $cache_key = preg_replace('/[^a-zA-Z0-9.]/', '', $cache_key) . '.store_' . $store_id;
            $template = $this->cache->get($cache_key);
            if ($template === null) {

                $where = 'WHERE bt.block_id = "'.(int)($block_id).'" ';
                //locate template based on block parent ID or 0 if generic template is set
                $where .= 'AND bt.parent_block_id in ('.(int)$parent_block_id.', 0) ';

                $sql = "SELECT "
                    ."bt.template as template, "
                    ."bt.date_added as date_added, "
                    ."bt.date_modified as date_modified "
                    ."FROM "
                    .$this->db->table_name("block_templates")." as bt "
                    .$where
                    ."ORDER BY "
                    ."bt.parent_block_id Desc";

                $query = $this->db->query($sql);
                $template = (string)$query->row['template'];
                $this->cache->put($cache_key, $template);
            }
        }
        return $template;
    }

    /**
     * @param int $custom_block_id
     *
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getBlockDescriptions($custom_block_id = 0)
    {
        if (!(int)$custom_block_id) {
            return [];
        }
        $cache_key = 'layout.block.descriptions.' . $custom_block_id;
        $output = $this->cache->get($cache_key);
        if ($output !== null) {
            // return cached blocks
            return $output;
        }

        $output = [];
        $result = $this->db->query(
            "SELECT bd.*, COALESCE(bl.status,0) as status
            FROM " . $this->db->table_name("block_descriptions") . " bd
            LEFT JOIN " . $this->db->table_name("block_layouts") . " bl
                ON bl.custom_block_id = bd.custom_block_id
            WHERE bd.custom_block_id = '" . ( int )$custom_block_id . "'"
        );
        if ($result->num_rows) {
            foreach ($result->rows as $row) {
                $output[$row['language_id']] = $row;
            }
        }
        $this->cache->put($cache_key, $output);
        return $output;
    }

}