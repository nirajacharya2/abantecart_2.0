<?php

namespace abc\modules\traits;

use abc\core\engine\AResource;
use abc\core\lib\AException;
use abc\models\catalog\Product;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use stdClass;

/**
 * Trait ProductListingTrait
 *
 * @property \abc\core\engine\ALanguage $language
 *
 */
trait ProductListingTrait
{
    public function fillSortsList()
    {
        $default_sorting = $this->config->get('config_product_default_sort_order');
        $sort_prefix = '';
        if (str_starts_with($default_sorting, 'name-')) {
            $sort_prefix = 'pd.';
        } elseif (str_starts_with($default_sorting, 'price-')) {
            $sort_prefix = 'p.';
        }
        $this->data['sorts'] = [
            $sort_prefix . $default_sorting => $this->language->get('text_default'),
            'name-ASC'                      => $this->language->get('text_sorting_name_asc'),
            'name-DESC'                     => $this->language->get('text_sorting_name_desc'),
            'price-ASC'                     => $this->language->get('text_sorting_price_asc'),
            'price-DESC'                    => $this->language->get('text_sorting_price_desc'),
            'rating-DESC'                   => $this->language->get('text_sorting_rating_desc'),
            'rating-ASC'                    => $this->language->get('text_sorting_rating_asc'),
            'date_modified-DESC'            => $this->language->get('text_sorting_date_desc'),
            'date_modified-ASC'             => $this->language->get('text_sorting_date_asc'),
        ];
    }


    /**
     * @param Collection|array $list
     * @return void
     * @throws InvalidArgumentException|ReflectionException|AException
     */
    public function processList(Collection|array $list)
    {
        $cart_rt = $this->config->get('embed_mode') ? 'r/checkout/cart/embed' : 'checkout/cart';

        if ($this->customer->isLogged() || $this->customer->isUnauthCustomer()) {
            $this->data['is_customer'] = true;
            $wishlist = $this->customer->getWishList();
        } else {
            $wishlist = [];
        }

        $productIds = $list->pluck('product_id')->toArray();

        //get thumbnails by one pass
        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'products',
            $productIds,
            $this->config->get('config_image_product_width'),
            $this->config->get('config_image_product_height')
        );

        /** @var stdClass|Collection|Product $result */
        foreach ($list as $i => $result) {
            $thumbnail = $thumbnails[$result->product_id];
            $rating = $result->rating;
            $special = false;
            $discount = $result->discount_price;

            if ($discount) {
                $price = $this->currency->format(
                    $this->tax->calculate(
                        $discount,
                        $result->tax_class_id,
                        $this->config->get('config_tax')
                    )
                );
            } else {
                $price = $this->currency->format(
                    $this->tax->calculate(
                        $result['price'],
                        $result->tax_class_id,
                        $this->config->get('config_tax')
                    )
                );
                $special = $result['special_price'];
                if ($special) {
                    $special = $this->currency->format(
                        $this->tax->calculate(
                            $special,
                            $result->tax_class_id,
                            $this->config->get('config_tax')
                        )
                    );
                }
            }

            $hasOptions = $result->option_count;

            if ($hasOptions) {
                $add = $this->html->getSEOURL(
                    'product/product',
                    '&product_id=' . $result['product_id'],
                    '&encode'
                );
            } else {
                if ($this->config->get('config_cart_ajax')) {
                    $add = '#';
                } else {
                    $add = $this->html->getSecureURL(
                        $cart_rt,
                        '&product_id=' . $result['product_id'],
                        '&encode'
                    );
                }
            }
            //check for stock status, availability and config
            $track_stock = false;
            $in_stock = false;
            $no_stock_text = $this->language->get('text_out_of_stock');
            $total_quantity = 0;
            $stock_checkout = $result->stock_checkout === ''
                ? $this->config->get('config_stock_checkout')
                : $result->stock_checkout;
            if ($result->subtract) {
                $track_stock = true;
                $total_quantity = $result->quantity;
                //we have stock or out of stock checkout is allowed
                if ($total_quantity > 0 || $stock_checkout) {
                    $in_stock = true;
                }
            }

            $in_wishlist = false;
            if ($wishlist && $wishlist[$result->product_id]) {
                $in_wishlist = true;
            }

            $catalog_mode = false;
            if ($result->product_type_id) {
                $prodTypeSettings = Product::getProductTypeSettings((int)$result->product_id);

                if ($prodTypeSettings
                    && is_array($prodTypeSettings)
                    && isset($prodTypeSettings['catalog_mode'])
                ) {
                    $catalog_mode = (bool)$prodTypeSettings['catalog_mode'];
                }
            }


            $this->data['products'][$i] = $result->toArray();
            $this->data['products'][$i]['stars'] = sprintf($this->language->get('text_stars'), $rating);
            $this->data['products'][$i]['price'] = $price;
            $this->data['products'][$i]['options'] = $hasOptions;
            $this->data['products'][$i]['special'] = $special;
            $this->data['products'][$i]['thumb'] = $thumbnail;
            $this->data['products'][$i]['href'] = $this->html->getSEOURL(
                'product/product',
                '&product_id=' . $result->product_id,
                '&encode'
            );
            $this->data['products'][$i]['add'] = $add;
            $this->data['products'][$i]['track_stock'] = $track_stock;
            $this->data['products'][$i]['in_stock'] = $in_stock;
            $this->data['products'][$i]['no_stock_text'] = $no_stock_text;
            $this->data['products'][$i]['total_quantity'] = $total_quantity;
            $this->data['products'][$i]['in_wishlist'] = $in_wishlist;
            $this->data['products'][$i]['product_wishlist_add_url'] = $this->html->getURL(
                'product/wishlist/add',
                '&product_id=' . $result->product_id
            );
            $this->data['products'][$i]['product_wishlist_remove_url'] = $this->html->getURL(
                'product/wishlist/remove',
                '&product_id=' . $result->product_id
            );
            $this->data['products'][$i]['catalog_mode'] = $catalog_mode;
        }
    }

}