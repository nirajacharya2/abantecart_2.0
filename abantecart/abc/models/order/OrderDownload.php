<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\catalog\Download;
use abc\models\QueryBuilder;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderDownload
 *
 * @property int $order_download_id
 * @property int $order_id
 * @property int $order_product_id
 * @property string $name
 * @property string $filename
 * @property string $mask
 * @property int $download_id
 * @property int $status
 * @property int $remaining_count
 * @property int $percentage
 * @property \Carbon\Carbon $expire_date
 * @property int $sort_order
 * @property string $activate
 * @property int $activate_order_status_id
 * @property string $attributes_data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Download $download
 * @property Order $order
 * @property OrderProduct $order_product
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads_histories
 *
 * @package abc\models
 */
class OrderDownload extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['history'];

    protected $primaryKey = 'order_download_id';
    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $casts = [
        'order_id'                 => 'int',
        'order_product_id'         => 'int',
        'download_id'              => 'int',
        'status'                   => 'int',
        'remaining_count'          => 'int',
        'percentage'               => 'int',
        'sort_order'               => 'int',
        'activate_order_status_id' => 'int',
        'attributes_data'          => 'serialized',
    ];

    protected $dates = [
        'expire_date',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'order_product_id',
        'name',
        'filename',
        'mask',
        'download_id',
        'status',
        'remaining_count',
        'percentage',
        'expire_date',
        'sort_order',
        'activate',
        'activate_order_status_id',
        'attributes_data',
    ];

    protected $rules = [
        /** @see validate() */
        'order_id'         => [
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
        'order_product_id' => [
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
        'name'             => [
            'checks'   => [
                'string',
                'max:64',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'filename'         => [
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
        'mask'             => [
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
        'download_id'      => [
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
        'status'           => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be an integer!',
                ],
            ],
        ],
        'remaining_count'  => [
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
        'percentage'       => [
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
        'expire_date'      => [
            'checks'   => [
                'date',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a date!',
                ],
            ],
        ],
        'sort_order'       => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be an integer!',
                ],
            ],
        ],

        'activate'                 => [
            'checks'   => [
                'string',
                'max:64',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'activate_order_status_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:order_statuses,order_status_id',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in order_statuses table!',
                ],
            ],
        ],
    ];


    public function setDownloadIdAttribute($value)
    {
        $this->attributes['download_id'] = empty($value) ? null : (int)$value;
    }
    public function setAttributesDataAttribute($value)
    {
        $this->attributes['attributes_data'] = serialize($value);
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

    public function history()
    {
        return $this->hasMany(OrderDownloadsHistory::class, 'order_download_id');
    }

    /**
     * @param int $order_id
     *
     * @return array
     * @throws \Exception
     */
    public static function getOrderDownloads($order_id)
    {
        /**
         * @var QueryBuilder $query
         */
        $query = OrderDownload::select(
            [
                'order_downloads.*',
                'order_products.*',
                'order_products.name AS product_name',
            ]
        )
                              ->leftJoin(
                                  'order_products',
                                  'order_products.order_product_id',
                                  '=',
                                  'order_downloads.order_product_id'
                              )
                              ->where('order_downloads.order_id', '=', $order_id)
                              ->orderBy('order_products.order_product_id', 'ASC')
                              ->orderBy('order_downloads.sort_order', 'ASC')
                              ->orderBy('order_downloads.name', 'ASC')
                              ->get()
                              ->toArray();

        $output = [];
        foreach ($query as $row) {
            $output[$row['product_id']]['product_name'] = $row['product_name'];
            // get download_history
            $row['download_history'] = OrderDownload::where(
                [
                    'order_id'          => $order_id,
                    'order_download_id' => $row['order_download_id'],
                ]
            )->get()->toArray();

            $output[$row['product_id']]['downloads'][] = $row;
        }

        return $output;
    }
}
