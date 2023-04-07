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

/**
 * Class BlockLayout
 *
 * @property int $instance_id
 * @property int $layout_id
 * @property int $block_id
 * @property int $custom_block_id
 * @property int $parent_instance_id
 * @property int $position
 * @property int $status
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @package abc\models
 */
class BlockLayout extends BaseModel
{

    protected $primaryKey = 'instance_id';

    protected $casts = [
        'layout_id'          => 'int',
        'block_id'           => 'int',
        'custom_block_id'    => 'int',
        'parent_instance_id' => 'int',
        'position'           => 'int',
        'status'             => 'boolean'
    ];

    protected $fillable = [
        'layout_id',
        'block_id',
        'custom_block_id',
        'parent_instance_id',
        'position',
        'status'
    ];
    protected $rules = [
        /** @see validate() */
        'layout_id'          => [
            'checks'   => [
                'int',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Layout ID is empty!'],
            ],
        ],
        'block_id'           => [
            'checks'   => [
                'int',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Block ID is empty!'],
            ],
        ],
        'custom_block_id'    => [
            'checks'   => [
                'int',
            ],
            'messages' => [
                '*' => ['default_text' => 'Custom Block ID is empty!'],
            ],
        ],
        'parent_instance_id' => [
            'checks'   => [
                'int',
            ],
            'messages' => [
                '*' => ['default_text' => 'Parent Instance ID is empty!'],
            ],
        ],
        'position'           => [
            'checks'   => [
                'int',
            ],
            'messages' => [
                '*' => ['default_text' => 'Position is not integer!'],
            ],
        ],
        'status'             => [
            'checks'   => [
                'bool',
            ],
            'messages' => [
                '*' => ['default_text' => 'Status is not integer!'],
            ],
        ],
    ];

    public function children()
    {
        return $this->HasMany(BlockLayout::class, 'instance_id', 'parent_instance_id');
    }

    public function layout()
    {
        return $this->belongsTo(Layout::class, 'layout_id');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    public function custom_block()
    {
        return $this->belongsTo(CustomBlock::class, 'custom_block_id');
    }

}
