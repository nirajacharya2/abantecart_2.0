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
namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\catalog\Download;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderDownloadsHistory
 *
 * @property int $order_download_history_id
 * @property int $order_download_id
 * @property int $order_id
 * @property int $order_product_id
 * @property string $filename
 * @property string $mask
 * @property int $download_id
 * @property int $download_percent
 * @property Carbon $time
 *
 * @property OrderDownload $order_download
 * @property Download $download
 * @property Order $order
 * @property OrderProduct $order_product
 *
 * @package abc\models
 */
class OrderDownloadsHistory extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'order_download_history_id';

    protected $table = 'order_downloads_history';
    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $casts = [
        'order_id'          => 'int',
        'order_download_id' => 'int',
        'order_product_id'  => 'int',
        'download_id'       => 'int',
        'download_percent'  => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'order_download_id',
        'order_product_id',
        'filename',
        'mask',
        'download_id',
        'download_percent',
    ];

    protected $rules = [
        /** @see validate() */
        'order_id'          => [
            'checks'   => [
                'integer',
                'required',
                'exists:orders',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in orders table!',
                ],
            ],
        ],
        'order_download_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:order_downloads',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in order_downloads table!',
                ],
            ],
        ],
        'order_product_id'  => [
            'checks'   => [
                'integer',
                'required',
                'exists:order_products',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in order_products table!',
                ],
            ],
        ],
        'filename'          => [
            'checks'   => [
                'string',
                'max:128',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'mask'              => [
            'checks'   => [
                'string',
                'max:128',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'download_id'       => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:downloads',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be an integer!',
                ],
            ],
        ],
        'download_percent'  => [
            'checks'   => [
                'integer',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be an integer!',
                ],
            ],
        ],
    ];

    public function order_download()
    {
        return $this->belongsTo(OrderDownload::class, 'order_download_id');
    }

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_product()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }
}
