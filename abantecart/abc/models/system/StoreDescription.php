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
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class StoreDescription
 *
 * @property int $store_id
 * @property int $language_id
 * @property string $description
 * @property string $title
 * @property string $meta_description
 * @property string $meta_keywords
 *
 * @property Store $store
 * @property Language $language
 *
 * @package abc\models
 */
class StoreDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'store_id',
        'language_id',
    ];
    public $timestamps = false;

    protected $casts = [
        'store_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'description',
        'title',
        'meta_description',
        'meta_keywords',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
