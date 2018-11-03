<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 01/11/2018
 * Time: 23:17
 */
namespace abc\controllers\storefront;
use abc\core\engine\AController;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AMenu_Storefront;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ControllerBlocksMenuNav extends AController {

    private $menu_items;
    public $data = [];

    public function main() {

        //disable cache when login display price setting is off or enabled showing of prices with taxes
        if( ($this->config->get('config_customer_price') && !$this->config->get('config_tax'))
            &&	$this->html_cache()	){
            return null;
        }

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

        $instance_id = func_get_arg(0);
        $cache_key = 'storefront_menu.store_'.(int)$this->config->get('config_store_id').'_lang_'.$this->config->get('storefront_language_id').'_instance_'.$instance_id;

        $block_data = $this->getBlockContent($instance_id);
        $this->view->assign('heading_title', $block_data['title'] );

        if($block_data['content']){
            // need to set wrapper for non products listing blocks
            if($this->view->isTemplateExists($block_data['block_wrapper'])){
                $this->view->setTemplate( $block_data['block_wrapper'] );
            }

            $this->menu_items = $this->cache->pull($cache_key);
            if($this->menu_items === false){
                $menu = new AMenu_Storefront();
                $this->menu_items = $menu->getMenuItems();

                //writes into cache result of calling _buildMenu func!
                $this->cache->push($cache_key, $this->menu_items);
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

        $output = array(
            'title' => $descriptions[$key]['title'],
            'content' => html_entity_decode($descriptions[$key]['content'], ENT_QUOTES, 'utf-8'),
            'block_wrapper' => $descriptions[$key]['block_wrapper'],
            'block_framed' => $descriptions[$key]['block_framed'],
        );

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
            $menu[] = array(
                'id' => $item['item_id'],
                'current' => $item['current'],
                'icon' => $item['item_icon'],
                'icon_rl_id' => $item['item_icon_rl_id'],
                'href' =>  $href,
                'text' => $item['item_text'][$lang_id],
                'children' => $this->_buildMenu( $item['item_id'] ),
            );
        }
        return $menu;
    }
}