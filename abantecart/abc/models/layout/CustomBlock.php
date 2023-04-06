<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
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
use Illuminate\Database\Eloquent\Collection;

/**
 * Class CustomBlock
 *
 * @property int $custom_block_id
 * @property int $block_id
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Block $block
 * @property Collection $block_descriptions
 * @property Collection $custom_lists
 *
 * @method static CustomBlock find(int $custom_block_id)
 * @method static CustomBlock create(array $attributes)
 *
 */
class CustomBlock extends BaseModel
{
    protected $cascadeDeletes = ['descriptions', 'custom_lists', 'block_layouts'];
    protected $primaryKey = 'custom_block_id';

    protected $casts = [
        'block_id' => 'int'
    ];

    protected $fillable = [
        'block_id'
    ];

    protected $rules = [
        /** @see validate() */
        'block_id' => [
            'checks'   => [
                'int',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Block ID is empty!'],
            ],
        ]
    ];

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    public function descriptions()
    {
        return $this->hasMany(BlockDescription::class, 'custom_block_id');
    }

    public function custom_lists()
    {
        return $this->hasMany(CustomList::class, 'custom_block_id');
    }

    public function block_layouts()
    {
        return $this->hasMany(BlockLayout::class, 'custom_block_id');
    }
}