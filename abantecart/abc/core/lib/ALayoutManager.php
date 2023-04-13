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

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\models\layout\Block;
use abc\models\layout\BlockDescription;
use abc\models\layout\BlockLayout;
use abc\models\layout\BlockTemplate;
use abc\models\layout\CustomBlock;
use abc\models\layout\Layout;
use abc\models\layout\Page;
use abc\models\layout\PageDescription;
use abc\models\layout\PagesLayout;
use abc\models\locale\Language;
use abc\models\QueryBuilder;
use abc\modules\traits\LayoutTrait;
use Exception;
use H;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * @property ALanguageManager $language
 */
class ALayoutManager
{
    use LayoutTrait;

    public $errors = 0;

    protected $pages = [];
    protected $page = [];
    protected $layouts = [];
    protected $blocks = [];
    protected $allBlocks = [];
    //Layout placeholder parent blocks present in any template
    protected $main_placeholders = [
        'header',
        'header_bottom',
        'column_left',
        'content_top',
        'content_bottom',
        'column_right',
        'footer_top',
        'footer',
    ];
    protected $templateTextId;
    protected $layoutId;
    protected $activeLayout = [];
    protected $pageId;
    protected $customBlocks = [];


    const LAYOUT_TYPE_DEFAULT = 0;
    const LAYOUT_TYPE_ACTIVE = 1;
    const LAYOUT_TYPE_DRAFT = 2;
    const LAYOUT_TYPE_TEMPLATE = 3;
    const HEADER_MAIN = 1;
    const HEADER_BOTTOM = 2;
    const LEFT_COLUMN = 3;
    const RIGHT_COLUMN = 4;
    const CONTENT_TOP = 5;
    const CONTENT_BOTTOM = 6;
    const FOOTER_TOP = 7;
    const FOOTER_MAIN = 8;
    const FIXED_POSITIONS = 8;

    /**
     *  Layout Manager Class to handle layout in the admin
     *  NOTES: Object can be constructed with specific template, page or layout id provided
     * Possible to create an object with no specifics to access layout methods.
     *
     * @param string|null $templateTextId
     * @param int|null $pageId
     * @param int|null $layoutId
     *
     * @throws AException
     */
    public function __construct(?string $templateTextId = '', ?int $pageId = 0, ?int $layoutId = 0)
    {
        if (!ABC::env('IS_ADMIN')) { // forbid for non admin calls
            throw new AException ('Error: permission denied to change page layout', AC_ERR_LOAD);
        }

        $defaultTemplate = Registry::config()->get('config_storefront_template');
        $this->templateTextId = $templateTextId ?: $defaultTemplate;

        //do check for existence of storefront template in case when $tmpl_id not set
        if (!$templateTextId) {
            //check is template an extension
            $template = $defaultTemplate;
            $dir = $template . ABC::env('DIRNAME_STORE') . ABC::env('DIRNAME_TEMPLATES') . $template;
            $enabled_extensions = Registry::extensions()->getEnabledExtensions();

            $isValid = (in_array($template, $enabled_extensions) && is_dir(ABC::env('DIR_APP_EXTENSIONS') . $dir));

            //check if this is template from core
            if (!$isValid && is_dir(ABC::env('DIR_TEMPLATES') . $template . DS . ABC::env('DIRNAME_STORE'))) {
                $isValid = true;
            }
            $this->templateTextId = $isValid ? $template : 'default';
        } else {
            $this->templateTextId = $templateTextId;
        }

        //load all pages specific to set template. No cross template page/layouts
        $this->pages = $this->getPages();
        //set current page for this object instance
        $this->setCurrentPage($pageId, $layoutId);
        $this->pageId = $this->page['page_id'];

        //preload all layouts for this page and template
        //NOTE: layout_type: 0 Default, 1 Active layout, 2 draft layout, 3 template layout
        $this->layouts = $this->getLayouts($this->templateTextId, $this->pageId);

        //locate layout for the page instance. If not specified for this instance fist active layout is used
        foreach ($this->layouts as $layout) {
            if ($layoutId) {
                if ($layout ['layout_id'] == $layoutId) {
                    $this->activeLayout = $layout;
                    break;
                }
            } else {
                if ($layout ['layout_type'] == 1) {
                    $this->activeLayout = $layout;
                    break;
                }
            }
        }

        //if not layout set, use default (layout_type=0) layout
        if (!count($this->activeLayout)) {
            $this->activeLayout = $this->getLayouts($this->templateTextId, null, 0)[0];
            if (!$this->activeLayout) {
                var_Dump($this->templateTextId, $this->pageId, 0, $this->activeLayout);
                exit;
            }
            if (!count($this->activeLayout)) {
                $messageText = 'No template layout found for page_id/controller '
                    . $this->pageId . '::' . $this->page ['controller'] . '!';
                $messageText .= ' Requested data: template: ' . $templateTextId
                    . ', page_id: ' . $pageId
                    . ', layout_id: ' . $layoutId;
                $messageText .= '  ' . H::genExecTrace('full');
                throw new AException ($messageText, AC_ERR_LOAD_LAYOUT);
            }
        }
        $this->layoutId = $this->activeLayout['layout_id'];

        ADebug::variable('Template id', $this->templateTextId);
        ADebug::variable('Page id', $this->pageId);
        ADebug::variable('Layout id', $this->layoutId);

        // Get blocks
        $this->allBlocks = $this->getAllBlocks();
        $this->blocks = $this->getAllLayoutBlocks($this->layoutId);
    }

    /**
     * Select pages on specified parameters linked to layout and template.
     * Note: returns an array of matching pages.
     *
     * @param string|null $controller
     * @param string|null $keyParameter
     * @param string|null $keyValue
     * @param string|null $templateTextId
     *
     * @return array
     */
    public function getPages(
        ?string $controller = '',
        ?string $keyParameter = '',
        ?string $keyValue = '',
        ?string $templateTextId = ''
    )
    {
        if (!$templateTextId) {
            $templateTextId = $this->templateTextId;
        }

        $languageId = Registry::language()->getContentLanguageID();
        $query = Page::select(['pages.*', 'page_descriptions.*', 'layouts.*'])
            ->leftJoin(
                'page_descriptions',
                function ($join) use ($languageId) {
                    /** @var JoinClause $join */
                    $join->on(
                        'page_descriptions.page_id',
                        '=',
                        'pages.page_id'
                    )->where(
                        'page_descriptions.language_id',
                        '=',
                        $languageId
                    );
                })
            ->leftJoin("pages_layouts", "pages_layouts.page_id", "=", "pages.page_id")
            ->leftJoin("layouts", "layouts.layout_id", "=", "pages_layouts.layout_id");

        if ($templateTextId) {
            $query->where('layouts.template_id', '=', $templateTextId);
        }
        if ($controller) {
            $query->where('pages.controller', '=', $controller);
            if ($keyParameter) {
                $query->where('pages.key_param', '=', $keyParameter);
                if ($keyValue) {
                    $query->where('pages.key_value', '=', $keyValue);
                }
            }
        }
        $pages = $query->orderBy('pages.page_id')
            ->useCache('layout')
            ->get()?->toArray();

        //process pages and tag restricted layout/pages
        //restricted layouts are the once without key_param and key_value
        foreach ($pages as &$page) {
            if ($page['layout_type'] == 2) {
                $page['name'] .= '(draft)';
            }
            if (!$page['key_param'] && !$page['key_value']) {
                $page['restricted'] = true;
            }
        }
        return $pages;
    }

    /**
     * Run logic to detect page ID and layout ID for given parameters
     * This will detect if requested page already has layout or return default otherwise.
     *
     * @param string|null $controller
     * @param string|null $keyParameter
     * @param string|null $keyValue
     *
     * @return array
     */
    public function getPageLayoutIDs(?string $controller = '', ?string $keyParameter = '', ?string $keyValue = '')
    {
        $output = [];
        if (!$controller) {
            return $output;
        }
        //check if we got most specific page/layout
        $pages = $this->getPages($controller, $keyParameter, $keyValue);
        if (!$pages) {
            $pages = $this->getPages($controller);
            if (!$pages) {
                $pages = $this->getPages('generic');
            }
        }

        if ($pages) {
            $output['page_id'] = $pages[0]['page_id'];
            $output['layout_id'] = $pages[0]['layout_id'];
        }

        return $output;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllBlocks()
    {
        $languageId = Registry::language()->getContentLanguageID();
        $db = Registry::db();
        $query = Block::select(
            [
                'blocks.*',
                'block_templates.parent_block_id',
                'block_templates.template',
                'custom_blocks.custom_block_id'
            ]
        )->selectRaw(
            "COALESCE(" . $db->table_name('block_descriptions') . ".name, "
            . $db->table_name('blocks') . ".block_txt_id) as block_name"
        )->leftJoin('block_templates', 'block_templates.block_id', '=', 'blocks.block_id')
            ->leftJoin('custom_blocks', 'custom_blocks.block_id', '=', 'blocks.block_id')
            ->leftJoin('block_descriptions',
                function ($join) use ($languageId) {
                    $join->on('block_descriptions.custom_block_id', '=', 'custom_blocks.custom_block_id')
                        ->where('block_descriptions.language_id', '=', $languageId);
                }
            )->orderBy('blocks.block_id');
        return $query->useCache('layout')->get()?->toArray();
    }

    /**
     * @param array $params
     *
     * @throws Exception
     * @deprecated
     */
    public function getBlocksList($params = [])
    {
        return Block::getBlocks($params);
    }

    /**
     * @return string
     */
    public function getTemplateId()
    {
        return $this->templateTextId;
    }

    /**
     * Returns all pages for this instance (template)
     *
     * @return array
     */
    public function getAllPages()
    {
        return $this->pages;
    }

    /**
     * @return array
     */
    public function getPageData()
    {
        return $this->page;
    }

    /**
     * @param $controller string
     *
     * @return array
     */
    public function getPageByController($controller)
    {
        foreach ($this->pages as $page) {
            if ($page['controller'] == $controller) {
                return $page;
            }
        }
        return [];
    }

    /**
     * @return array
     */
    public function getActiveLayout()
    {
        return $this->activeLayout;
    }

    /**
     * @param $block_txt_id string
     *
     * @return array
     */
    public function getLayoutBlockByTxtId($block_txt_id)
    {
        foreach ($this->blocks as $block) {
            if ($block['block_txt_id'] == $block_txt_id) {
                return $block;
            }
        }
        return [];
    }

    /**
     * @param $block_txt_id string
     *
     * @return array
     */
    public function getBlockByTxtId($block_txt_id)
    {
        foreach ($this->allBlocks as $block) {
            if ($block['block_txt_id'] == $block_txt_id) {
                return $block;
            }
        }

        return [];
    }

    /**
     * @param int $parent_instance_id
     * @param int $parent_block_id
     *
     * @return array
     * @throws Exception
     */
    public function getBlockChildren($parent_instance_id, $parent_block_id)
    {
        $blocks = [];
        foreach ($this->blocks as $block) {
            if ($block['parent_instance_id'] == $parent_instance_id) {
                //locate block template assigned based on parent block ID
                $block['template'] = $this->getBlockTemplate($block['block_id'], $parent_block_id);
                $blocks[] = $block;
            }
        }
        return $blocks;
    }

    /**
     * @return array
     */
    public function getInstalledBlocks()
    {
        $blocks = [];

        foreach ($this->allBlocks as $block) {
            // do not include main level blocks
            if (!in_array($block ['block_txt_id'], $this->main_placeholders)) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    /**
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getLayoutBlocks()
    {
        $blocks = [];

        foreach ($this->main_placeholders as $placeholder) {
            $block = $this->getLayoutBlockByTxtId($placeholder);
            if (!$block) {
                continue;
            }

            $blocks[$block['block_id']] = $block;
            $children = $this->getBlockChildren($block['instance_id'], $block['block_id']);
            //process special case of fixed location for header and footer
            if ($block['block_id'] == self::HEADER_MAIN
                || $block['block_id'] == self::FOOTER_MAIN
            ) {
                //fill in blank locations if any
                if (count($children) < self::FIXED_POSITIONS) {
                    $children = $this->buildChildrenBlocks($children, self::FIXED_POSITIONS);
                }
            }
            $blocks[$block['block_id']]['children'] = $children;
        }

        return $blocks;
    }

    /**
     * @param array $blocks
     * @param int $total_blocks
     *
     * @return array
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    protected function buildChildrenBlocks($blocks, $total_blocks)
    {
        $select_boxes = [];
        $empty_block = [
            'block_txt_id' => Registry::language()->get('text_none'),
        ];
        for ($x = 0; $x < $total_blocks; $x++) {
            $idx = $this->findBlockByPosition($blocks, ($x + 1) * 10);
            if ($idx >= 0) {
                $select_boxes[] = $blocks[$idx];
            } else {
                //put empty placeholder
                $select_boxes[] = $empty_block;
            }
        }

        return $select_boxes;
    }

    /**
     * @param array $blocks_arr
     * @param int $position
     *
     * @return int
     */
    protected function findBlockByPosition($blocks_arr, $position)
    {
        foreach ($blocks_arr as $index => $block_s) {
            if ($block_s['position'] == $position) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * @param $layout_type
     *
     * @return array
     */
    public function getLayoutByType($layout_type)
    {
        $layouts = [];
        foreach ($this->layouts as $layout) {
            if ($layout ['layout_type'] == $layout_type) {
                $layouts [] = $layout;
            }
        }

        return $layouts;
    }

    /**
     * @return array
     */
    public function getLayoutDrafts()
    {
        return $this->getLayoutByType(2);
    }

    /**
     * @return array
     */
    public function getLayoutTemplates()
    {
        return $this->getLayoutByType(3);
    }

    /**
     * @return int
     */
    public function getLayoutId()
    {
        return $this->layoutId;
    }

    /**
     * Process post data and prepare for layout to save
     *
     * @param array $post
     *
     * @return array
     */
    public function prepareInput($post)
    {
        if (empty($post)) {
            return null;
        }
        $data = [];
        $section = $post['section'];
        $block = $post['block'];
        $parentBlock = $post['parentBlock'];
        $blockStatus = $post['blockStatus'];

        foreach ($section as $k => $item) {
            $section[$k]['children'] = [];
        }

        foreach ($block as $k => $block_id) {
            $parent = $parentBlock[$k];
            $status = $blockStatus[$k];

            $section[$parent]['children'][] = [
                'block_id' => $block_id,
                'status'   => $status,
            ];
        }

        $data['layout_name'] = $post['layout_name'];
        $data['blocks'] = $section;

        return $data;
    }

    /**
     * Save Page/Layout and Layout Blocks
     *
     * @param $data array
     *
     * @return bool
     * @throws AException|Exception
     */
    public function savePageLayout($data)
    {
        $page = $this->page;
        $layout = $this->activeLayout;
        $new_layout = false;

        if ((!$layout['layout_type'] || isset($data['new']))
            && ($page['controller'] != 'generic' || $data['controller'])) {
            $layout['layout_name'] = $data ['layout_name'];
            $layout['layout_type'] = self::LAYOUT_TYPE_ACTIVE;

            $this->layoutId = $this->saveLayout($layout);
            $new_layout = true;

            PagesLayout::create(
                [
                    'layout_id' => $this->layoutId,
                    'page_id'   => $this->pageId
                ]
            );
        }

        foreach ($this->main_placeholders as $placeholder) {
            $block = $this->getLayoutBlockByTxtId($placeholder);
            if (!empty ($data ['blocks'])) {
                list($block ['block_id'], $block ['custom_block_id']) = explode("_", $block ['block_id']);
                if (!empty ($data ['blocks'] [$block ['block_id']])) {
                    $block = array_merge($block, $data ['blocks'] [$block ['block_id']]);
                    if ($new_layout) {
                        $block ['layout_id'] = $this->layoutId;
                        $instance_id = $this->saveLayoutBlocks($block);
                    } else {
                        $instance_id = $this->saveLayoutBlocks($block, $block ['instance_id']);
                    }

                    if (isset ($data ['blocks'] [$block ['block_id']] ['children'])) {
                        $this->deleteLayoutBlocks($this->layoutId, $instance_id);

                        foreach ($data ['blocks'] [$block ['block_id']] ['children'] as $key => $block_data) {
                            $child = [];
                            if (!empty ($block_data)) {
                                $child ['layout_id'] = $this->layoutId;
                                list($child['block_id'], $child['custom_block_id']) =
                                    explode("_", $block_data['block_id']);
                                $child['parent_instance_id'] = $instance_id;
                                //NOTE: Blocks positions are saved in 10th increment starting from 10
                                $child['position'] = ($key + 1) * 10;
                                $child['status'] = $block_data['status'];
                                if ($child ['block_id']) {
                                    $this->saveLayoutBlocks($child);
                                }
                            }
                        }
                    }
                }
            }
        }

        Registry::cache()->flush('layout');

        return true;
    }

    /**
     * Save Page/Layout and Layout Blocks Draft
     *
     * @param $data array
     *
     * @return int
     * @throws Exception
     */
    public function savePageLayoutAsDraft($data)
    {

        $layout = $this->activeLayout;
        $layout ['layout_type'] = self::LAYOUT_TYPE_DRAFT;

        $new_layout_id = $this->saveLayout($layout);

        PagesLayout::create(
            [
                'layout_id' => $new_layout_id,
                'page_id'   => $this->pageId
            ]
        );

        foreach ($this->main_placeholders as $placeholder) {
            $block = $this->getLayoutBlockByTxtId($placeholder);
            if (!$block) {
                continue;
            }

            list($block['block_id'], $block['custom_block_id']) = explode("_", $block['block_id']);
            if (!empty($data['blocks'][$block['block_id']])) {
                $block = array_merge($block, $data['blocks'][$block ['block_id']]);
                $block['layout_id'] = $new_layout_id;
                $instance_id = $this->saveLayoutBlocks($block);
                if (isset($data['blocks'][$block['block_id']]['children'])) {
                    foreach ($data['blocks'][$block['block_id']]['children'] as $key => $block_data) {
                        $child = [];
                        if (!empty ($block_data)) {
                            $child['layout_id'] = $new_layout_id;
                            list($child['block_id'], $child['custom_block_id']) = explode("_", $block_data['block_id']);
                            $child['parent_instance_id'] = $instance_id;
                            $child['position'] = ($key + 1) * 10;
                            $child['status'] = $block_data['status'];
                            $this->saveLayoutBlocks($child);
                        }
                    }
                }
            }
        }

        Registry::cache()->flush('layout');

        return $new_layout_id;
    }

    /**
     * Function to clone layout linked to the page
     *
     * @param int $srcLayoutId
     * @param int|null $dstLayoutId
     * @param string|null $layoutName
     *
     * @return bool
     * @throws AException
     */
    public function clonePageLayout($srcLayoutId, ?int $dstLayoutId, ?string $layoutName)
    {
        if (!$srcLayoutId) {
            return false;
        }

        $layout = $this->activeLayout;

        //this is a new layout
        if (!$dstLayoutId) {
            if ($layoutName) {
                $layout ['layout_name'] = $layoutName;
            }
            $layout ['layout_type'] = 1;
            $this->layoutId = $this->saveLayout($layout);
            $dstLayoutId = $this->layoutId;
            PagesLayout::create(
                [
                    'layout_id' => $this->layoutId,
                    'page_id'   => $this->pageId
                ]
            );
        } else {
            #delete existing layout data if provided cannot delete
            # based on $this->layout_id (done on purpose for confirmation)
            $this->deleteAllLayoutBlocks($dstLayoutId);
        }
        #clone blocks from source layout
        $this->cloneLayoutBlocks($srcLayoutId, $dstLayoutId);
        Registry::cache()->flush('layout');
        return true;
    }

    /**
     * Function to delete page and layout linked to the page
     *
     * @param int $page_id
     * @param int $layout_id
     *
     * @return bool
     * @throws Exception
     */
    public function deletePageLayoutByID($page_id, $layout_id)
    {
        if (!$page_id || !$layout_id) {
            return false;
        }
        Page::find($page_id)?->delete();
        Layout::find($layout_id)?->delete();
        Registry::cache()->flush('layout');
        return true;
    }

    /**
     * Function to delete page and layout linked to the page
     *
     * @param string $controller
     * @param string $key_param
     * @param string $key_value
     *
     * @return bool
     * @throws AException|Exception
     */
    public function deletePageLayout(string $controller, string $key_param, string $key_value)
    {
        if (!$controller || !$key_param || !$key_value) {
            return false;
        }
        $pages = $this->getPages($controller, $key_param, $key_value);
        if ($pages) {
            foreach ($pages as $page) {
                $this->deletePageLayoutByID($page['page_id'], $page['layout_id']);
            }
        }

        return true;
    }

    /**
     * Function to delete all pages and layouts linked to the page
     *
     * @param string $controller , $key_param, $key_value (all required)
     * @param string $key_param
     * @param string $key_value
     *
     * @return bool
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function deleteAllPagesLayouts(string $controller, string $key_param, string $key_value)
    {
        if (!$controller || !$key_param || !$key_value) {
            return false;
        }
        $templates = [];
        $directories = glob(ABC::env('DIR_TEMPLATES') . '*' . DS . ABC::env('DIRNAME_STORE'), GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $templates[] = basename(dirname($directory));
        }
        $enabled_templates = Registry::extensions()->getExtensionsList(
            [
                'filter' => 'template',
                'status' => 1,
            ]
        );
        foreach ($enabled_templates->rows as $template) {
            $templates[] = $template['key'];
        }
        foreach ($templates as $templateId) {
            $pages = $this->getPages($controller, $key_param, $key_value, $templateId);
            if ($pages) {
                foreach ($pages as $page) {
                    $this->deletePageLayoutByID($page['page_id'], $page['layout_id']);
                }
            }
        }

        return true;
    }

    /**
     * @param array $data
     * @param int|null $instance_id
     *
     * @return int
     */
    public function saveLayoutBlocks(array $data, ?int $instance_id = null)
    {
        $parent_instance_id = (int)$data ['parent_instance_id'] ?: null;
        $custom_block_id = (int)$data ['custom_block_id'] ?: null;

        $layoutInstance = BlockLayout::updateOrCreate(
            [
                'instance_id' => $instance_id ?: null
            ],
            [
                'layout_id'          => $data ['layout_id'],
                'block_id'           => $data ['block_id'],
                'custom_block_id'    => $custom_block_id,
                'parent_instance_id' => $parent_instance_id,
                'position'           => (int)$data ['position'],
                'status'             => (int)$data ['status']
            ]
        );
        $instance_id = $layoutInstance->instance_id;
        Registry::cache()->flush('layout');
        return $instance_id;
    }

    /**
     * Delete blocks from the layout based on instance ID
     *
     * @param int $layout_id
     * @param int|null $parent_instance_id
     *
     * @return void
     */
    public function deleteLayoutBlocks(int $layout_id, ?int $parent_instance_id)
    {
        BlockLayout::where('layout_id', '=', $layout_id)
            ->where('parent_instance_id', '=', $parent_instance_id)
            ->delete();
        Registry::cache()->flush('layout');
    }

    /**
     * Delete All blocks from the layout
     *
     * @param int $layout_id
     * @throws AException
     */
    public function deleteAllLayoutBlocks(int $layout_id)
    {
        if (!$layout_id) {
            throw new AException (
                'Error: Cannot to delete layout blocks. Empty layout ID!',
                AC_ERR_LOAD
            );
        }

        BlockLayout::where('layout_id', '=', $layout_id)?->delete();
        Registry::cache()->flush('layout');
    }

    /**
     * @param array $data
     * @param int|null $layoutId
     *
     * @return int
     * @throws Exception
     */
    public function saveLayout(array $data, ?int $layoutId = null)
    {
        if (!$data['template_id'] && !$data['layout_name'] && !$data['layout_type']) {
            throw new AException (
                'Error: Empty layout data for saving!',
                AC_ERR_LOAD
            );
        }

        $layout = Layout::updateOrCreate(
            [
                'layout_id' => $layoutId ?: null
            ],
            [
                'template_id' => $data['template_id'],
                'layout_name' => $data['layout_name'],
                'layout_type' => (int)$data['layout_type']
            ]
        );

        Registry::cache()->flush('layout');
        return $layout->layout_id;
    }

    /**
     * @param int $blockId
     * @return QueryBuilder|array|Model|object|null
     */
    public function getBlockInfo(int $blockId)
    {
        if (!$blockId) {
            return [];
        }

        //Note: Cannot restrict select block based on page_id and layout_id.
        // Some pages, might use default layout and have no pages_layouts entry
        // Use OR to select all options and order by layout_id
        return Block::getBlockInfo($blockId);
    }

    /**
     * @param int $blockId
     *
     * @return array
     * @throws Exception
     */
    public function getBlockTemplates(int $blockId)
    {
        if (!$blockId) {
            return [];
        }
        return BlockTemplate::where('block_id', '=', $blockId)
            ->useCache('layout')
            ->get()?->toArray();
    }

    /**
     * @param int $block_id
     * @param int|null $parent_block_id
     *
     * @return string
     */
    public function getBlockTemplate(int $block_id, ?int $parent_block_id = null)
    {
        if (!$block_id) {
            return '';
        }
        return (string)BlockTemplate::where('block_id', '=', $block_id)
            ->whereIn('parent_block_id', [$parent_block_id, null, 0])
            ->useCache('layout')
            ->first()?->template;
    }

    /**
     * @param array $data
     * @param int|null $pageId
     *
     * @return int
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function savePage(array $data, ?int $pageId = null)
    {
        $page = Page::updateOrCreate(
            [
                'page_id' => $pageId ?: null
            ],
            [
                'parent_page_id' => (int)$data ['parent_page_id'] ?: null,
                'controller'     => $data['controller'],
                'key_param'      => $data['key_param'],
                'key_value'      => $data['key_value']
            ]
        );
        $pageId = $page->page_id;
        PageDescription::where('page_id', '=', $pageId)->delete();

        // page description
        if ($data['page_descriptions']) {
            $language = Registry::language();
            foreach ($data['page_descriptions'] as $language_id => $description) {
                if (!(int)$language_id) {
                    continue;
                }

                $language->replaceDescriptions('page_descriptions',
                    ['page_id' => (int)$pageId],
                    [
                        (int)$language_id => [
                            'name'        => $description['name'],
                            'title'       => $description['title'],
                            'seo_url'     => $description['seo_url'],
                            'keywords'    => $description['keywords'],
                            'description' => $description['description'],
                            'content'     => $description['content'],
                        ],
                    ]
                );
            }
        }

        Registry::cache()->flush('layout');
        return $pageId;
    }

    /**
     * @param array $data
     * @param int|null $blockId
     *
     * @return int
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function saveBlock(array $data, ?int $blockId = null)
    {
        if (!$blockId) {
            $block = $this->getBlockByTxtId($data['block_txt_id']);
            $blockId = $block['block_id'];
        }

        $block = Block::updateOrCreate(
            [
                'block_id' => $blockId ?: null
            ],
            [
                'block_txt_id' => $data ['block_txt_id'],
                'controller'   => $data ['controller']
            ]
        );
        $blockId = $block->block_id;

        if (isset($data['templates'])) {
            $this->deleteBlockTemplates($blockId);
            foreach ((array)$data['templates'] as $tmpl) {
                if (!isset($tmpl['parent_block_id']) && $tmpl ['parent_block_txt_id']) {
                    $parent = $this->getBlockByTxtId($tmpl ['parent_block_txt_id']);
                    $tmpl['parent_block_id'] = $parent['block_id'];
                }
                BlockTemplate::create(
                    [
                        'block_id'        => $blockId,
                        'parent_block_id' => (int)$tmpl ['parent_block_id'] ?: null,
                        'template'        => $tmpl['template']
                    ]
                );
            }
        }

        // save block descriptions bypass
        $blockDescriptions = !isset($data['block_descriptions']) && $data['block_description']
            ? [$data['block_description']]
            : $data['block_descriptions'];
        if ($blockDescriptions) {
            $defaultLanguageId = Registry::language()->getContentLanguageID();
            foreach ($blockDescriptions as $blockDesc) {
                if (!isset($blockDesc['language_id']) && $blockDesc ['language_name']) {
                    $blockDesc['language_id'] = $this->_getLanguageIdByName(
                        $blockDesc['language_name']
                    );
                }
                if (!$blockDesc['language_id']) {
                    $blockDesc['language_id'] = $defaultLanguageId;
                }
                $this->saveBlockDescription($blockId, $blockDesc['block_description_id'], $blockDesc);
            }
        }
        Registry::cache()->flush('layout');
        return $blockId;
    }

    /**
     * @param int $status
     * @param int|null $blockId
     * @param int|null $customBlockId
     * @param int|null $layoutId
     *
     * @return bool
     * @throws Exception
     */
    public function editBlockStatus(
        int  $status,
        ?int $blockId = null,
        ?int $customBlockId = null,
        ?int $layoutId = null
    )
    {
        if (!$customBlockId && !$blockId) {
            Registry::log()->error(__FUNCTION__ . ": Cannot update block status! Block ID and Custom Block ID are empty.");
            return false;
        }

        $query = BlockLayout::query();

        if ($blockId && !$customBlockId) {
            if (CustomBlock::where('block_id', '=', $blockId)->count()) {
                Registry::log()->error(
                    __FUNCTION__ . ": Cannot to change status for block_id: "
                    . $blockId . ". It is base for custom blocks!"
                );
                return false;
            }
            $query->where('block_id', '=', $blockId);
        }

        if ($customBlockId) {
            $query->where('custom_block_id', '=', $customBlockId);
        }
        if ($layoutId) {
            $query->where('layout_id', '=', $layoutId);
        }

        $query->update(['status' => $status]);
        Registry::cache()->flush('layout');
        return true;
    }

    /**
     * @param int|null $blockId
     * @param int|null $customBlockId
     * @param array $description
     *
     * @return bool|int
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function saveBlockDescription(?int $blockId = null, ?int $customBlockId = null, array $description = [])
    {
        if (!$customBlockId && !$blockId) {
            Registry::log()->error(__FUNCTION__ . ": Cannot update block description! Block ID and Custom Block ID are empty.");
            return false;
        }
        if (!$description) {
            Registry::log()->error(__FUNCTION__ . ": Cannot update block description! Description array is empty.");
            return false;
        }
        $language = Registry::language();

        if (!$description['language_id']) {
            $description['language_id'] = $language->getContentLanguageID();
            $this->errors = 'Warning: block description does not provide language. '
                . 'Current language id ' . $description['language_id'] . ' is used!';
            Registry::log()->error($this->errors);
        }

        if ($blockId && !$customBlockId) {
            $cb = CustomBlock::create(['block_id' => $blockId]);
            $customBlockId = $cb->custom_block_id;
        }

        $update = [];
        foreach ((new BlockDescription())->getFillable() as $attrName) {
            if (isset($description[$attrName])) {
                $update[$attrName] = $description[$attrName];
            }
        }
        if ($update) {
            $language->replaceDescriptions(
                'block_descriptions',
                ['custom_block_id' => (int)$customBlockId],
                [(int)$description['language_id'] => $update]);
        }
        Registry::cache()->flush('layout');
        return $customBlockId;
    }

    /**
     * @param int $customBlockId
     *
     * @return array
     * @throws Exception
     */
    public function getBlockDescriptions(int $customBlockId)
    {
        $result = BlockDescription::select('block_descriptions.*')
            ->selectRaw(
                "(SELECT MAX(status) 
                 FROM " . Registry::db()->table_name('block_layouts') . " 
                 WHERE custom_block_id=" . $customBlockId . ") as status")
            ->where('block_descriptions.custom_block_id', '=', $customBlockId)
            ->useCache('layout')->get();
        $output = [];
        foreach ((array)$result?->toArray() as $row) {
            $output[$row['language_id']] = $row;
        }
        return $output;
    }

    /**
     * @param int $customBlockId
     * @param int $languageId
     *
     * @return string
     * @throws Exception
     */
    public function getCustomBlockName(int $customBlockId, int $languageId)
    {
        if (!$customBlockId) {
            Registry::log()->error(__FUNCTION__ . ": Cannot get block name! Custom Block ID is empty.");
            return false;
        }
        $info = $this->getBlockDescriptions($customBlockId);
        return $info[$languageId]
            ? $info[$languageId]['name']
            //take first
            : (reset($info)['name'] ?: '');
    }

    /**
     * get list of layouts that block used
     *
     * @param int $blockId
     * @param int|null $customBlockId
     *
     * @return array|Collection
     */
    public function getBlocksLayouts(int $blockId, ?int $customBlockId = 0)
    {
        $customBlockId = (int)$customBlockId;
        if (!$blockId && !$customBlockId) {
            Registry::log()->error(__FUNCTION__ . ": Cannot get block layouts! Block ID and Custom Block ID are empty.");
            return [];
        }
        return Layout::select(['layouts.*', 'pages_layouts.page_id'])
            ->join(
                'block_layouts',
                function ($join) use ($blockId, $customBlockId) {
                    $join->on('block_layouts.layout_id', '=', 'layouts.layout_id');
                    if ($customBlockId) {
                        $join->where('block_layouts.custom_block_id', '=', $customBlockId);
                    } else {
                        $join->where('block_layouts.block_id', '=', $blockId);
                    }
                }
            )
            ->leftJoin('pages_layouts', 'pages_layouts.layout_id', '=', 'layouts.layout_id')
            ->distinct()
            ->useCache('layout')->get();
    }

    /**
     * @param int $customBlockId
     *
     * @return bool
     * @throws Exception
     */
    public function deleteCustomBlock($customBlockId)
    {
        if (!(int)$customBlockId
            || BlockLayout::where('custom_block_id', '=', $customBlockId)->count()
        ) {
            return false;
        }
        $result = CustomBlock::find($customBlockId)?->delete();
        Registry::cache()->flush('layout');
        return $result;
    }

    /**
     * @param int $blockId
     * @param int|null $parentBlockId
     *
     * @throws AException
     */
    public function deleteBlockTemplates(int $blockId, ?int $parentBlockId = 0)
    {
        if (!$blockId) {
            throw new AException (
                'Error: Cannot to delete block template, block_id "' . $blockId . '" does not exists.',
                AC_ERR_LOAD
            );
        }

        $query = BlockTemplate::where('block_id', '=', $blockId);
        if ($parentBlockId) {
            $query->where('parent_block_id', '=', $parentBlockId);
        }
        $query->delete();
        Registry::cache()->flush('layout');
    }

    /**
     * @param string|null $blockTextId
     * @param int|null $blockId
     *
     * @throws AException
     */
    public function deleteBlock(?string $blockTextId = '', ?int $blockId = 0)
    {
        if (!$blockId && !$blockTextId) {
            throw new AException (
                'Cannot delete block! Block ID and Block Text ID are empty.',
                AC_ERR_LOAD
            );
        }

        if (!$blockId) {
            $block = $this->getBlockByTxtId($blockTextId);
            $blockId = $block['block_id'];
        }

        Block::find($blockId)?->delete();
        Registry::cache()->flush('layout');
    }

    /**
     * Clone Template layouts to new template
     *
     * @param $newTemplateTextId
     *
     * @return bool
     * @throws Exception
     */
    public function cloneTemplateLayouts($newTemplateTextId)
    {
        if (!$newTemplateTextId) {
            throw new AException (
                'Cannot Clone Layout! Template name is empty.',
                AC_ERR_LOAD
            );
        }

        $layouts = Layout::where('template_id', '=', $this->templateTextId)
            ->useCache('layout')
            ->get();

        foreach ($layouts as $layout) {
            //clone layout
            $newLayout = [
                'template_id' => $newTemplateTextId,
                'layout_name' => $layout['layout_name'],
                'layout_type' => $layout['layout_type'],
            ];
            $layout_id = $this->saveLayout($newLayout);
            $pages = PagesLayout::where('layout_id', '=', $layout->layout_id)
                ->useCache('layout')
                ->get();
            foreach ($pages as $page) {
                PagesLayout::create(
                    [
                        'layout_id' => $layout_id,
                        'page_id'   => $page->page_id
                    ]
                );
            }

            //clone blocks
            if (!$this->cloneLayoutBlocks($layout->layout_id, $layout_id)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Clone layout blocks to new layout ( update block instances)
     *
     * @param int $sourceLayoutId
     * @param int $newLayoutId
     *
     * @return bool
     * @throws AException
     */
    public function cloneLayoutBlocks(int $sourceLayoutId, int $newLayoutId)
    {
        if (!$sourceLayoutId || !$newLayoutId) {
            throw new AException (
                'Cannot Clone Layout Blocks! Both Template IDs are empty.',
                AC_ERR_LOAD
            );
        }

        $blocks = $this->getAllLayoutBlocks($sourceLayoutId);
        $instance_map = [];
        // insert top level block first
        foreach ($blocks as $block) {
            if ($block['parent_instance_id'] == 0) {
                $block['layout_id'] = $newLayoutId;
                $b_id = $this->saveLayoutBlocks($block);
                $instance_map[$block['instance_id']] = $b_id;
            }
        }
        // insert child blocks
        foreach ($blocks as $block) {
            if ($block['parent_instance_id']) {
                $block['layout_id'] = $newLayoutId;
                $block['parent_instance_id'] = $instance_map[$block['parent_instance_id']];
                $this->saveLayoutBlocks($block);
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function deleteTemplateLayouts()
    {
        //TODO: check if all layouts was deleted with relations!
        Layout::where('template_id', '=', $this->templateTextId)
            ?->delete();
        Registry::cache()->flush('layout');
    }

    /**
     * loadXML() Load layout from XML file or XML String
     *
     * @param array $data
     *
     * @return bool
     * @throws ReflectionException
     * @throws AException|InvalidArgumentException
     */
    public function loadXML($data)
    {
        // Input possible with XML string, File or both.
        // We process both one at a time. XML string processed first
        if ($data ['xml']) {
            $xml_obj = simplexml_load_string($data ['xml']);
            if (!$xml_obj) {
                $err = "Failed loading XML data string";
                foreach (libxml_get_errors() as $error) {
                    $err .= "  " . $error->message;
                }
                $error = new AError ($err);
                $error->toLog()->toDebug();
            } else {
                $this->processXML($xml_obj);
            }
        }

        if (isset ($data ['file']) && is_file($data ['file'])) {
            $xml_obj = simplexml_load_file($data ['file']);
            if (!$xml_obj) {
                $err = "Failed loading XML file " . $data ['file'];
                foreach (libxml_get_errors() as $error) {
                    $err .= "  " . $error->message;
                }
                $error = new AError ($err);
                $error->toLog()->toDebug();
            } else {
                $this->processXML($xml_obj);
            }
        }

        return true;
    }

    /**
     * @param int|null $page_id
     * @param int|null $layout_id
     *
     */
    protected function setCurrentPage(?int $page_id = 0, ?int $layout_id = 0)
    {
        //find page used for this instance. If page_id is not specified for the instance, generic page/layout is used.
        if ($page_id && $layout_id) {
            foreach ($this->pages as $page) {
                if ($page['page_id'] == $page_id && $page['layout_id'] == $layout_id) {
                    $this->page = $page;
                    return;
                }
            }
        } elseif ($page_id) {
            //we have page not related to any layout yet. need to pull differently
            $language_id = Registry::language()->getContentLanguageID();
            $page = Page::select(['page_descriptions.*', 'pages.*'])
                ->where('pages.page_id', '=', $page_id)
                ->leftJoin(
                    'page_descriptions',
                    function ($join) use ($language_id) {
                        $join->on(
                            'page_descriptions.page_id',
                            '=',
                            'pages.page_id'
                        )->where('page_descriptions.language_id', '=', $language_id);
                    })
                ->useCache('layout')->first()?->toArray();
            $this->pages[] = $page;
            $this->page = $page;
        } else {
            //set generic layout
            foreach ($this->pages as $page) {
                if ($page['controller'] == 'generic') {
                    $this->page = $page;
                    return;
                }
            }
        }
    }

    /**
     * @param object $xml_obj
     *
     * @return bool
     * @throws ReflectionException
     * @throws AException|InvalidArgumentException
     */
    protected function processXML($xml_obj)
    {
        $template_layouts = $xml_obj->xpath('/template_layouts/layout');
        if (empty($template_layouts)) {
            return false;
        }
        //process each layout
        foreach ($template_layouts as $layout) {

            /* Determine an action tag in all patent elements. Action can be insert, update and delete
               Default action (if not provided) is update
               ->>> action = insert
                    Before loading the layout, determine if same layout exists with same name,
                    template and type combination.
                    If does exists, return and log error
               ->>> action = update (default)
                    Before loading the layout, determine if same layout exists with same name,
                    template and type combination.
                    If does exists, write new settings over existing
               ->>> action = delete
                    Delete the element provided from database and delete relationships to
                    other elements linked to current one

                NOTE: Parent level delete action is cascaded to all children elements

                TODO: Need to use transaction sql here to prevent partial load or partial delete in case of error
            */

            //check if layout with same name exists
            $layout_id = (int)Layout::where('layout_name', '=', $layout->name)
                ->where('template_id', '=', $layout->template_id)
                ->useCache('layout')
                ->first()?->layout_id;

            if (!$layout_id && in_array($layout->action, ["", null, "update"])) {
                $layout->action = 'insert';
            }
            if ($layout_id && $layout->action == 'insert') {
                $layout->action = 'update';
            }
            //Delete layout if requested and all it's part included
            if ($layout->action == "delete") {
                if ($layout_id) {
                    Layout::find($layout_id)?->delete();

                    //Delete Blocks if we are allowed
                    foreach ($layout->blocks->block as $block) {
                        if (!$block->block_txt_id) {
                            continue;
                        }
                        //is this custom block?
                        if ($block->custom_block_txt_id) {
                            $this->_deleteCustomBlock($block, $layout_id);
                        } else {
                            $this->_deleteBlock($block, $layout_id);
                        }
                    }
                }

            } elseif ($layout->action == 'insert') {

                if ($layout_id) {
                    $error_text = 'Layout XML load error: '
                        . 'Cannot add new layout (layout name: "' . $layout->name . '") '
                        . 'into database because it already exists.';
                    $error = new AError ($error_text);
                    $error->toLog()->toDebug();
                    $this->errors = 1;
                    continue;
                }

                // check layout type
                $layout_type = $this->_getIntLayoutTypeByText((string)$layout->type);
                $layoutObj = Layout::create(
                    [
                        'template_id' => $layout->template_id,
                        'layout_name' => $layout->name,
                        'layout_type' => $layout_type
                    ]
                );
                $layout_id = $layoutObj->layout_id;

                // write pages section
                if ($layout->pages->page) {
                    foreach ($layout->pages->page as $page) {
                        $this->_processPage($layout_id, $page);
                    }
                }

            } else { // layout update
                if (!$layout_id) {
                    $error_text = 'Layout XML load error: Cannot update layout (layout name: "' . $layout->name
                        . '") because it not exists.';
                    $error = new AError ($error_text);
                    $error->toLog()->toDebug();
                    $this->errors = 1;
                    continue;
                }

                // check layout type
                $layout_type = $this->_getIntLayoutTypeByText((string)$layout->type);
                Layout::find($layout_id)->update(
                    [
                        'template_id' => $layout->template_id,
                        'layout_name' => $layout->name,
                        'layout_type' => $layout_type
                    ]
                );

                // write pages section
                if ($layout->pages->page) {
                    foreach ($layout->pages->page as $page) {
                        $this->_processPage($layout_id, $page);
                    }
                }
                //end layout manipulation
            }

            // block manipulation
            if ($layout->blocks->block) {
                foreach ($layout->blocks->block as $block) {
                    if (!$block->block_txt_id) {
                        $error_text = 'Error: cannot process block because block_txt_id is empty.';
                        $error = new AError ($error_text);
                        $error->toLog()->toDebug();
                        $this->errors = 1;
                        continue;
                    }
                    $layout->layout_id = $layout_id;
                    //start recursion on all blocks
                    $this->_processBlock($layout, $block);
                }
            }
        } //end of layout manipulation

        return true;
    }

    /**
     * @param int $layoutId
     * @param object $page
     *
     * @return bool
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function _processPage(int $layoutId, object $page)
    {
        $page = Page::firstOrCreate(
            [
                'controller' => $page->controller,
                'key_param'  => $page->key_param,
                'key_value'  => $page->key_value
            ],
            [
                'controller' => $page->controller,
                'key_param'  => $page->key_param,
                'key_value'  => $page->key_value
            ]
        );
        $pageId = $page->page_id;
        if ($pageId) {
            //create if not exists
            PagesLayout::firstOrCreate(
                [
                    'page_id'   => $pageId,
                    'layout_id' => $layoutId
                ],
                [
                    'page_id'   => $pageId,
                    'layout_id' => $layoutId
                ]
            );
        }

        if ($page->page_descriptions->page_description) {
            $language = Registry::language();
            foreach ($page->page_descriptions->page_description as $pageDescription) {
                $pageDescription->language = mb_strtolower($pageDescription->language, ABC::env('APP_CHARSET'));
                $languageId = $this->_getLanguageIdByName(
                    $pageDescription->language
                );

                //if loading language does not exist or installed, skip
                if ($languageId) {
                    $language->replaceDescriptions(
                        'page_descriptions',
                        ['page_id' => (int)$pageId],
                        [
                            $languageId => [
                                'name'        => $pageDescription->name,
                                'title'       => $pageDescription->title,
                                'seo_url'     => $pageDescription->seo_url,
                                'keywords'    => $pageDescription->keywords,
                                'description' => $pageDescription->description,
                                'content'     => $pageDescription->content,
                            ],
                        ]
                    );
                }
            }
        }

        return true;
    }

    /**
     * @param object $layout
     * @param object $block
     * @param int|null $parentInstanceId
     *
     * @return bool
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function _processBlock(object $layout, object $block, ?int $parentInstanceId = null)
    {
        $instanceId = null;
        $layoutId = (int)$layout->layout_id;
        $layoutName = $layout->name;

        if ((string)$block->type) {
            $this->_processCustomBlock($layoutId, $block, $parentInstanceId);
            return true;
        } /**
         * @deprecated
         * TODO : need to delete processing of tags <kind> from layout manager in the future
         */
        elseif ((string)$block->kind == 'custom') {
            $this->_processCustomBlock($layoutId, $block, $parentInstanceId);
            return true;
        }

        $restricted = true;
        if (!in_array($block->block_txt_id, $this->main_placeholders)) {
            $restricted = false;
        }
        //NOTE $restricted blocks can only be linked to layout. Can not be deleted or updated

        //try to get block_id to see if it exists
        $blockInfo = $this->getBlockByTxtId($block->block_txt_id);
        $block_id = (int)$blockInfo['block_id'];

        $action = (string)$block->action;
        if (!$block_id && (empty($action) || $action == "update")) {
            //if block does not exist, we need to insert new one
            $action = 'insert';
        }

        if ($block_id && $action == 'delete') {
            //try to delete the block if exists
            $this->_deleteBlock($block, $layoutId);
        } else {
            if ($action == 'insert') {
                //If block exists with same block_txt_id, log error and continue
                if ($block_id) {
                    $error_text = 'Layout (' . $layoutName . ') XML error: '
                        . 'Cannot insert block (block_txt_id: "' . $block->block_txt_id . '"). Block already exists!';
                    $error = new AError ($error_text);
                    $error->toLog()->toDebug();
                    $this->errors = 1;
                } else {
                    //if block does not exist - insert and get a new block_id
                    if (!$block->controller) {
                        $error_text = 'Layout (' . $layoutName . ') XML error: '
                            . 'Missing controller for new block (block_txt_id: "' . $block->block_txt_id
                            . '"). This block might not function properly!';
                        $error = new AError ($error_text);
                        $error->toLog()->toDebug();
                        $this->errors = 1;
                    }

                    $blockObj = Block::create(
                        [
                            'block_txt_id' => $block->block_txt_id,
                            'controller'   => $block->controller
                        ]
                    );
                    $block_id = $blockObj->block_id;

                    $position = (int)$block->position;
                    //if parent block exists add positioning
                    if ($parentInstanceId && !$position) {
                        $position = BlockLayout::where('parent_instance_id', '=', $parentInstanceId)
                                ->max('position') + 10;
                    }
                    $position = $position ?: 10;
                    $layoutInstance = BlockLayout::create(
                        [
                            'layout_id'          => $layoutId,
                            'block_id'           => $block_id,
                            'parent_instance_id' => $parentInstanceId,
                            'position'           => (int)$position,
                            'status'             => 1
                        ]
                    );
                    $instanceId = $layoutInstance->instance_id;

                    //insert new block details
                    if ($block->block_descriptions->block_description) {
                        $language = Registry::language();
                        foreach ($block->block_descriptions->block_description as $block_description) {
                            $language_id = $this->_getLanguageIdByName((string)$block_description->language);
                            //if loading language does not exist or installed, skip
                            if ($language_id) {
                                $language->replaceDescriptions('block_descriptions',
                                    [
                                        'instance_id' => $instanceId,
                                        'block_id'    => $block_id,
                                    ],
                                    [
                                        $language_id => [
                                            'name'        => (string)$block_description->name,
                                            'title'       => (string)$block_description->title,
                                            'description' => (string)$block_description->description,
                                            'content'     => (string)$block_description->content,
                                        ],
                                    ]);
                            }
                        }
                    }
                    //insert new block template
                    //Ideally, block needs to have a template set, but template can be set in the controller for the block.
                    if ($block->templates->template) {
                        $inserts = [];
                        foreach ($block->templates->template as $block_template) {
                            // parent block_id by parent_name
                            $parentBlockInfo = $this->getBlockByTxtId($block_template->parent_block);
                            $parent_block_id = $parentBlockInfo['block_id'];
                            $inserts[] = [
                                'block_id'        => $block_id,
                                'parent_block_id' => $parent_block_id,
                                'template'        => $block_template->template_name
                            ];
                        }
                        if ($inserts) {
                            BlockTemplate::insert($inserts);
                        }
                    }
                }

            } else {
                //other update action
                if ($block_id) {
                    //update non-restricted blocks and blocks for current template only.
                    //this will update blocks that present only in this template
                    $total = BlockLayout::where('block_layouts.block_id', '=', $block_id)
                        ->leftJoin('layouts', 'layouts.layout_id', '=', 'block_layouts.layout_id')
                        ->where('layouts.template_id', '<>', $layout->template_id)
                        ->count();

                    if ($total == 0 && !$restricted) {
                        Block::find($block_id)->update(['controller' => (string)$block->controller]);

                        // insert block's info
                        if ($block->block_descriptions->block_description) {
                            $language = Registry::language();
                            foreach ($block->block_descriptions->block_description as $block_description) {
                                $language_id = $this->_getLanguageIdByName((string)$block_description->language);
                                //if loading language does not exist or installed, log error on update
                                if (!$language_id) {
                                    $error = "ALayout_manager Error. Unknown language for block descriptions.'."
                                        . "(Block_id=" . $block_id . ", name=" . $block_description->name . ", "
                                        . "title=" . $block_description->title . ", "
                                        . "description=" . $block_description->description . ", "
                                        . "content=" . $block_description->content . ", "
                                        . ")";
                                    Registry::log()->error($error);
                                    Registry::messages()->saveError('layout import error', $error);
                                    continue;
                                }
                                $language->replaceDescriptions(
                                    'block_descriptions',
                                    ['block_id' => $block_id],
                                    [
                                        $language_id => [
                                            'name'        => (string)$block_description->name,
                                            'title'       => (string)$block_description->title,
                                            'description' => (string)$block_description->description,
                                            'content'     => (string)$block_description->content,
                                        ],
                                    ]
                                );
                            }
                        }
                        if ($block->templates->template) {
                            foreach ($block->templates->template as $blockTemplate) {
                                // parent block_id by parent_name
                                $parentBlockInfo = $this->getBlockByTxtId($blockTemplate->parent_block);
                                $parent_block_id = $parentBlockInfo['block_id'];
                                if (!$parent_block_id) {
                                    $error_text = 'Layout (' . $layoutName . ') XML error: block template "'
                                        . $blockTemplate->template_name . '" (block_txt_id: "' . $block->block_txt_id
                                        . '") have not parent block!';
                                    $error = new AError ($error_text);
                                    $error->toLog()->toDebug();
                                    $this->errors = 1;
                                }

                                BlockTemplate::firstOrCreate(
                                    [
                                        'block_id'        => $block_id,
                                        'parent_block_id' => $parent_block_id
                                    ],
                                    [
                                        'template' => $blockTemplate->template_name
                                    ]
                                );
                            }
                        }
                    } else {
                        if (!$restricted) {
                            //log warning if try to update existing block with new controller or template
                            if ($block->templates || $block->controller) {
                                $error_text = 'Layout (' . $layoutName . ') XML warning: '
                                    . 'Block (block_txt_id: "' . $block->block_txt_id . '") cannot be updated. '
                                    . 'This block is used by another template(s)! Will be linked to existing block';
                                $error = new AWarning ($error_text);
                                $error->toLog()->toDebug();
                            }
                        }
                    } // end of check for use

                    //Finally relate block with current layout
                    $exists = (bool)BlockLayout::where('layout_id', '=', $layoutId)
                        ->where('block_id', '=', $block_id)
                        ->where('parent_instance_id', '=', $parentInstanceId)
                        ->count();

                    $status = (int)$block->status ?: 1;

                    if (!$exists && $layout->action != "delete") {
                        $position = (int)$block->position;
                        // if parent block exists add positioning
                        if ($parentInstanceId && !$position) {
                            $position = BlockLayout::where('parent_instance_id', '=', $parentInstanceId)
                                    ->max('position') + 10;
                        }
                        $position = !$position ? 10 : $position;

                        $layoutInstance = BlockLayout::create(
                            [
                                'layout_id'          => $layoutId,
                                'block_id'           => $block_id,
                                'parent_instance_id' => $parentInstanceId,
                                'position'           => $position,
                                'status'             => $status
                            ]
                        );
                        $instanceId = $layoutInstance->instance_id;
                    }
                } // end if block_id
            }
        } // end of update block

        // start recursion for all included blocks
        if ($block->block) {
            foreach ($block->block as $childBlock) {
                $this->_processBlock($layout, $childBlock, $instanceId);
            }
        }

        return true;
    }

    /**
     * @param int $layoutId
     * @param object $block
     * @param int|null $parentInstanceId
     *
     * @return bool
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function _processCustomBlock(int $layoutId, object $block, ?int $parentInstanceId = null)
    {
        //get block_id of custom block by block type(base block_txt_id)
        $block_id = $this->getBlockByTxtId((string)$block->type)['block_id'];

        // if base block not found - break processing
        if (!$block_id) {
            $error_text = 'Layout XML load error: Cannot insert custom block (custom_block_txt_id: "'
                . $block->custom_block_txt_id . '") because block_id of type "'
                . $block->type . '" does not exists.';
            $error = new AError ($error_text);
            $error->toLog()->toDebug();
            $this->errors = 1;
            return false;
        }

        // get custom block by name and base block id
        $blockName = (string)$block->custom_block_txt_id;
        $customBlockId = (int)BlockDescription::select('custom_blocks.custom_block_id')
            ->join('custom_blocks', 'custom_blocks.custom_block_id', '=', 'block_descriptions.custom_block_id')
            ->where('block_descriptions.name', '=', $blockName)
            ->where('custom_blocks.block_id', '=', $block_id)
            ->useCache('layout')
            ->first()?->custom_block_id;

        $action = (string)$block->action;
        $status = (int)$block->status ?: 1;

        if (!$action) {
            $action = 'insert-update';
        }

        // DELETE BLOCK
        if ($action == 'delete') {
            $this->_deleteCustomBlock($block, $layoutId);
        } else {
            // insert or update custom block
            // check is this block was already inserted in previous loop by xml tree
            if (isset($this->customBlocks[(string)$block->custom_block_txt_id])) {
                $customBlockId = $this->customBlocks[(string)$block->custom_block_txt_id];
            } else {
                if (!$customBlockId) {
                    // if block is new
                    $customBlockObj = CustomBlock::create(
                        [
                            'block_id' => $block_id
                        ]
                    );
                    $customBlockId = $customBlockObj->custom_block_id;
                }
                $this->customBlocks[(string)$block->custom_block_txt_id] = $customBlockId;
            }

            $parent_inst = [];

            // if parent block exists
            if ($parentInstanceId) {
                $parent_inst[0] = $parentInstanceId;
            } else {
                $block_txt_id = $block->installed->placeholder;
                foreach ($block_txt_id as $parent_instance_txt_id) {
                    $parent_inst[] = $this->_getInstanceIdByTxtId($layoutId, (string)$parent_instance_txt_id);
                }
            }

            $position = (int)$block->position;
            foreach ($parent_inst as $par_inst) {
                //if no position provided increase by 10
                if (!$position) {
                    $position = BlockLayout::where('parent_instance_id', '=', $par_inst)
                            ->max('position') + 10;
                }
                BlockLayout::create(
                    [
                        'layout_id'          => $layoutId,
                        'block_id'           => (int)$block_id,
                        'custom_block_id'    => (int)$customBlockId,
                        'parent_instance_id' => (int)$par_inst,
                        'position'           => $position,
                        'status'             => $status
                    ]
                );
            }

            // insert custom block content
            if ($block->block_descriptions->block_description) {
                foreach ($block->block_descriptions->block_description as $xmlBlockDescription) {
                    $language_id = $this->_getLanguageIdByName((string)$xmlBlockDescription->language);
                    //if loading language does not exist or installed, skip
                    if (!$language_id) {
                        continue;
                    }

                    $description = ['language_id' => $language_id];
                    foreach ((new BlockDescription())->getFillable() as $attrName) {
                        $value = (string)$xmlBlockDescription->{$attrName};
                        if ($value) {
                            $description[$attrName] = $value;
                        }
                    }
                    $this->saveBlockDescription($block_id, $customBlockId, $description);
                }
            }
        }

        return true;
    }

    /**
     * @param object $xmlBlock
     * @param int $layout_id
     *
     * @return bool
     * @throws Exception
     */
    protected function _deleteBlock($xmlBlock, int $layout_id)
    {
        //delete block if we allowed
        if (in_array($xmlBlock->block_txt_id, $this->main_placeholders)) {
            return false;
        }
        //get block_id
        $block_id = (int)$this->getBlockByTxtId((string)$xmlBlock->block_txt_id);
        if (!$block_id) {
            // if we do not know about this block - break;
            return false;
        }

        // check if block is used by another layouts
        $total = BlockLayout::where('block_id', '=', $block_id)
            ->where('layout_id', '<>', $layout_id)
            ->count();
        //do not allow to delete block if used by other layout or template
        if (!$total) {
            $this->deleteBlock(null, $block_id);
        }

        return true;
    }

    /**
     * @param object $xmlBlock
     * @param int $layoutId
     *
     * @return bool
     * @throws Exception
     */
    protected function _deleteCustomBlock(object $xmlBlock, int $layoutId)
    {
        //get block_id of custom block by block type(base block_txt_id)
        $blockId = (int)$this->getBlockByTxtId($xmlBlock->type)['block_id'];
        if (!$blockId) {
            // if we do not know about this block - break;
            return false;
        }
        //get block custom
        $blockName = (string)$xmlBlock->custom_block_txt_id;
        $customBlockId = (int)BlockDescription::select('custom_blocks.custom_block_id')
            ->join('custom_blocks', 'custom_blocks.block_id', '=', 'blocks.block_id')
            ->where('block_descriptions.name', '=', $blockName)
            ->where('custom_blocks.block_id', '=', $blockId)
            ->useCache('layout')
            ->first()->custom_block_id;

        if (!$customBlockId) {
            // if we do not know about this custom block - break;
            return false;
        }

        //Delete block and unlink from layout
        BlockLayout::where('block_id', '=', $blockId)
            ->where('layout_id', '=', $layoutId)
            ->where('custom_block_id', '=', $customBlockId)
            ->delete();
        // check if block used by another layouts
        $total = BlockLayout::where('block_id', '=', $blockId)
            ->where('layout_id', '<>', $layoutId)
            ->where('custom_block_id', '=', $customBlockId)
            ->count();
        if (!$total) {
            CustomBlock::find($customBlockId)?->delete();
        }
        return true;
    }

    /**
     * @param string $languageName
     *
     * @return int
     * @throws Exception
     */
    protected function _getLanguageIdByName($languageName = '')
    {
        return (int)Language::where("filename", "like", $languageName)
            ->useCache('localization')
            ->first()->language_id;
    }

    /**
     * @param int $layoutId
     * @param string $blockTxtId
     *
     * @return int|null
     */
    protected function _getInstanceIdByTxtId(int $layoutId, string $blockTxtId)
    {
        if (!$layoutId || !$blockTxtId) {
            return false;
        }

        return BlockLayout::where('block_layouts.layout_id', '<>', $layoutId)
            ->join('blocks', 'blocks.block_id', '=', 'block_layouts.block_id')
            ->where('blocks.block_txt_id', '=', $blockTxtId)
            ->useCache('layout')
            ->first()->instance_id;
    }

    /**
     * Function return integer type of layout by given text. Used by xml-import of layouts.
     *
     * @param string $text_type
     *
     * @return int
     */
    protected function _getIntLayoutTypeByText($text_type)
    {
        $text_type = ucfirst($text_type);
        return match ($text_type) {
            'Default', 'General' => 0,
            'Active' => 1,
            'Draft' => 2,
            'Template' => 3,
            default => 1,
        };
    }
}