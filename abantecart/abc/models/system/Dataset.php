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
 * Class Dataset
 *
 * @property int $dataset_id
 * @property string $dataset_name
 * @property string $dataset_key
 *
 * @property Collection $dataset_definitions
 * @property Collection $dataset_properties
 *
 * @package abc\models
 */
class Dataset extends BaseModel
{
    protected $primaryKey = 'dataset_id';
    public $timestamps = false;

    protected $fillable = [
        'dataset_name',
        'dataset_key',
    ];

    public function dataset_definitions()
    {
        return $this->hasMany(DatasetDefinition::class, 'dataset_id');
    }

    public function dataset_properties()
    {
        return $this->hasMany(DatasetProperty::class, 'dataset_id');
    }
}
