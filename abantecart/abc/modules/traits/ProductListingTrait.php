<?php

namespace abc\modules\traits;

/**
 * Trait ProductListingTrait
 * @property \abc\core\engine\ALanguage $language
 *
 */
trait ProductListingTrait
{
    public function fillSortsList()
    {
        $this->data['sorts'] = [
            'p.sort_order-ASC'   => $this->language->get('text_default'),
            'pd.name-ASC'        => $this->language->get('text_sorting_name_asc'),
            'pd.name-DESC'       => $this->language->get('text_sorting_name_desc'),
            'p.price-ASC'        => $this->language->get('text_sorting_price_asc'),
            'p.price-DESC'       => $this->language->get('text_sorting_price_desc'),
            'rating-DESC'        => $this->language->get('text_sorting_rating_desc'),
            'rating-ASC'         => $this->language->get('text_sorting_rating_asc'),
            'date_modified-DESC' => $this->language->get('text_sorting_date_desc'),
            'date_modified-ASC'  => $this->language->get('text_sorting_date_asc'),
        ];
    }
}