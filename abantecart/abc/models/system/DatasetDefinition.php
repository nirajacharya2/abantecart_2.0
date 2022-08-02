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
use Illuminate\Database\Eloquent\Collection;

/**
 * Class DatasetDefinition
 *
 * @property int $dataset_column_id
 * @property int $dataset_id
 * @property string $dataset_column_name
 * @property string $dataset_column_type
 * @property int $dataset_column_sort_order
 *
 * @property Dataset $dataset
 * @property Collection $dataset_column_properties
 * @property Collection $dataset_values
 *
 * @package abc\models
 */
class DatasetDefinition extends BaseModel
{
    protected $table = 'dataset_definition';
    protected $primaryKey = 'dataset_column_id';
    public $timestamps = false;

    protected $casts = [
        'dataset_id'                => 'int',
        'dataset_column_sort_order' => 'int',
    ];

    protected $fillable = [
        'dataset_id',
        'dataset_column_name',
        'dataset_column_type',
        'dataset_column_sort_order',
    ];

    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id');
    }

    public function column_properties()
    {
        return $this->hasMany(DatasetColumnProperty::class, 'dataset_column_id');
    }

    public function values()
    {
        return $this->hasMany(DatasetValue::class, 'dataset_column_id');
    }
}
