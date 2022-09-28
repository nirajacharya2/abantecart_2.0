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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Layout
 *
 * @property int $layout_id
 * @property string $template_id
 * @property string $layout_name
 * @property int $layout_type
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $pages_layouts
 *
 * @package abc\models
 */
class Layout extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['pages_layouts', 'block_layouts'];
    protected $primaryKey = 'layout_id';
    public $timestamps = false;

    protected $casts = [
        'layout_type'   => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'template_id',
        'layout_name',
        'layout_type',
        'date_added',
        'date_modified',
    ];

    public function pages_layouts()
    {
        return $this->hasMany(PagesLayout::class, 'layout_id');
    }

    public function block_layouts()
    {
        return $this->hasMany(BlockLayout::class, 'layout_id');
    }
}
