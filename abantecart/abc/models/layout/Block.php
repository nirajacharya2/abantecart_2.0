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
 * Class Block
 *
 * @property int $block_id
 * @property string $block_txt_id
 * @property string $controller
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $block_templates
 * @property Collection $custom_blocks
 *
 * @package abc\models
 */
class Block extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;
    protected $cascadeDeletes = ['templates', 'custom_blocks'];
    protected $primaryKey = 'block_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'block_txt_id',
        'controller',
        'date_added',
        'date_modified',
    ];

    public function templates()
    {
        return $this->hasMany(BlockTemplate::class, 'block_id');
    }

    public function custom_blocks()
    {
        return $this->hasMany(CustomBlock::class, 'block_id');
    }
}
