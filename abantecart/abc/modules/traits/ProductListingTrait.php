<?php

namespace abc\modules\traits;

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
            $sort_prefix.$default_sorting => $this->language->get('text_default'),
            'pd.name-ASC'                 => $this->language->get('text_sorting_name_asc'),
            'pd.name-DESC'                => $this->language->get('text_sorting_name_desc'),
            'p.price-ASC'                 => $this->language->get('text_sorting_price_asc'),
            'p.price-DESC'                => $this->language->get('text_sorting_price_desc'),
            'rating-DESC'                 => $this->language->get('text_sorting_rating_desc'),
            'rating-ASC'                  => $this->language->get('text_sorting_rating_asc'),
            'date_modified-DESC'          => $this->language->get('text_sorting_date_desc'),
            'date_modified-ASC'           => $this->language->get('text_sorting_date_asc'),
        ];
    }
}