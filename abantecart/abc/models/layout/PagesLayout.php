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

/**
 * Class PagesLayout
 *
 * @property int $layout_id
 * @property int $page_id
 *
 * @property Layout $layout
 * @property Page $page
 *
 */
class PagesLayout extends BaseModel
{
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'page_id',
        'layout_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'layout_id' => 'int',
        'page_id'   => 'int',
    ];
    protected $fillable = [
        'layout_id',
        'page_id',
    ];

    protected $rules = [
        /** @see validate() */
        'layout_id' => [
            'checks'   => [
                'integer',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Layout ID is empty!'],
            ],
        ],
        'page_id'   => [
            'checks'   => [
                'integer',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Page ID is empty!'],
            ],
        ]
    ];

    public function layout()
    {
        return $this->belongsTo(Layout::class, 'layout_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}