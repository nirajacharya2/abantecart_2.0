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

namespace abc\models\content;

use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Content
 *
 * @property int $content_id
 * @property int $parent_content_id
 * @property int $sort_order
 * @property int $status
 *
 * @property ContentDescription $description
 * @property ContentDescription|Collection $descriptions
 * @property ContentsToStore|Collection $stores
 *
 * @package abc\models
 */
class Content extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'stores'];
    public $timestamps = false;

    protected $primaryKey = 'content_id';
    protected $casts = [
        'parent_content_id' => 'int',
        'sort_order'        => 'int',
        'status'            => 'int',
    ];

    protected $fillable = [
        'sort_order',
        'status',
    ];

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(ContentDescription::class, 'content_id', 'content_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(ContentDescription::class, 'content_id');
    }

    public function stores()
    {
        return $this->hasMany(ContentsToStore::class, 'content_id');
    }
}
