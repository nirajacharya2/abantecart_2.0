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
namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class DownloadAttributeValue
 *
 * @property int $download_attribute_id
 * @property int $attribute_id
 * @property int $download_id
 * @property string $attribute_value_ids
 *
 * @property Download $download
 *
 * @package abc\models
 */
class DownloadAttributeValue extends BaseModel
{
    protected $primaryKey = 'download_attribute_id';
    public $timestamps = false;

    protected $casts = [
        'attribute_id' => 'int',
        'download_id'  => 'int',
    ];

    protected $fillable = [
        'attribute_id',
        'download_id',
        'attribute_value_ids',
    ];

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }
}
