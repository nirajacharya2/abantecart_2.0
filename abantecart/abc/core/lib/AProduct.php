<?php

namespace abc\core\lib;

use abc\core\engine\AHtml;
use abc\core\engine\ALanguage;
use abc\core\engine\AResource;
use abc\models\catalog\Product;
use abc\models\catalog\ProductOption;
use abc\models\catalog\ProductOptionValue;
use H;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class AProduct
{
    /**
     * @param int $productId
     * @param int $languageId
     * @param AConfig $config
     * @param ATax $tax
     * @param ACurrency $currency
     * @param ALanguage $language
     * @param ACustomer $customer
     * @param ADownload $download
     * @param AHtml $html
     * @return array|null
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    static function getProductDetails(
        int $productId,
        int $languageId,
        AConfig $config,
        ATax $tax,
        ACurrency $currency,
        ALanguage $language,
        ACustomer $customer,
        ADownload $download,
        AHtml $html
    ): ?array {
        $relations = [
            'description',
            'descriptions' => function ($query) use ($languageId) {
                $query->where('product_descriptions.language_id', '=', $languageId);
            },
            'options.description',
            'options.values.description',
            'manufacturer',
            'stock_status',
            'related.description',
            'related.options',
            'active_reviews',
        ];
        if ($config->get('enable_reviews')) {
            $relations[] = 'related.active_reviews';
        }

        $product = Product::with($relations)->find($productId);
        if (!$product) {
            return null;
        }

        $promotion = new APromotion();
        $discount = $promotion->getProductDiscount($productId);

        $decimal_place = (int)$currency->getCurrency()['decimal_place'];
        $decimal_place = $decimal_place ?: 2;

        if ($discount) {
            $priceNum = $tax->calculate(
                $discount,
                $product->tax_class_id,
                (bool)$config->get('config_tax')
            );
            $special = false;
        } else {
            $productPrice = round((float)$product->price, $decimal_place);
            $priceNum = $tax->calculate(
                $productPrice,
                $product->tax_class_id,
                (bool)$config->get('config_tax')
            );

            $special = $promotion->getProductSpecial($productId);
            if ($special) {
                $specialNum = $tax->calculate(
                    $special,
                    $product->tax_class_id,
                    (bool)$config->get('config_tax')
                );
            } else {
                $special = false;
            }
        }

        $product->priceNum = $priceNum;
        $product->specialNum = $specialNum ?? 0;
        $product->special = $special;

        $productDiscounts = $promotion->getProductDiscounts($productId);
        $discounts = [];
        foreach ($productDiscounts as $discount) {
            $discounts[] = [
                'quantity' => $discount['quantity'],
                'price' => $currency->format(
                    $tax->calculate(
                        $discount['price'],
                        $product->tax_class_id,
                        (bool)$config->get('config_tax')
                    ),
                    '',
                    '',
                    false
                ),
            ];
        }
        $product->discounts = $discounts;
        $product->product_price = $productPrice;

        $catalogMode = false;
        if ($product->product_type_id) {
            $prodTypeSettings = Product::getProductTypeSettings($productId);
            if ($prodTypeSettings && is_array($prodTypeSettings) && isset($prodTypeSettings['catalog_mode'])) {
                $catalogMode = (bool)$prodTypeSettings['catalog_mode'];
            }
        }
        $product->catalog_mode = $catalogMode;

        if (!H::has_value($product->stock_checkout)) {
            $product->stock_checkout = $config->get('config_stock_checkout');
        }


        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb(
            'manufacturers',
            $product->manufacturer_id,
            (int)$config->get('config_image_grid_width'),
            (int)$config->get('config_image_grid_height')
        );
        if (!str_contains($thumbnail['thumb_url'], 'no_image')) {
            $product->manufacturer_icon = $thumbnail['thumb_url'];
        }

        // Prepare options and values for display
        $options = [];
        $product_options = $product->options;

        //get info from cart if key presents
        $cart_product_info = [];
        foreach ($product_options as $option) {
            /** @var ProductOption $option */
            $values = [];
            $disabled_values = [];
            $name = $price = $attr = '';
            $defaultValue = $cart_product_info['options'][$option->product_option_id];
            if ($option->element_type == 'R') {
                $defaultValue = is_array($defaultValue) ? current($defaultValue) : (string)$defaultValue;
            }
            $preset_value = $defaultValue;
            $optStockMessage = '';
            foreach ($option->values as $optionValue) {
                /** @var ProductOptionValue $optionValue */
                $defaultValue = $optionValue->default && !$defaultValue
                    ? $optionValue->product_option_value_id
                    : $defaultValue;

                $name = $optionValue->description->name;
                //check if we disable option based on stock settings
                if ($optionValue->subtract && $config->get('config_nostock_autodisable')
                    && $optionValue->quantity <= 0) {
                    continue;
                }

                //Stock and status
                $optStockMessage = '';
                //if options has stock tracking and not allowed to be purchased out of stock
                if ($optionValue->subtract && !$product->stock_checkout) {
                    if ($optionValue->quantity <= 0) {
                        //show out of stock message
                        $optStockMessage = $language->get('text_out_of_stock');
                        $disabled_values[] = $optionValue->product_option_value_id;
                    } else {
                        if ($config->get('config_stock_display')) {
                            if ($optionValue->quantity > 0) {
                                $optStockMessage = $optionValue->quantity . " " . $language->get('text_instock');
                                $optStockMessage = "({$optStockMessage})";
                            }
                        }
                    }
                } else {
                    if ($optionValue->subtract && $product->stock_checkout) {
                        if ($optionValue->quantity <= 0
                            && $config->get('config_stock_display')
                            && $product->stock_status
                        ) {
                            $optStockMessage = "({$product->stock_status->name})";
                        }
                    }
                }

                //Apply option price modifier
                if ($optionValue->prefix == '%') {
                    $price = $tax->calculate(
                        ($productPrice * $optionValue->price / 100),
                        $product->tax_class_id,
                        (bool)$config->get('config_tax')
                    );
                    if ($price != 0) {
                        $price = $currency->format($price);
                    } else {
                        $price = '';
                    }
                } else {
                    $price = $tax->calculate(
                        $optionValue->price,
                        $product->tax_class_id,
                        (bool)$config->get('config_tax')
                    );
                    if ($price != 0) {
                        $price = $currency->format($price);
                    } else {
                        $price = '';
                    }
                }

                $values[$optionValue->product_option_value_id] = $optionValue->description->name
                    . ' '
                    . $price
                    . ' '
                    . $optStockMessage;

                //disable stock tracking for product if some of option have subtract
                if ($optionValue->subtract) {
                    $product->subtract = false;
                }

                if ($option->element_type == 'B') {
                    $name = $defaultValue = preg_replace("/\r|\n/", " ", $optionValue->description->name);
                    if ($price) {
                        $defaultValue .= '</br>';
                        $name .= ' ';
                    }
                    if ($price) {
                        $defaultValue .= $price . ' ';
                        $name .= $price;
                    }
                    $option->required = false;
                }
            }

            //if not values are build, nothing to show
            if (count($values)) {
                $value = '';
                //add price to option name if it is not element with options
                if ($option->element_type != 'B') {
                    $option->description->name .= ' <small>' . $price . '</small>';
                    if ($optStockMessage) {
                        $option['name'] .= '<br />' . $optStockMessage;
                    }
                    $value = $defaultValue ?: $name;
                } else {
                    if ($option->element_type == 'B') {
                        $value = $name;
                    }
                }

                //set default selection is nothing selected
                if (!H::has_value($value) && H::has_value($defaultValue)) {
                    $value = $defaultValue;
                }

                //for checkbox with empty value
                if ($option->element_type == 'C') {
                    if ($value == '') {
                        $value = 1;
                    }
                    $attr = key($option['option_value']);
                }

                $option_data = [
                    'type' => $option->element_type,
                    'name' => $option->product_option_id,
                    'attr' => ' data-attribute-value-id="' . $attr . '"',
                    'value' => $value,
                    'options' => $values,
                    'disabled_options' => $disabled_values,
                    'required' => $option['required'],
                    'placeholder' => $option['option_placeholder'],
                    'regexp_pattern' => $option['regexp_pattern'],
                    'error_text' => $option['error_text'],
                ];

                if ($option->element_type == 'C') {
                    if (!in_array($value, ['0', '1'])) {
                        $option_data['label_text'] = $value;
                    }
                    $option_data['checked'] = (bool)$preset_value;
                }

                $options[] = [
                    'name' => $option->description->name,
                    'option_data' => $option_data
                ];

                // main product image
                $mSizes = [
                    'main' => [
                        'width' => $config->get('config_image_popup_width'),
                        'height' => $config->get('config_image_popup_height'),
                    ],
                    'thumb' => [
                        'width' => $config->get('config_image_thumb_width'),
                        'height' => $config->get('config_image_thumb_height'),
                    ],
                ];

                $option_images['main'] =
                    $resource->getResourceAllObjects('product_option_value', $option_data['value'], $mSizes, 1, false);
                if (!$option_images['main']) {
                    unset($option_images['main']);
                }

                // additional images
                $oSizes = [
                    'main' =>
                        [
                            'width' => $config->get('config_image_popup_width'),
                            'height' => $config->get('config_image_popup_height'),
                        ],
                    'thumb' =>
                        [
                            'width' => $config->get('config_image_additional_width'),
                            'height' => $config->get('config_image_additional_height'),
                        ],
                    //product image zoom related thumbnail
                    'thumb2' =>
                        [
                            'width' => $config->get('config_image_thumb_width'),
                            'height' => $config->get('config_image_thumb_height'),
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

        $product->options = $options;

        //handle stock messages
        // if track stock is off. no messages needed.
        if ($product->isStockTrackable()) {
            //NOTE: total quantity can be integer and true(in case stock-track is off)
            $total_quantity = $product->hasAnyStock();
            $product->track_stock = true;
            $product->can_buy = true;
            //out of stock if no quantity and no stock checkout is disabled
            if ($total_quantity <= 0 && !$product->stock_checkout) {
                $product->can_buy = false;
                $product->in_stock = false;
                //show out of stock message
                $product->stock = $language->get('text_out_of_stock');
            } else {
                $product->can_buy = true;
                $product->in_stock = true;
                $product->stock = '';
                if ($config->get('config_stock_display') && $total_quantity > 0) {
                    //if not tracked - show nothing
                    $product->stock = $total_quantity !== true ? $total_quantity . ' ' : '';
                }
                if ($total_quantity <= 0) {
                    $product->stock = $product->stock_status ? $product->stock_status->name : 'No stock';
                } else {
                    $product->stock .= $language->get('text_instock');
                }
            }

            //check if we need to disable product for no stock
            if ($config->get('config_nostock_autodisable') && $total_quantity <= 0) {
                //set available data
                $pd_identifiers = "ID: " . $productId;
                $pd_identifiers .= (empty($product->getAttribute('model')) ? ''
                    : " Model: " . $product->getAttribute('model'));
                $pd_identifiers .= (empty($product->sku) ? '' : " SKU: " . $product->sku);
                $message_ttl = sprintf($language->get('notice_out_of_stock_ttl'), $product->description->name);
                $message_txt = sprintf(
                    $language->get('notice_out_of_stock_body'),
                    $product->description->name,
                    $pd_identifiers
                );
            }
        } else {
            $product->can_buy = true;
            if ($product->quantity <= 0) {
                $product->stock = $product->stock_status ? $product->stock_status->name : 'No stock';
            }
        }

        // main product image
        $sizes = [
            'main' => [
                'width' => $config->get('config_image_popup_width'),
                'height' => $config->get('config_image_popup_height'),
            ],
            'thumb' => [
                'width' => $config->get('config_image_thumb_width'),
                'height' => $config->get('config_image_thumb_height'),
            ],
        ];
        if (!$option_images['main']) {
            $product->image_main = $resource->getResourceAllObjects('products', $productId, $sizes, 1, false);
            if ($product->image_main) {
                $product->image_main['sizes'] = $sizes;
            }
        } else {
            $product->image_main = $option_images['main'];
            if ($product->image_main) {
                $product->image_main['sizes'] = $sizes;
            }
            unset($option_images['main']);
        }

        // additional images
        $sizes = [
            'main' => [
                'width' => $config->get('config_image_popup_width'),
                'height' => $config->get('config_image_popup_height'),
            ],
            'thumb' => [
                'width' => $config->get('config_image_additional_width'),
                'height' => $config->get('config_image_additional_height'),
            ],
            'thumb2' => [
                'width' => $config->get('config_image_thumb_width'),
                'height' => $config->get('config_image_thumb_height'),
            ],
        ];
        if (!$option_images['images']) {
            $product->images = $resource->getResourceAllObjects('products', $productId, $sizes, 0, false);
        } else {
            $product->images = $option_images['images'];
        }

        $product->related_products = [];
        $product->tags = [];
        $relatedProducts = $product->related;
        foreach ($relatedProducts as $related) {
            /** @var Product|Collection $related */
            // related product image
            $sizes = [
                'main' => [
                    'width' => $config->get('config_image_related_width'),
                    'height' => $config->get('config_image_related_height'),
                ],
                'thumb' => [
                    'width' => $config->get('config_image_related_width'),
                    'height' => $config->get('config_image_related_height'),
                ],
            ];
            $image = $resource->getResourceAllObjects('products', $related->product_id, $sizes, 1);

            $rating = $related->active_reviews
                ? $related->active_reviews->pluck('rating')->avg()
                : false;

            $special = false;
            $discount = $promotion->getProductDiscount($related->product_id);
            if ($discount) {
                $price = $currency->format(
                    $tax->calculate(
                        $discount,
                        $related->tax_class_id,
                        (bool)$config->get('config_tax')
                    )
                );
            } else {
                $price = $currency->format(
                    $tax->calculate(
                        $related->price,
                        $related->tax_class_id,
                        (bool)$config->get('config_tax')
                    )
                );

                $special = $promotion->getProductSpecial($related->product_id);
                if ($special) {
                    $special = $currency->format(
                        $tax->calculate(
                            $special,
                            $related->tax_class_id,
                            (bool)$config->get('config_tax')
                        )
                    );
                }
            }

            $product->related_products[] = [
                'product_id' => $related->product_id,
                'name' => $related->description->name,
                'model' => $related->getAttribute('model'),
                'rating' => $rating,
                'stars' => sprintf($language->get('text_stars'), $rating),
                'price' => $price,
                'call_to_order' => $related->call_to_order,
                'options' => $related->options->toArray(),
                'special' => $special,
                'image' => $image,
                'tax_class_id' => $related->tax_class_id,
            ];
        }

        if ($config->get('config_customer_price')) {
            $display_price = true;
        } elseif ($customer->isLogged()) {
            $display_price = true;
        } else {
            $display_price = false;
        }
        $product->display_price = $display_price;


        if ($config->get('config_download')) {
            $download_list = $download->getDownloadsBeforeOrder($productId);
            if ($download_list) {
                $downloads = [];

                foreach ($download_list as $download) {
                    $href = $html->getURL(
                        'account/download/startdownload',
                        '&download_id=' . $download['download_id']
                    );
                    $download['attributes'] = $download->getDownloadAttributesValuesForCustomer(
                        $download['download_id']
                    );
                    $downloads[] = $download;
                }

                $product->downloads = $downloads;
            }
        }

        #check if product is in a wishlist
        $product->is_customer = false;
        if ($customer->isLogged() || $customer->isUnauthCustomer()) {
            $product->is_customer = true;
            $wishList = $customer->getWishList();
            if ($wishList[$productId]) {
                $product->in_wishlist = true;
            }
        }
        return $product->toArray();
    }
}