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
namespace abc\models\layout;

use abc\models\BaseModel;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Page
 *
 * @property int $page_id
 * @property int $parent_page_id
 * @property string $controller
 * @property string $key_param
 * @property string $key_value
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $page_descriptions
 * @property Collection $pages_forms
 * @property Collection $pages_layouts
 *
 * @package abc\models
 */
class Page extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'forms', 'layouts'];

    protected $primaryKey = 'page_id';
    public $timestamps = false;

    protected $casts = [
        'parent_page_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'parent_page_id',
        'controller',
        'key_param',
        'key_value',
        'date_added',
        'date_modified',
    ];

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(PageDescription::class, 'page_id', 'page_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(PageDescription::class, 'page_id');
    }

    /**
     * @return HasMany
     */
    public function forms()
    {
        return $this->hasMany(PagesForm::class, 'page_id');
    }

    /**
     * @return HasMany
     */
    public function layouts()
    {
        return $this->hasMany(PagesLayout::class, 'page_id');
    }
}
