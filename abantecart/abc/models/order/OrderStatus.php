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

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\models\BaseModel;
use abc\models\QueryBuilder;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class OrderStatus
 *
 * @property int $order_status_id
 * @property string $status_text_id
 * @property int $display_status
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $order_histories
 * @property Collection $descriptions
 * @property OrderStatusDescription $description
 * @property Order $orders
 *
 * @method static OrderStatus find(int $order_status_id) OrderStatus
 *
 * @package abc\models
 */
class OrderStatus extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $primaryKey = 'order_status_id';
    protected $cascadeDeletes = ['descriptions'];

    protected $casts = [
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'status_text_id',
        'display_status',
    ];

    protected $rules = [

        'status_text_id' => [
            'checks'   => [
                'string',
                'max:64',
                'sometimes',
                'required',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_status_text_id',
                    'language_block' => 'localisation/order_status',
                    'section'        => 'admin',
                    'default_text'   => ':attribute must be string 64 characters length!',
                ],
            ],
        ],
        'display_status' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Display Status must be 1 or 0 !',
                ],
            ],
        ],
    ];

    /** Wrap basic method to implement conditional rules
     *
     * @param array $data
     * @param array $messages
     * @param array $customAttributes
     *
     * @throws ValidationException
     * @throws ReflectionException
     * @throws AException|InvalidArgumentException
     */
    public function validate(array $data = [], array $messages = [], array $customAttributes = [])
    {
        $rule = Rule::unique('order_statuses', 'status_text_id');
        if ($this->order_status_id) {
            $rule->ignore($this->order_status_id, 'order_status_id');
        }
        $this->rules['status_text_id']['checks'][] = $rule;

        parent::validate($data, $messages, $customAttributes);
    }

    public function descriptions()
    {
        return $this->hasMany(OrderStatusDescription::class, 'order_status_id');
    }

    public function description()
    {
        return $this->hasOne(OrderStatusDescription::class, 'order_status_id')
                    ->where('language_id', '=', static::$current_language_id);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id');
    }

    /**
     * @param string|null $status_text_id
     *
     * @return array
     */
    public static function getOrderStatusConfig(string $status_text_id = null)
    {

        $orderStatus = OrderStatus::with('description')->get()->toArray();
        $conf = ABC::env('ORDER')['statuses'];
        foreach ($orderStatus as &$item) {
            $item['config'] = $conf[$item['status_text_id']];
            if ($item['status_text_id'] == $status_text_id) {
                return $item;
            }
        }
        return $orderStatus;
    }

    /**
     * @param array $inputData
     * @param string $mode - can be empty or "total_only" (for counting rows)
     *
     * @return \Illuminate\Support\Collection|int
     */
    public static function getOrderStatuses($inputData = [], $mode = '')
    {
        $language_id = static::$current_language_id;
        $db = Registry::db();
        $aliasO = $db->table_name('order_statuses');
        $order = new OrderStatus();

        $select = [];
        if ($mode == 'total_only') {
            $select[] = $db->raw('COUNT(*) as total');
        } else {
            $select = [
                'order_status_descriptions.*',
            ];
        }

        /**
         * @var QueryBuilder $query
         */
        if ($mode != 'total_only') {
            $query = $order->selectRaw($db->raw_sql_row_count().' '.$aliasO.'.*');
        } else {
            $query = $order->select();
        }
        $query->addSelect($select);

        $query->leftJoin(
            'order_status_descriptions',
            'order_status_descriptions.order_status_id',
            '=',
            'order_statuses.order_status_id'
        );
        $query->where('order_status_descriptions.language_id', '=', $language_id);

        //If for total, we'done building the query
        if ($mode == 'total_only') {
            //allow to extend this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $inputData);
            $result = $query->first();
            return (int)$result->total;
        }

        $sort_data = [
            'order_status_id' => 'order_statuses.order_id',
            'name'            => 'order_status_descriptions.name',
            'display_status'  => 'order_statuses.display_status',
            'date_added'      => 'order_statuses.date_added',
        ];

        // NOTE: Performance slowdown might be noticed or larger search results

        $orderBy = $sort_data[$inputData['sort']] ? : 'name';
        if (isset($inputData['order']) && (strtoupper($inputData['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }

        $query->orderBy($orderBy, $sorting);
        if (isset($inputData['start']) || isset($inputData['limit'])) {
            if ($inputData['start'] < 0) {
                $inputData['start'] = 0;
            }
            if ($inputData['limit'] < 1) {
                $inputData['limit'] = 20;
            }
            $query->offset((int)$inputData['start'])->limit((int)$inputData['limit']);
        }

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $inputData);
        $query->useCache('order_status');
        return $query->get();
    }
}