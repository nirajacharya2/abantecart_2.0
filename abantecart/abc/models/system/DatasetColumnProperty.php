<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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
namespace abc\models\system;

use abc\models\BaseModel;

/**
 * Class DatasetColumnProperty
 *
 * @property int $rowid
 * @property int $dataset_column_id
 * @property string $dataset_column_property_name
 * @property string $dataset_column_property_value
 *
 * @property DatasetDefinition $dataset_definition
 *
 * @package abc\models
 */
class DatasetColumnProperty extends BaseModel
{
    protected $primaryKey = 'rowid';
    public $timestamps = false;

    protected $casts = [
        'dataset_column_id' => 'int',
    ];

    protected $fillable = [
        'dataset_column_id',
        'dataset_column_property_name',
        'dataset_column_property_value',
    ];

    public function definition()
    {
        return $this->belongsTo(DatasetDefinition::class, 'dataset_column_id');
    }
}
