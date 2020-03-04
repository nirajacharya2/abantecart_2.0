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

namespace abc\controllers\storefront;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\APromotion;
use abc\core\engine\AResource;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\core\lib\AMessage;
use abc\models\catalog\Category;
use abc\models\catalog\Manufacturer;
use abc\models\catalog\Product;
use abc\models\catalog\ProductOption;
use abc\models\catalog\ProductOptionValue;
use abc\models\catalog\ProductTag;
use Exception;
use H;
use Illuminate\Support\Collection;

/**
 * Class ControllerPagesProductProduct
 *
 * @package abc\controllers\storefront
 */
class ControllerPagesProductProduct extends AController
{

    public $data = [];
    protected $routes = [];
    /**
     * @var Product
     */
    protected $model;

    protected function init()
    {
        //is this an embed mode
        if ($this->config->get('embed_mode') == true) {
            $this->routes['cart_rt'] = 'r/checkout/cart/embed';
        } else {
            $this->routes['cart_rt'] = 'checkout/cart';
        }
    }

    /**
     * Check if HTML Cache is enabled for the method
     *
     * @return array - array of data keys to be used for cache key building
     */
    public static function main_cache_keys()
    {
        //disable cache when some error occurred and need to show it
        $registry = Registry::getInstance();
        if (!empty($registry->get('session')->data['error'])) {
            return null;
        }

        return ['product_id', 'path', 'key', 'manufacturer_id', 'category_id', 'description', 'keyword'];
    }

    public function main()
    {
        $request = $this->request->get;
        $this->init();

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        if ($this->config->get('config_require_customer_login') && !$this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        $this->loadModel('tool/seo_url');

        if (isset($request['path'])) {
            $path = '';
            foreach (explode('_', $request['path']) as $path_id) {
                $category_info = Category::getCategory($path_id);
                if (!$path) {
                    $path = $path_id;
                } else {
                    $path .= '_'.$path_id;
                }
                if ($category_info) {
                    $this->document->addBreadcrumb([
                        'href'      => $this->html->getSEOURL('product/category', '&path='.$path, '&encode'),
                        'text'      => $category_info['name'],
                        'separator' => $this->language->get('text_separator'),
                    ]);
                }
            }
        }

        $this->loadModel('catalog/manufacturer');
        if (isset($request['manufacturer_id'])) {
            $manufacturer_info = (new Manufacturer)->getManufacturer($request['manufacturer_id']);

            if ($manufacturer_info) {
                $this->document->addBreadcrumb([
                    'href'      => $this->html->getSEOURL('product/manufacturer',
                        '&manufacturer_id='.$request['manufacturer_id'], '&encode'),
                    'text'      => $manufacturer_info['name'],
                    'separator' => $this->language->get('text_separator'),
                ]);
            }
        }

        if (isset($request['keyword'])) {
            $url = '';
            if (isset($request['category_id'])) {
                $url .= '&category_id='.$request['category_id'];
            }
            if (isset($request['description'])) {
                $url .= '&description='.$request['description'];
            }
            $this->document->addBreadcrumb([
                'href'      => $this->html->getURL('product/search', '&keyword='.$request['keyword'].$url, '&encode'),
                'text'      => $this->language->get('text_search'),
                'separator' => $this->language->get('text_separator'),
            ]);
        }

        //key of product from cart
        $key = [];
        if (H::has_value($request['key'])) {
            $key = explode(':', $request['key']);
            $product_id = (int)$key[0];
        } elseif (H::has_value($request['product_id'])) {
            $product_id = (int)$request['product_id'];
        } else {
            $product_id = 0;
        }

        $urls = [
            'is_group_option' => $this->html->getURL(
                'r/product/product/is_group_option',
                '&product_id='.$product_id,
                '&encode'
            ),
        ];
        $this->view->assign('urls', $urls);

        $this->loadModel('catalog/product');
        $promotion = new APromotion();

        $rels = [
            'description',
            'options.description',
            'options.values.description',
            'manufacturer',
            'stock_status',
            'related.description',
            'related.options',
            'active_reviews',
        ];
        if ($this->config->get('enable_reviews')) {
            $rels[] = 'related.active_reviews';
        }

        $product = Product::with($rels)->find($product_id);

        //can not locate product? get out
        if (!$product) {
            $this->_product_not_found($product_id);
            return null;
        }

        //add this id into session for recently viewed block
        $this->session->data['recently_viewed'][] = $product_id;

        $url = $this->_build_url_params();

        $this->view->assign('error', '');
        if (isset($this->session->data['error'])) {
            $this->view->assign('error', $this->session->data['error']);
            unset($this->session->data['error']);
        }

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSEOURL('product/product', $url.'&product_id='.$product_id, '&encode'),
            'text'      => $product->description->name,
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->document->setTitle($product->description->name);
        $this->document->setKeywords($product->description->meta_keywords);
        $this->document->setDescription($product->description->meta_description);

        $this->data['heading_title'] = $product->description->name;
        $this->data['minimum'] = $product->minimum;
        $this->data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product->minimum);
        $this->data['maximum'] = $product->maximum;
        $this->data['text_maximum'] = sprintf($this->language->get('text_maximum'), $product->maximum);
        $this->data['option_resources_url'] = $this->html->getURL('r/product/product/get_option_resources');
        $this->data['calc_total_url'] = $this->html->getURL('r/product/product/calculateTotal');
        $this->data['product_review_url'] = $this->html->getURL('product/review/review', '&product_id='.$product_id);
        $this->data['product_review_write_url'] = $this->html->getURL(
            'product/review/write',
            '&product_id='.$product_id
        );
        $this->data['product_wishlist_add_url'] = $this->html->getURL(
            'product/wishlist/add',
            '&product_id='.$product_id
        );
        $this->data['product_wishlist_remove_url'] = $this->html->getURL(
            'product/wishlist/remove',
            '&product_id='.$product_id
        );
        $this->data['captcha_url'] = $this->html->getURL('common/captcha');
        $this->data['update_view_count_url'] = $this->html->getURL(
            'common/view_count/product',
            '&product_id='.$product_id
        );

        $this->data['tab_review'] = sprintf(
            $this->language->get('tab_review'),
            $product->active_reviews->count()
        );

        if ($this->config->get('enable_reviews')) {
            $average = $product->active_reviews->pluck('rating')->avg();
            $this->data['rating_element'] = HtmlElementFactory::create(
                [
                    'type'    => 'rating',
                    'name'    => 'rating',
                    'value'   => '',
                    'options' => [1 => 1, 2, 3, 4, 5],
                    'pack'    => true,
                ]);
        } else {
            $average = false;
        }

        $this->data['review_status'] = $this->config->get('enable_reviews');
        $this->data['text_stars'] = sprintf($this->language->get('text_stars'), $average);

        $this->data['review_name'] = HtmlElementFactory::create(
            [
                'type' => 'input',
                'name' => 'name',
            ]);
        $this->data['review_text'] = HtmlElementFactory::create(
            [
                'type' => 'textarea',
                'name' => 'text',
                'attr' => ' rows="8" cols="50" ',
            ]);

        if ($this->config->get('config_recaptcha_site_key')) {
            $this->data['recaptcha_site_key'] = $this->config->get('config_recaptcha_site_key');
            $this->data['review_recaptcha'] = HtmlElementFactory::create(
                [
                    'type'               => 'recaptcha',
                    'name'               => 'recaptcha',
                    'recaptcha_site_key' => $this->data['recaptcha_site_key'],
                    'language_code'      => $this->language->getLanguageCode(),
                ]);

        } else {
            $this->data['review_captcha'] = HtmlElementFactory::create(
                [
                    'type' => 'input',
                    'name' => 'captcha',
                    'attr' => '',
                ]);
        }
        $this->data['review_button'] = HtmlElementFactory::create(
            [
                'type'  => 'button',
                'name'  => 'review_submit',
                'text'  => $this->language->get('button_submit'),
                'style' => 'btn-primary lock-on-click',
                'icon'  => 'fa fa-comment',
            ]);

        $this->data['product_info'] = $product->toArray();

        $form = new AForm();
        $form->setForm(['form_name' => 'product']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'product',
                'action' => $this->html->getSecureURL($this->routes['cart_rt']),
            ]);

        $discount = $promotion->getProductDiscount($product_id);

        //Need to round price after discounts and specials
        //round main price to currency decimal_place setting (most common 2, but still...)
        $currency = Registry::currency()->getCurrency();
        $decimal_place = (int)$currency['decimal_place'];
        $decimal_place = !$decimal_place ? 2 : $decimal_place;

        if ($discount) {
            $product_price = round($discount, $decimal_place);
            $this->data['price_num'] = $this->tax->calculate(
                $discount,
                $product->tax_class_id,
                (bool)$this->config->get('config_tax')
            );
            $this->data['special'] = false;
        } else {
            $product_price = round($product->price, $decimal_place);
            $this->data['price_num'] = $this->tax->calculate(
                $product_price,
                $product->tax_class_id,
                (bool)$this->config->get('config_tax')
            );

            $special = $promotion->getProductSpecial($product_id);
            if ($special) {
                $product_price = round($special, $decimal_place);
                $this->data['special_num'] = $this->tax->calculate(
                    $special,
                    $product->tax_class_id,
                    (bool)$this->config->get('config_tax')
                );
            } else {
                $this->data['special'] = false;
            }
        }

        $this->data['price'] = $this->currency->format($this->data['price_num']);

        if (isset($this->data['special_num'])) {
            $this->data['special'] = $this->currency->format($this->data['special_num']);
        }

        $product_discounts = $promotion->getProductDiscounts($product_id);
        $discounts = [];
        foreach ($product_discounts as $discount) {
            $discounts[] = [
                'quantity' => $discount['quantity'],
                'price'    => $this->currency->format(
                    $this->tax->calculate($discount['price'],
                        $product->tax_class_id,
                        (bool)$this->config->get('config_tax'))),
            ];
        }
        $this->data['discounts'] = $discounts;
        $this->data['product_price'] = $product_price;
        $this->data['tax_class_id'] = $product->tax_class_id;

        if (!$product->call_to_order) {
            $qnt = (int)$request['quantity'];
            if (!$qnt) {
                $qnt = $product->minimum ?: 1;
            }

            $qnt = (int)$product->minimum && $product->minimum > $qnt
                ? (int)$product->minimum
                : $qnt;
            $qnt = (int)$product->maximum && $product->maximum < $qnt
                ? (int)$product->maximum
                : $qnt;

            $this->data['form']['minimum'] = $form->getFieldHtml(
                [
                    'type'  => 'input',
                    'name'  => 'quantity',
                    'value' => $qnt,
                    'style' => 'short',
                    'attr'  => ' size="3" ',
                ]);

            $this->data['form']['add_to_cart'] = $form->getFieldHtml(
                [
                    'type'  => 'button',
                    'name'  => 'add_to_cart',
                    'text'  => $this->language->get('button_add_to_cart'),
                    'style' => 'button1',
                ]);
        }

        $this->data['form']['product_id'] = $form->getFieldHtml(
            [
                'type'  => 'hidden',
                'name'  => 'product_id',
                'value' => $product_id,
            ]
        );
        $this->data['form']['redirect'] = $form->getFieldHtml(
            [
                'type'  => 'hidden',
                'name'  => 'redirect',
                'value' => $this->html->getURL('product/product', $url.'&product_id='.$product_id, '&encode'),
            ]
        );

        $this->data['model'] = $product->getAttribute('model');
        $this->data['manufacturer'] = $product->manufacturer->name;
        $this->data['manufacturers'] = $this->html->getSEOURL(
            'product/manufacturer',
            '&manufacturer_id='.$product->manufacturer_id,
            '&encode'
        );
        $this->data['description'] = html_entity_decode(
            $product->description->description,
            ENT_QUOTES,
            ABC::env('APP_CHARSET')
        );
        $this->data['product_id'] = $product_id;
        $this->data['average'] = $average;

        $catalog_mode = false;
        if ($product->product_type_id) {
            $prodTypeSettings = Product::getProductTypeSettings($product_id);
            if ($prodTypeSettings && is_array($prodTypeSettings) && isset($prodTypeSettings['catalog_mode'])) {
                $catalog_mode = (bool)$prodTypeSettings['catalog_mode'];
            }
        }
        $this->data['catalog_mode'] = $catalog_mode;

        if (!H::has_value($product->stock_checkout)) {
            $this->data['product_info']['stock_checkout'] = $this->config->get('config_stock_checkout');
        }

        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb(
            'manufacturers',
            $product->manufacturer_id,
            (int)$this->config->get('config_image_grid_width'),
            (int)$this->config->get('config_image_grid_height')
        );
        if (!preg_match('/no_image/', $thumbnail['thumb_url'])) {
            $this->data['manufacturer_icon'] = $thumbnail['thumb_url'];
        }

        // Prepare options and values for display
        $elements_with_options = HtmlElementFactory::getElementsWithOptions();
        $options = [];
        $product_options = $product->options;

        //get info from cart if key presents
        $cart_product_info = [];
        if ($key) {
            $cart_product_info = $this->cart->getProduct($request['key']);
        }
        $htmlElements = HtmlElementFactory::getAvailableElements();
        foreach ($product_options as $option) {
            /** @var ProductOption $option */
            $values = [];
            $disabled_values = [];
            $name = $price = $attr = '';
            $default_value = $cart_product_info['options'][$option->product_option_id];
            if ($option->element_type == 'R') {
                $default_value = is_array($default_value) ? current($default_value) : (string)$default_value;
            }
            $preset_value = $default_value;
            $opt_stock_message = '';
            foreach ($option->values as $option_value) {
                /** @var ProductOptionValue $option_value */
                $default_value = $option_value->default && !$default_value
                    ? $option_value->product_option_value_id
                    : $default_value;
                // for case when trying to add to cart without required options. we get option-array back inside _GET
                if (H::has_value($request['option'][$option->product_option_id])) {
                    $default_value = $request['option'][$option->product_option_id];
                }

                $name = $option_value->description->name;
                //check if we disable option based on stock settings
                if ($option_value->subtract && $this->config->get('config_nostock_autodisable')
                    && $option_value->quantity <= 0) {
                    continue;
                }

                //Stock and status
                $opt_stock_message = '';
                //if options has stock tracking and not allowed to be purchased out of stock
                if ($option_value->subtract && !$product->stock_checkout) {
                    if ($option_value->quantity <= 0) {
                        //show out of stock message
                        $opt_stock_message = $this->language->get('text_out_of_stock');
                        $disabled_values[] = $option_value->product_option_value_id;
                    } else {
                        if ($this->config->get('config_stock_display')) {
                            if ($option_value->quantity > 0) {
                                $opt_stock_message = $option_value->quantity." ".$this->language->get('text_instock');
                                $opt_stock_message = "({$opt_stock_message})";
                            }
                        }
                    }
                } else {
                    if ($option_value->subtract && $product->stock_checkout) {
                        if ($option_value->quantity <= 0
                            && $this->config->get('config_stock_display')
                            && $product->stock_status
                        ) {
                            $opt_stock_message = "({$product->stock_status->name})";
                        }
                    }
                }

                //Apply option price modifier
                if ($option_value->prefix == '%') {
                    $price = $this->tax->calculate(
                        ($product_price * $option_value->price / 100),
                        $product->tax_class_id,
                        (bool)$this->config->get('config_tax')
                    );
                    if ($price != 0) {
                        $price = $this->currency->format($price);
                    } else {
                        $price = '';
                    }
                } else {
                    $price = $this->tax->calculate(
                        $option_value->price,
                        $product->tax_class_id,
                        (bool)$this->config->get('config_tax')
                    );
                    if ($price != 0) {
                        $price = $this->currency->format($price);
                    } else {
                        $price = '';
                    }
                }

                $values[$option_value->product_option_value_id] = $option_value->description->name
                    .' '
                    .$price
                    .' '
                    .$opt_stock_message;

                //disable stock tracking for product if some of option have subtract
                if ($option_value->subtract) {
                    $this->data['product_info']['subtract'] = false;
                }

                if ($option->element_type == 'B') {
                    $name = $default_value = preg_replace("/\r|\n/", " ", $option_value->description->name);
                    if ($price) {
                        $default_value .= '</br>';
                        $name .= ' ';
                    }
                    if ($price) {
                        $default_value .= $price.' ';
                        $name .= $price;
                    }
                    $option->required = false;
                }
            }

            //if not values are build, nothing to show
            if (count($values)) {
                $value = '';
                //add price to option name if it is not element with options
                if (!in_array($option->element_type, $elements_with_options) && $option->element_type != 'B') {
                    $option->description->name .= ' <small>'.$price.'</small>';
                    if ($opt_stock_message) {
                        $option['name'] .= '<br />'.$opt_stock_message;
                    }
                    $value = $default_value ? $default_value : $name;
                } else {
                    if ($option->element_type == 'B') {
                        $value = $name;
                    }
                }

                //set default selection is nothing selected
                if (!H::has_value($value) && H::has_value($default_value)) {
                    $value = $default_value;
                }

                //for checkbox with empty value
                if ($option->element_type == 'C') {
                    if ($value == '') {
                        $value = 1;
                    }
                    $attr = key($option['option_value']);
                }

                $option_data = [
                    'type'             => $htmlElements[$option->element_type]['type'],
                    'name'             => !in_array($option->element_type, HtmlElementFactory::getMultivalueElements())
                        ? 'option['.$option->product_option_id.']'
                        : 'option['.$option->product_option_id.'][]',
                    'attr'             => ' data-attribute-value-id="'.$attr.'"',
                    'value'            => $value,
                    'options'          => $values,
                    'disabled_options' => $disabled_values,
                    'required'         => $option['required'],
                    'placeholder'      => $option['option_placeholder'],
                    'regexp_pattern'   => $option['regexp_pattern'],
                    'error_text'       => $option['error_text'],
                ];

                if ($option->element_type == 'C') {
                    if (!in_array($value, ['0', '1'])) {
                        $option_data['label_text'] = $value;
                    }
                    $option_data['checked'] = $preset_value ? true : false;
                }

                $options[] = [
                    'name' => $option->description->name,
                    'html' => $this->html->buildElement($option_data),  // not a string!!! it's object!
                ];

                // main product image
                $mSizes = [
                    'main'  => [
                        'width'  => $this->config->get('config_image_popup_width'),
                        'height' => $this->config->get('config_image_popup_height'),
                    ],
                    'thumb' => [
                        'width'  => $this->config->get('config_image_thumb_width'),
                        'height' => $this->config->get('config_image_thumb_height'),
                    ],
                ];

                $option_images['main'] =
                    $resource->getResourceAllObjects('product_option_value', $option_data['value'], $mSizes, 1, false);
                if (!$option_images['main']) {
                    unset($option_images['main']);
                }

                // additional images
                $oSizes = [
                    'main'   =>
                        [
                            'width'  => $this->config->get('config_image_popup_width'),
                            'height' => $this->config->get('config_image_popup_height'),
                        ],
                    'thumb'  =>
                        [
                            'width'  => $this->config->get('config_image_additional_width'),
                            'height' => $this->config->get('config_image_additional_height'),
                        ],
                    //product image zoom related thumbnail
                    'thumb2' =>
                        [
                            'width'  => $this->config->get('config_image_thumb_width'),
                            'height' => $this->config->get('config_image_thumb_height'),
                        ],
                ];

                $option_images['images'] = $resource->getResourceAllObjects(
                    'product_option_value',
                    $option_data['value'],
                    $oSizes,
                    0,
                    false
                );

                if (!$option_images['images']) {
                    unset($option_images['images']);
                }
            }
        }

        $this->data['options'] = $options;

        //handle stock messages
        // if track stock is off. no messages needed.
        if ($product->isStockTrackable()) {
            //NOTE: total quantity can be integer and true(in case stock-track is off)
            $total_quantity = $product->hasAnyStock();
            $this->data['track_stock'] = true;
            $this->data['can_buy'] = true;
            //out of stock if no quantity and no stock checkout is disabled
            if ($total_quantity <= 0 && !$this->data['product_info']['stock_checkout']) {
                $this->data['can_buy'] = false;
                $this->data['in_stock'] = false;
                //show out of stock message
                $this->data['stock'] = $this->language->get('text_out_of_stock');
            } else {
                $this->data['can_buy'] = true;
                $this->data['in_stock'] = true;
                $this->data['stock'] = '';
                if ($this->config->get('config_stock_display') && $total_quantity > 0) {
                    //if not tracked - show nothing
                    $this->data['stock'] = $total_quantity !== true ? $total_quantity.' ' : '';
                }
                if ($total_quantity <= 0) {
                    $this->data['stock'] = $product->stock_status ? $product->stock_status->name : 'No stock';
                } else {
                    $this->data['stock'] .= $this->language->get('text_instock');
                }
            }

            //check if we need to disable product for no stock
            if ($this->config->get('config_nostock_autodisable') && $total_quantity <= 0) {
                //set available data
                $pd_identifiers = "ID: ".$product_id;
                $pd_identifiers .= (empty($product->getAttribute('model')) ? ''
                    : " Model: ".$product->getAttribute('model'));
                $pd_identifiers .= (empty($product->sku) ? '' : " SKU: ".$product->sku);
                $message_ttl = sprintf($this->language->get('notice_out_of_stock_ttl'), $product->description->name);
                $message_txt = sprintf(
                    $this->language->get('notice_out_of_stock_body'),
                    $product->description->name,
                    $pd_identifiers
                );
                //record to message box
                $msg = new AMessage();
                $msg->saveNotice($message_ttl, $message_txt);
                try {
                    $product->update(['status' => 0]);
                } catch (Exception $e) {
                    $this->log->error(__FILE__.":".__LINE__.": ".$e->getMessage());
                }

                abc_redirect(
                    $this->html->getSEOURL(
                        'product/product',
                        '&product_id='.$product_id,
                        '&encode'
                    )
                );
            }
        } else {
            $this->data['can_buy'] = true;
            if ($product->quantity <= 0) {
                $this->data['stock'] = $product->stock_status ? $product->stock_status->name : 'No stock';
            }
        }

        // main product image
            $sizes = [
                'main'  => [
                    'width'  => $this->config->get('config_image_popup_width'),
                    'height' => $this->config->get('config_image_popup_height'),
                ],
                'thumb' => [
                    'width'  => $this->config->get('config_image_thumb_width'),
                    'height' => $this->config->get('config_image_thumb_height'),
                ],
            ];
        if (!$option_images['main']) {
            $this->data['image_main'] = $resource->getResourceAllObjects('products', $product_id, $sizes, 1, false);
            if ($this->data['image_main']) {
                $this->data['image_main']['sizes'] = $sizes;
            }
        } else {
            $this->data['image_main'] = $option_images['main'];
            if ($this->data['image_main']) {
                $this->data['image_main']['sizes'] = $sizes;
            }
            unset($option_images['main']);
        }

        // additional images
        $sizes = [
            'main'   => [
                'width'  => $this->config->get('config_image_popup_width'),
                'height' => $this->config->get('config_image_popup_height'),
            ],
            'thumb'  => [
                'width'  => $this->config->get('config_image_additional_width'),
                'height' => $this->config->get('config_image_additional_height'),
            ],
            'thumb2' => [
                'width'  => $this->config->get('config_image_thumb_width'),
                'height' => $this->config->get('config_image_thumb_height'),
            ],
        ];
        if (!$option_images['images']) {
            $this->data['images'] = $resource->getResourceAllObjects('products', $product_id, $sizes, 0, false);
        } else {
            $this->data['images'] = $option_images['images'];
        }

        $this->data['related_products'] = [];
        $this->data['tags'] = [];
        $relatedProducts = $product->related;
        foreach ($relatedProducts as $related) {
            /** @var Product|Collection $related */
            // related product image
            $sizes = [
                'main'  => [
                    'width'  => $this->config->get('config_image_related_width'),
                    'height' => $this->config->get('config_image_related_height'),
                ],
                'thumb' => [
                    'width'  => $this->config->get('config_image_related_width'),
                    'height' => $this->config->get('config_image_related_height'),
                ],
            ];
            $image = $resource->getResourceAllObjects('products', $related->product_id, $sizes, 1);

            $rating = $related->active_reviews
                ? $related->active_reviews->pluck('rating')->avg()
                : false;

            $special = false;
            $discount = $promotion->getProductDiscount($related->product_id);
            if ($discount) {
                $price = $this->currency->format(
                    $this->tax->calculate(
                        $discount,
                        $related->tax_class_id,
                        (bool)$this->config->get('config_tax')
                    )
                );
            } else {
                $price = $this->currency->format(
                    $this->tax->calculate(
                        $related->price,
                        $related->tax_class_id,
                        (bool)$this->config->get('config_tax')
                    )
                );

                $special = $promotion->getProductSpecial($related->product_id);
                if ($special) {
                    $special = $this->currency->format(
                        $this->tax->calculate(
                            $special,
                            $related->tax_class_id,
                            (bool)$this->config->get('config_tax')
                        )
                    );
                }
            }

            if (count($related->options)) {
                $add = $this->html->getSEOURL(
                    'product/product',
                    '&product_id='.$related->product_id,
                    '&encode'
                );
            } else {
                if ($this->config->get('config_cart_ajax')) {
                    $add = '#';
                } else {
                    $add = $this->html->getSecureURL(
                        $this->routes['cart_rt'],
                        '&product_id='.$related->product_id,
                        '&encode');
                }
            }

            $this->data['related_products'][] = [
                'product_id'    => $related->product_id,
                'name'          => $related->description->name,
                'model'         => $related->getAttribute('model'),
                'rating'        => $rating,
                'stars'         => sprintf($this->language->get('text_stars'), $rating),
                'price'         => $price,
                'call_to_order' => $related->call_to_order,
                'options'       => $related->options->toArray(),
                'special'       => $special,
                'image'         => $image,
                'href'          => $this->html->getSEOURL(
                    'product/product',
                    '&product_id='.$related->product_id,
                    '&encode'
                ),
                'add'           => $add,
                'tax_class_id'  => $related->tax_class_id,
            ];

            foreach ($related->tagsByLanguage() as $tag) {
                /** @var ProductTag $tag */
                if ($tag->tag) {
                    $this->data['tags'][] = [
                        'tag'  => $tag->tag,
                        'href' => $this->html->getURL('product/search', '&keyword='.$tag->tag, '&encode'),
                    ];
                }
            }
        }

        if ($this->config->get('config_customer_price')) {
            $display_price = true;
        } elseif ($this->customer->isLogged()) {
            $display_price = true;
        } else {
            $display_price = false;
        }
        $this->data['display_price'] = $display_price;

        //downloads before order if allowed
        if ($this->config->get('config_download')) {
            $download_list = $this->download->getDownloadsBeforeOrder($product_id);
            if ($download_list) {
                $downloads = [];

                foreach ($download_list as $download) {
                    $href = $this->html->getURL(
                        'account/download/startdownload',
                        '&download_id='.$download['download_id']
                    );
                    $download['attributes'] = $this->download->getDownloadAttributesValuesForCustomer(
                        $download['download_id']
                    );

                    $download['button'] = $form->getFieldHtml(
                        [
                            'type'  => 'button',
                            'id'    => 'download_'.$download['download_id'],
                            'href'  => $href,
                            'title' => $this->language->get('text_start_download'),
                            'text'  => $this->language->get('text_start_download'),
                        ]);
                    $downloads[] = $download;
                }

                $this->data['downloads'] = $downloads;
            }
        }

        #check if product is in a wishlist
        $this->data['is_customer'] = false;
        if ($this->customer->isLogged() || $this->customer->isUnauthCustomer()) {
            $this->data['is_customer'] = true;
            $whishlist = $this->customer->getWishList();
            if ($whishlist[$product_id]) {
                $this->data['in_wishlist'] = true;
            }
        }

        $this->view->setTemplate('pages/product/product.tpl');
        $this->view->batchAssign($this->data);
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function _product_not_found($product_id)
    {
        $this->init();
        $url = $this->_build_url_params();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSEOURL(
                    'product/product',
                    $url.'&product_id='.$product_id,
                    '&encode'
                ),
                'text'      => $this->language->get('text_error'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->document->setTitle($this->language->get('text_error'));

        $this->data['heading_title'] = $this->language->get('text_error');
        $this->data['text_error'] = $this->language->get('text_error');
        $continue = HtmlElementFactory::create(
            [
                'type'  => 'button',
                'name'  => 'continue_button',
                'text'  => $this->language->get('button_continue'),
                'style' => 'button',
            ]
        );

        $this->view->assign('button_continue', $continue);
        $this->data['continue'] = $this->html->getHomeURL();

        $this->view->setTemplate('pages/error/not_found.tpl');
        $this->view->batchAssign($this->data);
        $this->processTemplate();
    }

    protected function _build_url_params()
    {
        $request = $this->request->get;
        $url = '';
        if (isset($request['path'])) {
            $url .= '&path='.$request['path'];
        }

        if (isset($request['manufacturer_id'])) {
            $url .= '&manufacturer_id='.$request['manufacturer_id'];
        }

        if (isset($request['keyword'])) {
            $url .= '&keyword='.$request['keyword'];
            }

        if (isset($request['category_id'])) {
            $url .= '&category_id='.$request['category_id'];
        }

        if (isset($request['description'])) {
            $url .= '&description='.$request['description'];
        }

        return $url;
    }
}
