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

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\core\lib\AException;
use abc\core\lib\AListing;
use abc\models\catalog\Product;
use abc\modules\traits\ProductListingTrait;
use Illuminate\Database\Eloquent\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ControllerBlocksListingBlock extends AController
{
    use ProductListingTrait;
    public function __construct($registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->data['empty_render_text'] =
            'To view content of block you should be logged in and prices must be without taxes';
    }

    public function main($instance_id = 0, $custom_block_id = 0)
    {
        //set default template first for case singleton usage
        if (!$this->view->getTemplate()) {
            $this->view->setTemplate('blocks/listing_block.tpl');
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $block_data = $this->getBlockContent($instance_id, $custom_block_id);

        $block_details = $this->layout->getBlockDetails($instance_id);
        $parent_block = $this->layout->getBlockDetails($block_details['parent_instance_id']);
        $parent_block_txt_id = $parent_block['block_txt_id'];

        $extension_controllers = $this->extensions->getExtensionControllers();
        $exists = false;
        foreach ($extension_controllers as $ext) {
            if (in_array($this->data['controller'], (array) $ext['storefront'])) {
                $exists = true;
                break;
            }
        }

        $templateOverridden = false;

        if ($block_data) {
            if (!$exists || !$this->data['controller']) {
                //Only products have special listing data preparation
                if (in_array(
                    $this->data['listing_datasource'],
                    [
                        'collection',
                        'custom_products',
                        'catalog_product_getRelatedProducts',
                        'catalog_product_getPopularProducts',
                        'catalog_product_getSpecialProducts',
                        'catalog_product_getFeatured',
                        'catalog_product_getLatest',
                        'catalog_product_getBestsellers',
                    ]
                )) {
                    $this->prepareProducts($block_data['content'], $block_data['block_wrapper']);
                    $templateOverridden = true;
                } else {
                    $block_data['content'] = $this->prepareItems($block_data['content']);
                }

                $this->view->assign('block_framed', (int) $block_data['block_framed']);
                $this->view->assign('content', $block_data['content']);
                $this->view->assign('heading_title', $block_data['title']);
            } else {
                $override = $this->dispatch($this->data['controller'], [$parent_block_txt_id, $block_data]);
                $this->view->setOutput($override->dispatchGetOutput());
            }
            // need to set wrapper for non products listing blocks
            if ($this->view->isTemplateExists($block_data['block_wrapper']) && !$templateOverridden) {
                $this->view->setTemplate($block_data['block_wrapper']);
            }

            $this->processTemplate();
        }
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * @param Collection $productList
     * @param string $block_wrapper
     *
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function prepareProducts(Collection &$productList, $block_wrapper = '')
    {
        $this->loadLanguage('product/product');
        $this->processList($productList);
        $productList = $this->data['products'];

        // set sign of displaying prices on storefront
        if ($this->config->get('config_customer_price')) {
            $display_price = true;
        } elseif ($this->customer->isLogged()) {
            $display_price = true;
        } else {
            $display_price = false;
        }
        $this->view->assign('display_price', $display_price);
        $this->view->assign('review_status', $this->config->get('enable_reviews'));

        $this->view->assign('products', $this->data['products']);
        $vertical_tpl = [
            'blocks/listing_block_column_left.tpl',
            'blocks/listing_block_column_right.tpl',
        ];

        if ($this->view->isTemplateExists($block_wrapper)) {
            $template = $block_wrapper;
        } else {
            $template = in_array($this->view->getTemplate(), $vertical_tpl)
                ? 'blocks/special.tpl'
                : 'blocks/special_home.tpl';
        }
        $this->view->setTemplate($template);
    }

    /**
     * @param array $content
     *
     * @return array
     * @throws AException|InvalidArgumentException
     */
    protected function prepareItems($content = [])
    {
        $item_name = '';
        if (isset($content[0]['category_id'])) {
            $item_name = 'category';
        } else {
            if (isset($content[0]['manufacturer_id'])) {
                $item_name = 'manufacturer';
            } else {
                if (isset($content[0]['product_id'])) {
                    $item_name = 'product';
                } else {
                    if (isset($content[0]['resource_id'])) {
                        $item_name = 'resource';
                    }
                }
            }
        }

        foreach ($content as &$cn) {
            $cn['item_name'] = $item_name;
            switch ($item_name) {
                case 'category':
                    $cn['href'] = $this->html->getSEOURL(
                        'product/category',
                        '&category_id='.$cn['category_id'],
                        '&encode'
                    );
                    break;
                case 'manufacturer':
                    $cn['href'] = $this->html->getSEOURL(
                        'product/manufacturer',
                        '&manufacturer_id='.$cn['manufacturer_id'],
                        '&encode'
                    );
                    break;
            }
        }
        return $content;
    }

    /**
     * @param int $instance_id
     * @param int $custom_block_id
     *
     * @return array
     * @throws AException|InvalidArgumentException|ReflectionException
     */
    protected function getBlockContent($instance_id = 0, $custom_block_id = 0)
    {
        $output = [];
        if ($custom_block_id) {
            $this->data['custom_block_id'] = $custom_block_id;
        } else {
            $this->data['block_info'] = $this->layout->getBlockDetails($instance_id);
            $this->data['custom_block_id'] = $this->data['block_info']['custom_block_id'];
        }

        //getting block properties
        $this->data['descriptions'] = $this->layout->getBlockDescriptions($this->data['custom_block_id']);

        if ($this->data['descriptions'][$this->config->get('storefront_language_id')]) {
            $key = $this->config->get('storefront_language_id');
        } else {
            $key = key($this->data['descriptions']);
        }

        // getting list
        $this->data['content'] = $this->getListing();
        if ($this->data['content']) {
            $output = [
                'title'         => $this->data['descriptions'][$key]['title'],
                'block_framed'  => $this->data['descriptions'][$key]['block_framed'],
                'content'       => $this->data['content'],
                'block_wrapper' => $this->data['descriptions'][$key]['block_wrapper'],
            ];
        }

        return $output;
    }

    public function getListing()
    {
        if (!$this->data['custom_block_id'] || !$this->data['descriptions']) {
            return false;
        }
        $result = [];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $listing = new AListing($this->data['custom_block_id']);
        $content = unserialize($this->data['descriptions'][$this->config->get('storefront_language_id')]['content']);
        if (!$content && $this->data['descriptions']) {
            $content = current($this->data['descriptions']);
            $content = unserialize($content['content']);
        }
        $this->data['controller'] = $content['block_appearance'];
        $this->data['listing_datasource'] = $content['listing_datasource'];

        $data_sources = $listing->getListingDataSources();
        $data_source = $data_sources[$content['listing_datasource']];

        $route = $content['listing_datasource'];
        $limit = $content['limit'];

        // for resource library
        if ($route == 'media') {
            $result = $this->getMedia($content, $limit);
        } // otherwise -  select list from method
        elseif ($route) {
            $args = [
                'language_id'       => $this->language->getLanguageID(),
                'limit'             => $limit,
                'store_id'          => $this->config->get('config_store_id'),
                'customer_group_id' => $this->customer->getCustomerGroupId()
                    ///use default customer group if customer is unknown
                    ?? $this->config->get('config_customer_group_id'),
            ];

            if ($route == 'collection' && $content['collection_id']) {
                $args['collection_id'] = $content['collection_id'];
            }
            if (class_exists($data_source['model'])) {
                $mdl = new $data_source['model'];
            } else {
                return false;
            }

            $args = $listing->getListingMethodArguments(
                $data_source['model'],
                $content['listing_datasource'],
                $args
            );

            if ($args === false) {
                return false;
            }

            $result = call_user_func_array(
                [
                    $mdl,
                    $data_source['method'],
                ],
                $args
            );

            $result = $result instanceof Collection ? $result : (array)$result;
            if ($result) {
                $desc = $listing->getListingDataSources();
                foreach ($desc as $d) {
                    if ($d['method'] == $data_source['method']) {
                        $data_source = $d;
                        break;
                    }
                }
            }
        }

        if ($result && $data_source['model'] != Product::class && !current($result)['thumb']) {
            //add thumbnails to custom list of items. 1 thumbnail per item
            $result = $this->prepareCustomItems($data_source, $result);
        }
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        return $result;
    }

    protected function getMedia($content, $limit)
    {
        if (!$content['resource_type']) {
            return false;
        }
        $rl = new AResource($content['resource_type']);
        $image_sizes = [
            'main' => [
                'width'  => $this->config->get('config_image_popup_width'),
                'height' => $this->config->get('config_image_popup_height'),
            ],
        ];

        if (isset($this->request->get['product_id'])) {
            $object_name = 'products';
            $object_id = $this->request->get['product_id'];
            $image_sizes['thumb'] = [
                'width'  => $this->config->get('config_image_product_width'),
                'height' => $this->config->get('config_image_product_height'),
            ];
        } elseif (isset($this->request->get['category_id']) || isset($this->request->get['path'])) {
            $object_name = 'categories';
            $image_sizes['thumb'] = [
                'width'  => $this->config->get('config_image_category_width'),
                'height' => $this->config->get('config_image_category _height'),
            ];
            if (isset($this->request->get['category_id'])) {
                $object_id = $this->request->get['product_id'];
            } else {
                $temp = explode("_", $this->request->get['path']);
                end($temp);
                $object_id = current($temp);
            }
        } elseif (isset($this->request->get['manufacturer_id'])) {
            $object_name = 'manufacturers';
            $object_id = $this->request->get['manufacturer_id'];
            $image_sizes['thumb'] = [
                'width'  => $this->config->get('config_image_manufacturer_width'),
                'height' => $this->config->get('config_image_manufacturer_height'),
            ];
        } else {
            $object_name = '';
            $object_id = null;
            $image_sizes['thumb'] = [
                'width'  => $this->config->get('config_image_product_width'),
                'height' => $this->config->get('config_image_product_height'),
            ];
        }

        $resources = $rl->getResourceAllObjects(
            $object_name,
            $object_id,
            [
                'main' => [
                    'width'  => $image_sizes['main']['width'],
                    'height' => $image_sizes['main']['height'],
                ],

                'thumb' => [
                    'width'  => $image_sizes['thumb']['width'],
                    'height' => $image_sizes['thumb']['height'],
                ],
            ],
            $limit,
            true
        );

        if (!$resources) {
            return null;
        }
        if ($limit == 1) {
            $resources = [$resources];
        }

        foreach ($resources as $k => $resource) {
            if ($resource['origin'] == 'external') {
                $result[$k]['resource_code'] = $resource['thumb_html'];
            } else {
                if ($content['resource_type'] != 'image') {
                    $title = $resource['title'];
                } else {
                    $title = $resource['title'];
                }

                $result[$k]['thumb'] = [
                    'main_url'      => $resource['main_url'],
                    'main_html'     => $resource['main_html'],
                    'thumb_url'     => $resource['thumb_url'],
                    'thumb_html'    => $resource['thumb_html'],
                    'width'         => $image_sizes['thumb']['width'],
                    'height'        => $image_sizes['thumb']['height'],
                    'title'         => $title,
                    'resource_type' => $content['resource_type'],
                    'origin'        => 'internal',
                ];
            }
        }
        return $result;
    }

    /**
     * @param array $data_source
     * @param array $inData
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    private function prepareCustomItems($data_source, $inData)
    {
        if (!$data_source['rl_object_name']) {
            return $inData;
        }
        $inData = $inData instanceof Collection ? $inData->toArray() : $inData;
        $resource = new AResource('image');
        $image_sizes = [];
        if ($inData) {
            if ($data_source['rl_object_name']) {
                switch ($data_source['rl_object_name']) {
                    case 'categories':
                        $image_sizes = [
                            'thumb' => [
                                'width'  => $this->config->get('config_image_category_width'),
                                'height' => $this->config->get('config_image_category_height'),
                            ],
                        ];
                        break;
                    case 'manufacturers':
                        $image_sizes = [
                            'thumb' => [
                                'width'  => $this->config->get('config_image_manufacturer_width'),
                                'height' => $this->config->get('config_image_manufacturer_height'),
                            ],
                        ];
                        break;
                    default:
                        $image_sizes = [
                            'thumb' => [
                                'width'  => $this->config->get('config_image_product_width'),
                                'height' => $this->config->get('config_image_product_height'),
                            ],
                        ];
                }
            }

            //build list of ids
            $ids = array_column($inData, $data_source['data_type']);

            $thumbnails = $ids
                ? $resource->getMainThumbList(
                $data_source['rl_object_name'],
                $ids,
                $image_sizes['thumb']['width'],
                $image_sizes['thumb']['height']
                )
                : [];

            foreach ($inData as &$item) {
                $thumbnail = $thumbnails[$item[$data_source['data_type']]];
                $item['image'] = $item['thumb'] = $thumbnail;
            }
        }
        return $inData;
    }
}