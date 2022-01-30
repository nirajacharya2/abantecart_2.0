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

namespace abc\models\catalog;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class ObjectFieldSetting extends BaseModel
{
    protected $primaryKey = 'object_field_setting_id';
    public $timestamps = false;

    protected $fillable = [
        'object_type',
        'object_type_id',
        'object_field_name',
        'field_setting',
        'field_setting_value'
    ];

    protected function setKeysForSaveQuery($query):Builder
    {
        $query
            ->where('object_type', '=', $this->getAttribute('object_type'))
            ->where('object_type_id', '=', $this->getAttribute('object_type_id'))
            ->where('object_field_name', '=', $this->getAttribute('object_field_name'))
            ->where('field_setting', '=', $this->getAttribute('field_setting'));
        return $query;
    }

}
