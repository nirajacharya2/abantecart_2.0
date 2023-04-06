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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BlockTemplate
 *
 * @property int $block_id
 * @property int $parent_block_id
 * @property string $template
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 *
 * @package abc\models
 */
class BlockTemplate extends BaseModel
{
    protected $primaryKeySet = ['block_id', 'parent_block_id'];

    protected $casts = [
        'block_id'        => 'int',
        'parent_block_id' => 'int',
        'template'        => 'string'
    ];

    protected $fillable = [
        'block_id',
        'parent_block_id',
        'template'
    ];

    protected $rules = [
        /** @see validate() */
        'block_id'        => [
            'checks'   => [
                'int',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Block ID is empty!'],
            ],
        ],
        'parent_block_id' => [
            'checks'   => [
                'int'
            ],
            'messages' => [
                '*' => ['default_text' => 'Parent Block ID is not integer!'],
            ],
        ],
        'template'        => [
            'checks'   => [
                'string',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Template Route is empty!'],
            ],
        ]
    ];

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }
}