<?php

namespace abc\models\customer;

use abc\core\engine\Registry;
use abc\core\lib\ADataEncryption;
use abc\core\lib\ADB;
use abc\models\BaseModel;
use abc\models\order\Order;
use abc\models\order\OrderProduct;
use abc\models\QueryBuilder;
use abc\models\system\Audit;
use abc\models\system\Store;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Class Customer
 *
 * @property int $customer_id
 * @property int $store_id
 * @property string $firstname
 * @property string $lastname
 * @property string $loginname
 * @property string $email
 * @property string $telephone
 * @property string $fax
 * @property string $sms
 * @property string $salt
 * @property string $password
 * @property string $cart
 * @property string $wishlist
 * @property int $newsletter
 * @property int $address_id
 * @property int $status
 * @property int $approved
 * @property int $customer_group_id
 * @property string $ip
 * @property string $data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * @property \Carbon\Carbon $last_login
 *
 * @property Store $store
 * @property \Illuminate\Database\Eloquent\Collection $addresses
 * @property \Illuminate\Database\Eloquent\Collection $customer_notifications
 * @property \Illuminate\Database\Eloquent\Collection $customer_transactions
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @method static Customer find(int $customer_id) Customer
 * @package abc\models
 */
class Customer extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;
    const SUBSCRIBERS_GROUP_NAME = 'Newsletter Subscribers';
    protected $cascadeDeletes = ['addresses', 'notifications', 'transactions'];

    /**
     * @var string
     */
    protected $primaryKey = 'customer_id';
    /**
     * @var bool
     */
    public $timestamps = false;

    protected $casts = [
        'store_id'          => 'int',
        'newsletter'        => 'int',
        'address_id'        => 'int',
        'status'            => 'int',
        'approved'          => 'int',
        'customer_group_id' => 'int',
        'cart'              => 'serialized',
        'data'              => 'serialized',
        'wishlist'          => 'serialized'
    ];

    protected $dates = [
        'date_added',
        'date_modified',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        "customer_id",
        "store_id",
        "firstname",
        "lastname",
        "loginname",
        "email",
        "telephone",
        "fax",
        "sms",
        "salt",
        "password",
        "cart",
        "wishlist",
        "newsletter",
        "address_id",
        "status",
        "advanced_status",
        "approved",
        "customer_group_id",
        "ip",
        "data",
        "stage_id",
        "last_login",
        "date_deleted"
    ];

    protected $rules = [
        'customer_id'       => [
                                'checks' => [
                                            'integer'
                                            ],
                                'messages' => [
                                    '*' => [ 'default_text' => 'Customer ID is not Integer!' ]
                                ]
                    ],
        'store_id'          => [
                    'checks'   => [
                        'integer',
                        //required only when new customer creating
                        'required_without:customer_id',
                    ],
                    'messages' => [
                        'integer'                  => [
                            'default_text'   => 'Store ID must be an integer!'
                        ],
                        'required_without:customer_id' => [
                            'default_text'   => 'Store ID required.'
                        ],
                    ],
                ],

        'loginname' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:5,96',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_loginname',
                    'language_block' => 'account/create',
                    'default_text'   => 'Login name must be alphanumeric only and between 5 and 96 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'firstname' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:1,32',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_firstname',
                    'language_block' => 'account/create',
                    'default_text'   => 'First Name must be between 1 and 32 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'lastname' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:1,32',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_lastname',
                    'language_block' => 'account/create',
                    'default_text'   => 'Last Name must be between 1 and 32 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'email' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'max:96',
                'regex:/^[A-Z0-9._%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i'
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_email',
                    'language_block' => 'account/create',
                    'default_text'   => 'Address must be between 3 and 128 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'telephone' => [
            'checks'   => [
                'string',
                'max:32'
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_telephone',
                    'language_block' => 'account/create',
                    'default_text'   => 'Telephone number must be less than 32 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'fax' => [
            'checks'   => [
                'string',
                'max:32'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Fax number must be less than 32 characters!',
                ],
            ],
        ],

        'sms' => [
            'checks'   => [
                'string',
                'max:32'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Mobile phone number must be less than 32 characters!',
                ],
            ],
        ],

        'salt' => [
            'checks'   => [
                'string',
                'max:10'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Salt must be less than 8 characters!',
                ],
            ],
        ],

        'password' => [
            'checks'   => [
                'string',
                'required_without:customer_id',
                'required_with:password_confirmation',
                'same:password_confirmation',
                'between:3,40',
            ],
            'messages' => [
                'same' => [
                    'language_key'   => 'error_confirm',
                    'language_block' => 'account/create',
                    'default_text'   => 'Password confirmation does not match password!',
                    'section'        => 'storefront',
                ],
                '*' => [
                    'language_key'   => 'error_password',
                    'language_block' => 'account/create',
                    'default_text'   => 'Password must be between 4 and 20 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'password_confirmation' => [
                    'checks'   => [
                        'string',
                        'required_without:customer_id',
                        'between:3,40',
                    ],
                    'messages' => [
                        '*' => [
                            'language_key'   => 'error_confirm',
                            'language_block' => 'account/create',
                            'default_text'   => 'Password confirmation does not match password!',
                            'section'        => 'storefront',
                        ],
                    ],
                ],

        'wishlist' => [
            'checks'   => [
                'string',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Wishlist must be a string!',
                ],
            ],
        ],

        'address_id' => [
            'checks'   => [
                'integer',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Address ID must be an integer!',
                ],
            ],
        ],

        'status' => [
            'checks'   => [
                'integer',
                'digits:1'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Status must be 1 or 0 !',
                ],
            ],
        ],

        'advanced_status' => [
            'checks'   => [
                'string',
                'max:128'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Advanced Status must be a string and less than 128 characters!',
                ],
            ],
        ],

        'approved' => [
            'checks'   => [
                'integer',
                'digits:1'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => '"Approved" must be 1 or 0 !',
                ],
            ],
        ],

        'customer_group_id' => [
            'checks'   => [
                'integer',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Customer Group ID must be an integer or NULL !',
                ],
            ],
        ],

        'ip' => [
            'checks'   => [
                'string',
                'max:50'
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'IP-address must be a less that 50 characters!',
                ],
            ],
        ],

    ];

    /** Wrap basic method to implement conditional rules
     * @param array $data
     * @param array $messages
     * @param array $customAttributes
     *
     * @return bool|void
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function validate( array $data= [], array $messages = [], array $customAttributes = [])
    {
        if(Registry::config()->get( 'prevent_email_as_login' )) {
            $this->rules['loginname']['checks'][] = 'regex:/^[\w._-]+$/i';
        }
        //we cannot to define rule as function in the class body.
        //so, adding validation rule for uniqueness here
        $this->rules['loginname']['checks'][] = Rule::unique('customers', 'loginname')->ignore($this->customer_id, 'customer_id');
        $this->rules['email']['checks'][] = Rule::unique('customers', 'email')->ignore($this->customer_id, 'customer_id');
        parent::validate($data, $messages, $customAttributes);
    }

    public function getFields()
    {
        return [
            'store_id' => [
                'rule' => 'required',
                'input_type' => 'selectbox'
            ],
            'firstname' => [
                'rule' => 'required_with:lastname|max:32',
                'input_type' => 'input',
            ],
            'lastname' => [
                'rule' => 'required_with:firstname|max:32',
                'input_type' => 'input',
            ],
            'loginname' => [
                'rule' => 'unique|max:36|nullable',
                'input_type' => 'input',
            ],
            'email' => [
                'rule' => 'required|email|unique|max:96',
                'input_type' => 'input',
            ],
            'telephone' => [
                'rule' => 'max:32|nullable',
                'input_type' => 'input',
            ],
            'fax' => [
                'rule' => 'max:32|nullable',
                'input_type' => 'input',
            ],
            'sms' => [
                'rule' => 'max:32|nullable',
                'input_type' => 'input',
            ],
            'salt' => [
                'rule' => 'max:8|nullable',
                'input_type' => 'none',
                'access' => 'read'
            ],
            'password' => [
                'rule' => 'max:40',
                'input_type' => 'password',
                'access' => 'read'
            ],
            'cart' => [
                'rule' => 'json|nullable',
                'input_type' => 'none',
                'access' => 'read'
            ],
            'wishlist' => [
                'rule' => 'json|nullable',
                'input_type' => 'none',
                'access' => 'read'
            ],
            'newsletter' => [
                'rule' => 'boolean|nullable',
                'input_type' => 'checkbox',
            ],
            'address_id' => [
                'rule' => 'integer|nullable',
                'input_type' => 'none',
            ],
            'status' => [
                'rule' => 'boolean|nullable',
                'input_type' => 'checkbox',
            ],
            'approved' => [
                'rule' => 'boolean|nullable',
                'input_type' => 'checkbox',
            ],
            'customer_group_id' => [
                'rule' => 'integer|nullable',
                'input_type' => 'none',
            ],
            'ip' => [
                'rule' => 'ip|nullable',
                'input_type' => 'input',
                'access' => 'read'
            ],
            'data' => [
                'rule' => 'json|nullable',
                'input_type' => 'none',
                'access' => 'read'
            ],
            'date_added' => [
                'rule' => 'date',
                'input_type' => 'none',
                'access' => 'read'
            ],
            'date_modified' => [
                'rule' => 'date',
                'input_type' => 'none',
                'access' => 'read'
            ],
            'last_login' => [
                'rule' => 'date|nullable',
                'input_type' => 'none',
                'access' => 'read'
            ],
        ];
    }


    public function setDataAttribute($value){
        $this->attributes['data'] = serialize($value);
    }
    public function setCartAttribute($value){
        $this->attributes['cart'] = serialize($value);
    }
    public function setWishlistAttribute($value){
        $this->attributes['wishlist'] = serialize($value);
    }

    public function setPasswordAttribute($password){
        if (! $this->originalIsEquivalent('password', $password)) {
            $salt_key = H::genToken(8);
            $this->fill(['salt' => $salt_key]);
            $this->attributes['password'] = H::getHash($password, $salt_key);
        }
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws \abc\core\lib\AException
     */
    public function save($options = [])
    {
        $inserting = !($this->customer_id);
        $data = $this->attributes;

        //remove serialized fields
        foreach($this->casts as $k=>$v){
            if($v == 'serialized'){
                unset($data[$k]);
            }
        }
        //prevent double mutation
        foreach($data as $k=>$v){
            if(method_exists($this,'Set'.ucfirst($k).'Attribute')){
                unset($data[$k]);
            }
        }

        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();
        if ($dcrypt->active) {
            $data = $dcrypt->encrypt_data($data, 'customers');
        }

        $this->fill($data);
        $result = parent::save($options);
        if (isset($data['newsletter']) && $inserting) {
            //enable notification setting for newsletter via email
            $this->saveCustomerNotificationSettings(['newsletter' => ['email' => (int)$data['newsletter']]]);
        }

        return $result;
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function customer_group()
    {
        return $this->HasOne(CustomerGroup::class, 'customer_group_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(CustomerNotification::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'user');
    }

    /**
     * @throws \Exception
     */
    public function approve()
    {
        if (!$this->hasPermission('write', ['approved'])) {
            throw new \Exception('Permissions are restricted '.__CLASS__."::".__METHOD__."\n");
        }
        $this->approved = 1;
        $this->save();
    }

    /**
     * @return bool
     */
    public function isSubscriber()
    {
        $name = $this->customer_group()->where('customer_group_id', '=', $this->customer_group_id)->first()->name;
        return ($name == self::SUBSCRIBERS_GROUP_NAME);
    }

    /**
     * Function returns parsed customers data as array
     *
     * @param $customer_id
     *
     * @param string $mode - can be quick(without orders_count), default, total_only(returns row count)
     *
     * @return array|\Illuminate\Support\Collection|int
     * @throws \abc\core\lib\AException
     */
    public static function getCustomer($customer_id, $mode = 'quick'){
        $customer_id = (int)$customer_id;
        if(!$customer_id){
             return [];
        }
        $result = static::getCustomers(['filter' => ['include' => [$customer_id]]], $mode);
        return $result[0];
    }

    /**
     * @param array $data
     * @param string $mode
     *
     * @return Collection|int
     * @throws \abc\core\lib\AException
     */
    public static function getCustomers($data = [], $mode = 'quick')
    {
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();
        /**
         * @var ADB $db
         */
        $db = Registry::db();
        $customer = new Customer();

        $aliasC = $db->table_name('customers');

        $select = [];
        if ($mode == 'total_only' && !$dcrypt->active) {
            $select[] = $db->raw('COUNT(*) as total');
        } else {
            $select = [
                "customers.*",
                $db->raw('CONCAT('.$aliasC.'.firstname, \' \', '.$aliasC.'.lastname) AS name'),
                'customer_groups.name AS customer_group'
            ];
        }

        if ($mode != 'total_only' && $mode != 'quick') {
            $select[] = $db->raw('(SELECT COUNT(o.order_id) as cnt 
                                    FROM '.$db->table_name("orders").' o
                                    WHERE '.$aliasC.'.customer_id = o.customer_id AND o.order_status_id>0) 
                                   as orders_count');
        }

        if ($dcrypt->active) {
            $select[] = 'customers.key_id';
        }

        /**
         * @var QueryBuilder $query
         */
        $query = $customer->select($db->raw_sql_row_count())
                          ->select($select)
                          ->join(
                              'customer_groups',
                              'customer_groups.customer_group_id',
                              '=',
                              'customers.customer_group_id'
                          );

        $filter = (isset($data['filter']) ? $data['filter'] : []);

        if (H::has_value($filter['name'])) {
            $query->whereRaw("CONCAT(".$aliasC.".firstname, ' ', ".$aliasC.".lastname) LIKE '%".$filter['name']."%'");
        }

        if (H::has_value($filter['name_email'])) {
            $query->whereRaw("CONCAT(".$aliasC.".firstname, ' ', ".$aliasC.".lastname, ' ', ".$aliasC.".email) LIKE '%".$filter['name_email']."%'");
        }
        //more specific login, last and first name search
        if (H::has_value($filter['loginname'])) {
            if($filter['search_operator'] == 'equal'){
                $query->whereRaw("LOWER(".$aliasC.".loginname) =  '".mb_strtolower($filter['loginname'])."'");
            }else {
                $query->whereRaw("LOWER(".$aliasC.".loginname) LIKE '".mb_strtolower($filter['loginname'])."%'");
            }
        }

        if (H::has_value($filter['firstname'])) {
            if($filter['search_operator'] == 'equal') {
                $query->whereRaw("LOWER(".$aliasC.".firstname) =  '".mb_strtolower($filter['firstname'])."'");
            }else {
                $query->whereRaw("LOWER(".$aliasC.".firstname) LIKE '".mb_strtolower($filter['firstname'])."%'");
            }
        }

        if (H::has_value($filter['lastname'])) {
            if($filter['search_operator'] == 'equal') {
                $query->whereRaw("LOWER(".$aliasC.".lastname) =  '".mb_strtolower($filter['lastname'])."'");
            }else{
                $query->whereRaw("LOWER(".$aliasC.".lastname) LIKE '".mb_strtolower($filter['lastname'])."%'");
            }
        }

        if (H::has_value($filter['password'])) {
            $query->whereRaw($aliasC.".password = SHA1(CONCAT(".$aliasC.".salt, SHA1(CONCAT(".$aliasC.".salt, SHA1('".$db->escape($filter['password'])."')))))");
        }

        //select differently if encrypted
        if (!$dcrypt->active) {
            if (H::has_value($filter['email'])) {
                $emails = (array)$filter['email'];
                $query->orWhere(function($query) use ($emails, $filter){
                    foreach($emails as $email) {
                        if($filter['search_operator'] == 'equal') {
                            $query->orWhere('customers.email', '=', mb_strtolower($email));
                        }else {
                            $query->orWhere('customers.email', 'like', "%".mb_strtolower($email)."%");
                        }
                    }
                });
            }

            if (H::has_value($filter['telephone'])) {
                $query->where('customers.telephone', 'like', "%".$filter['telephone']."%");
            }
            if (H::has_value($filter['sms'])) {
                $query->where('customers.sms', 'like', "%".$filter['sms']."%");
            }
        }

        if (H::has_value($filter['customer_group_id'])) {
            $query->where('customer_groups.customer_group_id', '=', $filter['customer_group_id']);
        }
        // select only subscribers (group + customers with subscription)
        $subscriberGroupId = CustomerGroup::where('name', '=', self::SUBSCRIBERS_GROUP_NAME)->first()->customer_group_id;

        if (H::has_value($filter['only_subscribers'])) {
            $query->where(function($query) use ($subscriberGroupId){
                $query->where('customer_groups.customer_group_id', '=', $subscriberGroupId);
            });
        } elseif (H::has_value($filter['all_subscribers'])) {
            $query->where(function($query){
                             $query->where('customers.newsletter', '=', 1)
                                   ->where('customers.status', '=', 1)
                                   ->where('customers.approved', '=', 1);
                         })
            ->orWhere(function($query) use ($subscriberGroupId){
                             $query->where('customers.newsletter', '=', 1)
                                   ->where('customer_groups.customer_group_id', '=', $subscriberGroupId);
                         });
        }
        // select only customers without newsletter subscribers
        elseif (H::has_value($filter['only_customers'])) {
            $query->where('customer_groups.customer_group_id', '<>', $subscriberGroupId);
        }

        if (H::has_value($filter['only_with_mobile_phones'])) {
            $query->whereRaw("TRIM(COALESCE(".$aliasC.".sms,'')) <> ''");
        }

        //include ids set
        if (H::has_value($filter['include'])) {
            $filter['include'] = (array)$filter['include'];
            foreach($filter['include'] as &$id){
                $id = (int)$id;
            }
            $query->whereIn('customers.customer_id', $filter['include']);
        }
        //exclude already selected in chosen element
        if (H::has_value($filter['exclude'])) {
            $filter['exclude'] = (array)$filter['exclude'];
            foreach($filter['exclude'] as &$id){
                $id = (int)$id;
            }
            $query->whereNotIn('customers.customer_id', $filter['exclude']);
        }

        if (H::has_value($filter['status'])) {
            $query->where('customers.status', '=', (int)$filter['status']);
        }

        if (H::has_value($filter['approved'])) {
            $query->where('customers.approved', '=', (int)$filter['approved']);
        }

        if (H::has_value($filter['date_added'])) {
            $query->whereRaw("DATE(".$aliasC.".date_added) = DATE('".$db->escape($filter['date_added'])."')");
        }

        if ($data['store_id'] !== null) {
            $query->where('customers.store_id', '=', (int)$data['store_id']);
        }

        if (($filter['all_subscribers'] || $filter['only_subscribers']) && $filter['newsletter_protocol']) {
            $query->join('customer_notifications',
                function ($join) use($filter){
                    $join->on('customer_notifications.customer_id', '=', 'customers.customer_id')
                         ->on('customer_notifications.sendpoint', '=', 'newsletter');
                });
            $query->where('customer_notifications.status', '=', 1)
                  ->where('customer_notifications.protocol', '=', $filter['newsletter_protocol']);
        }

        //If for total, we done building the query
        if ($mode == 'total_only' && !$dcrypt->active) {
            $result = $query->first();
            return (int)$result->total;
        }

        $sort_data = [
            'name'           => 'name',
            'loginname'      => 'customers.loginname',
            'lastname'       => 'customers.lastname',
            'email'          => 'customers.email',
            'sms'            => 'customers.sms',
            'customer_group' => 'customer_group',
            'status'         => 'customers.status',
            'approved'       => 'customers.approved',
            'date_added'     => 'customers.date_added',
        ];

        if($mode != 'quick'){
            $sort_data['orders_count'] = 'orders_count';
        }

        //Total calculation for encrypted mode
        // NOTE: Performance slowdown might be noticed or larger search results
        if ($mode != 'total_only') {
            $orderBy = $sort_data[$data['sort']] ? $sort_data[$data['sort']] : 'name';
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
        }
        $result_rows = $query->get();

//???? TODO need to check when encrypted
        if ($result_rows->count() &&  $dcrypt->active) {

            if (H::has_value($filter['email'])) {
                $result_rows = H::filterByEncryptedField($result_rows->toArray(), 'email', $filter['email']);
            }
            if (H::has_value($filter['telephone'])) {
                $result_rows = H::filterByEncryptedField($result_rows->toArray(), 'telephone', $filter['telephone']);
            }
            if (H::has_value($filter['sms'])) {
                $result_rows = H::filterByEncryptedField($result_rows->toArray(), 'sms', $filter['sms']);
            }
        }

        if ($mode == 'total_only') {
            //we get here only if in data encryption mode
            return $result_rows->count();
        }
        //finally decrypt data and return result
        $totalNumRows = $db->sql_get_row_count();
        for ($i = 0; $i < $result_rows->count(); $i++) {
            $result_rows[$i] = $dcrypt->decrypt_data($result_rows[$i], 'customers');
            $result_rows[$i]['total_num_rows'] = $totalNumRows;
        }

        return $result_rows;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \abc\core\lib\AException
     */
    public static function getTotalCustomers($data = [])
    {
        return static::getCustomers($data,'total_only');
    }

    /**
     * @param array $settings
     *
     * @return bool
     * @throws \Exception
     */
    public function saveCustomerNotificationSettings($settings = [])
    {

        if (!$this->customer_id) {
            return false;
        }

        $customer_id = $this->customer_id;

        $im = Registry::im();
        $sendpoints = array_keys($im->sendpoints);
        $im_protocols = $im->getProtocols();

        $update = [];
        foreach ($settings as $sendpoint => $row) {
            if (!in_array($sendpoint, $sendpoints)) {
                continue;
            }
            foreach ($im_protocols as $protocol) {
                $update[$sendpoint][$protocol] = (int)$settings[$sendpoint][$protocol];
            }
        }

        if ($update) {
            foreach ($update as $sendpoint => $row) {
                foreach ($row as $protocol => $status) {
                    CustomerNotification::where('customer_id', '=', $this->customer_id)
                        ->where('sendpoint', '=',$sendpoint)
                        ->where('protocol', '=',$protocol)
                        ->delete();

                    $cn = new CustomerNotification( compact('customer_id', 'sendpoint','protocol','status'));
                    $cn->save();
                }
            }

            //for newsletter subscription do changes inside customers table
            //if at least one protocol enabled - set 1, otherwise - 0
            if ( H::has_value( $settings['newsletter'] ) ) {
                $newsletter_status = 0;
                foreach ( $settings['newsletter'] as $protocol => $status ) {
                    if ( $status ) {
                        $newsletter_status = 1;
                        break;
                    }
                }
                $this->update(['newsletter' => $newsletter_status ]);
            }
        }
        return true;
    }

    public static function getCustomersByProduct($product_id)
    {
        if (!$product_id) {
            return [];
        }

        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();

        /**
         * @var ADB $db
         */
        $db = Registry::db();

        $query = OrderProduct::where('order_products.product_id', '=', $product_id);
        /**
         * @var QueryBuilder $query
         */
        $query->select($db->raw_sql_row_count())
              ->select('customers.*');
        $query->join('orders', function($join){
            $join->on('orders.order_id', '=', 'order_products.order_id');
        });
        $query->join('customers', function($join){
            $join->on('orders.customer_id', '=', 'customers.customer_id');
        })
        ->where('orders.order_status_id', '>', 0)
        ->distinct();

        $result_rows = $query->get();
        $totalNumRows = $db->sql_get_row_count();
        for ($i = 0; $i < count($result_rows); $i++) {
            $result_rows[$i] = $dcrypt->decrypt_data($result_rows[$i], 'customers');
            $result_rows[$i]['total_num_rows'] = $totalNumRows;
        }

        return $result_rows;
    }

    public static function isUniqueLoginname($loginname, $customer_id = 0)
    {
        if (empty($loginname)) {
            return false;
        }
        /**
         * @var ADB $db
         */
        $db = Registry::db();
        $aliasC = $db->table_name('customers');

        //exclude current customer from checking
        $query = static::whereRaw("LOWER(". $aliasC.".loginname) = '".mb_strtolower($loginname)."'");

        if ($customer_id) {
            $query->where('customer_id', '<>', $customer_id);
        }

        return !($query->get()->count());
    }

    public static function getSubscribersGroupId()
    {
        return (int)CustomerGroup::where('name', '=', self::SUBSCRIBERS_GROUP_NAME)->first()->customer_group_id;
    }

    public function editCustomerNotifications( $data )
    {
        if ( ! $data ) {
            return false;
        }

        $customer_id = (int)$this->customer_id;

        if(!$customer_id){
            return false;
        }


        $db = Registry::db();
        $im = Registry::im();

        $upd = [];
        //get only active IM drivers
        $im_protocols = $im->getProtocols();
        $columns = $db->database()->getColumnListing("customers");

        foreach ( $im_protocols as $protocol ) {
            if ( isset( $data[$protocol] ) && in_array($protocol, $columns) ) {
                $upd[$protocol] = $data[$protocol];
            }
        }
        $this->update($upd);
        return true;
    }

}