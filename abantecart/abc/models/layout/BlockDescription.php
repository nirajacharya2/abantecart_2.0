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
use abc\models\locale\Language;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BlockDescription
 *
 * @property int $block_description_id
 * @property int $custom_block_id
 * @property int $language_id
 * @property string $block_wrapper
 * @property bool $block_framed
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $content
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property CustomBlock $custom_block
 * @property Language $language
 *
 * @package abc\models
 */
class BlockDescription extends BaseModel
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $primaryKey = 'block_description_id';
    protected $primaryKeySet = [
        'custom_block_id',
        'language_id',
    ];

    protected $casts = [
        'custom_block_id' => 'int',
        'language_id'     => 'int',
        'block_wrapper'   => 'string',
        'block_framed'    => 'bool',
        'name'            => 'string',
        'title'           => 'string',
        'description'     => 'string',
        'content'         => 'string',
    ];

    protected $fillable = [
        'block_wrapper',
        'block_framed',
        'name',
        'title',
        'description',
        'content'
    ];

    protected $rules = [
        /** @see validate() */
        'custom_block_id' => [
            'checks'   => [
                'int',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Custom Block ID is empty!'],
            ],
        ],
        'language_id'     => [
            'checks'   => [
                'int',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not integer!'],
            ],
        ],
        'block_wrapper'   => [
            'checks'   => [
                'string'
            ],
            'messages' => [
                '*' => ['default_text' => 'Block Wrapper (Template Route) is empty!'],
            ],
        ],
        'block_framed'    => [
            'checks'   => [
                'bool'
            ],
            'messages' => [
                '*' => ['default_text' => 'Block Wrapper (Template Route) is empty!'],
            ],
        ]
    ];

    public function custom_block()
    {
        return $this->belongsTo(CustomBlock::class, 'custom_block_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
