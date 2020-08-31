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
namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\lib\AMenu_Storefront;

class ControllerBlocksMenuNav extends AController {

    private $menu_items;
    public $data = [];

    public function main() {

        //disable cache when login display price setting is off or enabled showing of prices with taxes
        if( ($this->config->get('config_customer_price')
                && !$this->config->get('config_tax'))
                && $this->html_cache()
        ){
            return null;
        }

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

        $instance_id = func_get_arg(0);
        $cache_key = 'storefront_menu.store_'.(int)$this->config->get('config_store_id')
            .'_lang_'.$this->config->get('storefront_language_id')
            .'_instance_'.$instance_id;

        $block_data = $this->getBlockContent($instance_id);
        $this->view->assign('heading_title', $block_data['title'] );

        if($block_data['content']){
            // need to set wrapper for non products listing blocks
            if($this->view->isTemplateExists($block_data['block_wrapper'])){
                $this->view->setTemplate( $block_data['block_wrapper'] );
            }

            $this->menu_items = $this->cache->get($cache_key);
            if($this->menu_items === null){
                $menu = new AMenu_Storefront();
                $this->menu_items = $menu->getMenuItems();

                //writes into cache result of calling _buildMenu func!
                $this->cache->put($cache_key, $this->menu_items);
            }

            //build menu structure after caching. related to http/https urls
            $this->menu_items = $this->_buildMenu($block_data['content']);

            $this->data['storemenu'] =  $this->menu_items;

            $this->view->assign('instance_id', $instance_id);
            $this->view->batchAssign($this->data);
            $this->processTemplate();
        }
        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
    }

    protected function getBlockContent($instance_id) {
        $block_info = $this->layout->getBlockDetails($instance_id);
        $custom_block_id = $block_info['custom_block_id'];
        $descriptions = $this->layout->getBlockDescriptions($custom_block_id);
        if($descriptions[$this->config->get('storefront_language_id')]){
            $key = $this->config->get('storefront_language_id');
        }else{
            $key = key($descriptions);
        }

        $output = [
            'title' => $descriptions[$key]['title'],
            'content' => html_entity_decode($descriptions[$key]['content'], ENT_QUOTES, 'utf-8'),
            'block_wrapper' => $descriptions[$key]['block_wrapper'],
            'block_framed' => $descriptions[$key]['block_framed'],
        ];

        return $output;
    }

    private function _buildMenu( $parent = '' ) {
        $menu = [];
        if ( empty($this->menu_items[$parent]) ) return $menu;
        $lang_id = (int)$this->config->get('storefront_language_id');

        foreach ( $this->menu_items[$parent] as $item ) {
            if( preg_match ( "/^http/i", $item ['item_url'] ) ){
                $href = $item ['item_url'];
            }
            //process relative url such as ../blog/index.php
            elseif( preg_match ( "/^\.\.\//i", $item ['item_url'] ) ){
                $href = str_replace('../','',$item ['item_url']);
            }else {
                $href = $this->html->getSecureURL( $item ['item_url'] );
            }
            $menu[] = [
                'id' => $item['item_id'],
                'current' => $item['current'],
                'icon' => $item['item_icon'],
                'icon_rl_id' => $item['item_icon_rl_id'],
                'href' =>  $href,
                'text' => $item['item_text'][$lang_id],
                'children' => $this->_buildMenu( $item['item_id'] ),
            ];
        }
        return $menu;
    }
}