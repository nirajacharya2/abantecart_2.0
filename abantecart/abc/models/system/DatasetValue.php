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
use Carbon\Carbon;

/**
 * Class DatasetValue
 *
 * @property int $dataset_column_id
 * @property int $value_integer
 * @property float $value_float
 * @property string $value_varchar
 * @property string $value_text
 * @property Carbon $value_timestamp
 * @property bool $value_boolean
 * @property int $value_sort_order
 * @property int $row_id
 *
 * @property DatasetDefinition $dataset_definition
 *
 * @package abc\models
 */
class DatasetValue extends BaseModel
{
    protected $primaryKey = 'value_sort_order';
    public $timestamps = false;

    protected $casts = [
        'dataset_column_id' => 'int',
        'value_integer'     => 'int',
        'value_float'       => 'float',
        'value_boolean'     => 'bool',
        'row_id'            => 'int',
    ];

    protected $dates = [
        'value_timestamp',
    ];

    protected $fillable = [
        'dataset_column_id',
        'value_integer',
        'value_float',
        'value_varchar',
        'value_text',
        'value_timestamp',
        'value_boolean',
        'row_id',
    ];

    public function dataset_definition()
    {
        return $this->belongsTo(DatasetDefinition::class, 'dataset_column_id');
    }
}
