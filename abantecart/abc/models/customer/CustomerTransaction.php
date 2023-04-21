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
namespace abc\models\customer;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\QueryBuilder;
use Carbon\Carbon;
use Exception;
use H;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Class CustomerTransaction
 *
 * @property int $customer_transaction_id
 * @property int $customer_id
 * @property int $order_id
 * @property int $created_by
 * @property int $section
 * @property float $credit
 * @property float $debit
 * @property string $transaction_type
 * @property string $comment
 * @property string $description
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Customer $customer
 *
 * @method static CustomerTransaction find(int $customer_transaction_id) CustomerTransaction
 * @method static CustomerTransaction UpdateOrCreate(array $data) CustomerTransaction
 * @method static CustomerTransaction create(array $data) CustomerTransaction
 * @method static QueryBuilder where(mixed $conditions, string $condition = null, mixed $value = null)
 * @method static QueryBuilder select(mixed $fields)
 * @method static CustomerTransaction firstOrCreate(array $attributes, array $values = []) QueryBuilder
 *
 * @package abc\models
 */
class CustomerTransaction extends BaseModel
{
    public $restrictUpdate = true;

    protected $primaryKey = 'customer_transaction_id';

    protected $mainClassName = Customer::class;
    protected $mainClassKey = 'customer_id';

    protected $casts = [
        'customer_id'      => 'int',
        'order_id'         => 'int',
        'created_by'       => 'int',
        'section'          => 'int',
        'credit'           => 'float',
        'debit'            => 'float',
        'transaction_type' => 'string',
        'comment'          => 'string',
        'description'      => 'string',
        'date_added'       => 'datetime',
        'date_modified'    => 'datetime'
    ];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'customer_transaction_id',
        'customer_id',
        'order_id',
        'created_by',
        'section',
        'credit',
        'debit',
        'transaction_type',
        'comment',
        'description',
        'date_added',
        'date_modified',
        'stage_id',
    ];

    protected $touches = ['customer'];

    protected $rules = [
        'customer_transaction_id' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                'integer' => [
                    'default_text' => 'Transaction ID must be an integer!',
                ],
            ],
        ],
        'customer_id'             => [
            'checks'   => [
                'integer',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Customer ID must be an integer!',
                ],
            ],
        ],
        'order_id'                => [
            'checks'   => [
                'integer',
                'nullable',
            ],
            'messages' => [
                'integer' => [
                    'default_text' => 'Order ID must be an integer or Null!',
                ],
            ],
        ],
        'created_by'              => [
            'checks'   => [
                'integer',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'User ID (:attribute) who creates transaction must be an integer!',
                ],
            ],
        ],
        'section'                 => [
            'checks'   => [
                'integer',
                'in:0,1',
            ],
            'messages' => [
                'integer' => [
                    'default_text' => ':attribute must be 1(admin) or 0 (storefront)!',
                ],
            ],
        ],
        'credit'                  => [
            'checks'   => [
                'numeric',
                'max:99999999999.9999',
                'required_without:debit',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_incorrect_debit_credit',
                    'language_block' => 'sale/customer',
                    'default_text'   => ':attribute value must be numeric less than 99 999 999 999.9999',
                    'section'        => 'admin',
                ],
            ],
        ],
        'debit'                   => [
            'checks'   => [
                'numeric',
                'max:99999999999.9999',
                'required_without:credit',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_incorrect_debit_credit',
                    'language_block' => 'sale/customer',
                    'default_text'   => ':attribute value must be numeric less than 99 999 999 999.9999',
                    'section'        => 'admin',
                ],
            ],
        ],
        'transaction_type'        => [
            'checks'   => [
                'string',
                'max:255',
                'required',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_transaction_type',
                    'language_block' => 'sale/customer',
                    'default_text'   => 'Please fill transaction type form field.',
                    'section'        => 'admin',
                ],
            ],
        ],
        'comment'                 => [
            'checks'   => [
                'string',
                'max:1500',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Comment must be less than 1500 characters!',
                ],
            ],
        ],
        'description'             => [
            'checks'   => [
                'string',
                'max:1500',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Description must be less than 1500 characters!',
                ],
            ],
        ],

    ];

    /**
     * Forbid to update transactions. To change balance use adding of new transaction
     *
     * @param array $options
     *
     * @return bool
     * @throws Exception
     */
    public function save(array $options = [])
    {
        if ($this->exists && $this->restrictUpdate) {
            //you cannot to update transaction!
            //All records must be incremental!
            //INSERTS ONLY!
            $dbg = debug_backtrace();
            $debug = '';
            foreach ($dbg as $k => $d) {
                $debug .= '#' . $k . ' ' . $d['file'] . ":" . $d['line'] . "\n";
            }
            Registry::log()->error(
                "Customer " . $this->customer_id . " attempts to update transaction # " . $this->customer_transaction_id
                . ". Action is prohibited. "
                . "\n Input Data: " . var_export($this->getAttributes(), true) . "backtrace: \n" . $debug
            );
            return true;
        }
        parent::save($options);
        return true;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public static function getBalance(int $customer_id, $update = false)
    {
        $customer = Customer::find($customer_id);
        if(!$customer){
            return null;
        }
        $customer->running_balance_datetime = $customer->running_balance_datetime ?: '1970-01-01';
        if($customer->running_balance_datetime == '1970-01-01'){
            $customer->running_balance = 0;
        }

        $customerTransaction = new static;
        $transTable = $customerTransaction->getConnection()->getTablePrefix().$customerTransaction->getTable();
        $now = date('Y-m-d H:i:s');
        // do not allow additional filters here! globalScopes must be disabled here
        $query = static::withoutGlobalScopes()
            ->selectRaw('sum(credit) - sum(debit) as balance')
            ->selectRaw('sum(credit) as credit')
            ->selectRaw('sum(debit) as debit')
            ->where('customer_id', '=', $customer_id)
            ->whereRaw( $transTable.".date_added > '".$customer->running_balance_datetime."' AND ".$transTable.".date_added < '".$now."'" )
            ->first();
        return $query->balance + $customer->running_balance;
    }

    /**
     * @param $data
     * @param string $mode
     *
     * @return Collection | integer
     */
    public static function getTransactions($data, $mode = 'default')
    {

        $db = Registry::db();

        $aliasCu = $db->table_name('customers');
        $aliasC = $db->table_name('customer_transactions');
        $aliasU = $db->table_name('users');

        $rawInc = "CASE WHEN ".$aliasC.".section = 1
                        THEN CONCAT(".$aliasCu.".firstname,' ',".$aliasCu.".lastname, ' (',".$aliasCu.".loginname,')') 
                    ELSE
                        CONCAT(".$aliasU.".firstname,' ',".$aliasU.".lastname, ' (',".$aliasU.".username,')')
                    END";

        $select = [];
        if ($mode == 'total_only') {
            $select[] = $db->raw('COUNT(*) as total');
        } else {
            $select = [
                $db->raw($rawInc." as user")
            ];
        }

        $query = CustomerTransaction::selectRaw($db->raw_sql_row_count()." ".$db->table_name('customer_transactions').".*")
                ->addSelect($select)
                ->leftJoin(
                      'users',
                      'users.user_id',
                      '=',
                      'customer_transactions.created_by'
                  )
                ->leftJoin(
                      'customers',
                      'customers.customer_id',
                      '=',
                      'customer_transactions.created_by'
                  );
        if($data['customer_id']) {
            $query->where(['customer_transactions.customer_id' => (int) $data['customer_id']]);
        }

        $filter = $data['filter'] ?? [];

        if (H::has_value($filter['date_start']) && H::has_value($filter['date_end'])) {
            $query->whereRaw($aliasC . ".date_added 
                                BETWEEN '" . $db->escape($filter['date_start']) . "' 
                                    AND '" . $db->escape($filter['date_end']) . "'");
        }
        if (H::has_value($filter['debit'])) {
            $query->whereRaw( "ROUND(".$aliasC.".debit,2) = '".round((float)$filter['debit'], 2)."'" );
        }

        if (H::has_value($filter['credit'])) {
            $query->whereRaw( "ROUND(".$aliasC.".credit,2) = '".round((float)$filter['credit'], 2)."'" );
        }
        if (H::has_value($filter['transaction_type'])) {
            $query->whereRaw( $aliasC.".transaction_type LIKE '%".$db->escape($filter['transaction_type'])."%'");
        }

        if (H::has_value($filter['description'])) {
            $query->whereRaw( $aliasC.".description LIKE '%".$db->escape($filter['description'])."%'");
        }

        if (H::has_value($filter['user'])) {
            $query->whereRaw( "LOWER(".$rawInc.") like '%".mb_strtolower($db->escape($filter['user']))."%'");
        }

        //If for total, we're done building the query
        if ($mode == 'total_only') {
            //allow to extend this method from extensions
            Registry::extensions()->hk_extendQuery(new static,__FUNCTION__, $query, $data);
            $result = $query->first();
            return (int)$result->total;
        }

        $sort_data = [
            'date_added' => 'customer_transactions.date_added',
            'user' => 'user',
            'debit' => 'customer_transactions.debit',
            'credit' => 'customer_transactions.credit',
            'transaction_type' => 'customer_transactions.transaction_type',
        ];

         // NOTE: Performance slowdown might be noticed or larger search results

         $orderBy = $sort_data[$data['sort']] ? : 'customer_transactions.date_added';
         if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
             $sorting = "desc";
         } else {
             $sorting = "asc";
         }

         $query->orderBy( $orderBy, $sorting);
         if (isset($data['start']) || isset($data['limit'])) {
             if ($data['start'] < 0) {
                 $data['start'] = 0;
             }
             if ($data['limit'] < 1) {
                 $data['limit'] = 20;
             }
             $query->offset((int)$data['start'])->limit((int)$data['limit']);
         }

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $data);
        $query->useCache('customer');
        return $query->get();
    }

    /**
     * @return Collection
     */
    public static function getTransactionTypes()
    {
        /** @var QueryBuilder $query */
        $query = self::select(['transaction_type'])
            ->distinct(['transaction_type'])
            ->orderBy('transaction_type')
            ->useCache('customer');
        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static,__FUNCTION__, $query);
        return $query->get();
    }

}
