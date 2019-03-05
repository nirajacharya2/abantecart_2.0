<?php

namespace abc\models\customer;

use abc\models\BaseModel;
use abc\models\order\Order;
use abc\models\system\Audit;
use abc\models\system\Store;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @package abc\models
 */
class Customer extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

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

    public function getFields()
    {
        return [
            'store_id' => [
                'rule' => 'required',
                'imput_type' => 'selectbox'
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

        /*
        $rules = [
            ['email', 'required', 'except' => ['register', 'search']],
            ['last_name, type_id, speciality_ids, country_id, region_id, city_id', 'required', 'except' => 'search'],
            ['first_name', 'required', 'except' => [self::SCENARIO_ORGANIZATION, 'search', self::SCENARIO_WITH_USER]],
            ['npi', 'default', 'setOnEmpty' => true, 'value' => null],
            ['npi', 'numerical', 'integerOnly' => true],
            ['npi', 'length', 'is' => 10, 'max'=>10],
            ['npi', 'validateNpiUnique'],
            ['npi', 'ext.validators.CareproviderNPILuhnValidator', 'allowEmpty' => true],
            ['has_schedule', 'numerical', 'integerOnly' => true],
            ['email', 'email'],
            [
                'email',
                'unique',
                'className' => 'Careprovider',
                'criteria' => ['condition' => 'user_id IS NOT NULL'],
                'except' => ['profile']
            ],
            ['language', 'numerical', 'integerOnly' => true],
            ['prescription_authority_flag', 'default', 'value' => \BaseModel::NO],
            ['birthday', 'ext.validators.VDateValidator', 'format' => param('date'), 'allowEmpty' => true],
            ['birthday', 'vbirthday'],
            ['gender', 'in', 'range' => array_keys(Customer::getGenderOptions())],
            ['user_id, service_location_id, type_id', 'length', 'max' => 10],
            ['first_name, last_name, middle_name, mobile, workphone', 'length', 'max' => 255],
            ['phone, phone_ah, fax', 'length', 'max' => 23],
            ['mobile, workphone, ttd_number', 'match', 'pattern' => Customer::PATTERN_PHONE],
            ['phone, phone_ah, fax', 'match', 'pattern' => Customer::PATTERN_PHONE_EXTENDED],
            ['country_id', 'exist', 'className' => 'Country', 'attributeName' => 'id', 'allowEmpty' => true],
            ['region_id', 'exist', 'className' => 'Region', 'attributeName' => 'id', 'allowEmpty' => true],
            ['city_id', 'exist', 'className' => 'City', 'attributeName' => 'id', 'allowEmpty' => true],
            ['postal_code', 'length', 'max' => 10],
            ['photoUrl, avatar, vendor', 'length', 'max' => 100],
            ['postal_code', 'match', 'pattern' => Customer::PATTERN_POSTAL_CODE],
            [
                'first_name, middle_name, last_name',
                'match',
                'pattern' => Customer::PATTERN_LETTERS_ONLY,
                'message' => 'Invalid characters. May only contain letters, spaces, and hyphens.',
                'except' => [self::SCENARIO_ORGANIZATION, self::SCENARIO_WITH_USER],
            ],
            [
                'last_name',
                'match',
                'pattern' => Customer::PATTERN_LETTERS_NUMBERS_SPACE_HYPHEN_AMPERSAND_APOSTROPHE_HASH,
                'message' => 'Invalid characters. May only contain letters, spaces, numbers, ampersands, apostrophe, hyphens and hash.',
                'on' => self::SCENARIO_ORGANIZATION
            ],
            ['grantdir', 'in', 'range' => array_keys(BaseModel::getYesNoOptions())],
            ['tax_id', 'validateTaxIDs'],
            ['tax_id', 'length', 'max' => 250],
            ['phone', 'required'],
            ['postal_code', 'required', 'except' => self::SCENARIO_CLIENTCP],
            ['service_location_id', 'required', 'on' => self::SCENARIO_CLIENTCP],
            ['p_min_age', 'default', 'value' => param('careproviderProfile')['min']['patientMinAge']],
            ['p_max_age', 'default', 'value' => param('careproviderProfile')['max']['patientMaxAge']],
            ['p_max_age', 'moreThen', 'then' => 'p_min_age'],
            ['medicare', 'length', 'max' => 255],
            ['medicaid, medicaid_site, ihc_number, ancillary_ihc_number', 'length', 'max' => 12],
            ['site_ihc', 'length', 'max' => 8],
            ['begin_date, end_date', 'ext.validators.VDateValidator', 'format' => param('date'), 'allowEmpty' => true],
            ['specialityIds, language_ids, languageIds', 'safe'],
            ['in_network, attested_provider_indicator, texas_medicaid_indicator, drg_facility_indicator, is_health_steps_indicator', 'boolean'],
            [
                'is_ancillary, is_vfc, service_location_id, has_wheelchair, has_signservice, has_behaviorsn, has_phsn',
                'numerical',
                'integerOnly' => true
            ],
            ['ihc_maximum, h_affiliation', 'numerical', 'integerOnly' => true],
            ['time_equiv', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 9999],
            ['vfc_referral, p_number, a_pname', 'length', 'max' => 100],
            ['h_name', 'length', 'max' => 50],
            ['driving_dirs, public_trans, add_comments', 'length', 'max' => 250],
            ['p_min_age', 'in', 'range' => array_keys(self::getPatientAgeOptions('min'))],
            ['p_max_age', 'in', 'range' => array_keys(self::getPatientAgeOptions('max'))],
            ['p_gender', 'in', 'range' => array_keys(self::getPatientGenderOptions())],
            ['spec_services', 'in', 'range' => array_keys(self::getSpecialitySevicesOptions())],
            ['h_priviledge', 'in', 'range' => array_keys(self::getPriviledgeOptions()), 'allowEmpty' => true],
            ['profile_type', 'in', 'range' => array_keys(self::getProfileTypeOptions())],
            ['suffix', 'in', 'range' => array_keys(self::getSuffixOptions())],
            ['schedule_comment, spec_comments', 'length', 'max' => 250],
            ['schedule_daytime', 'timeOpenClose'],
            ['npis', 'safe'],
            ['npis', 'length'],
            ['npis', 'validatorNpis'],
            [
                'schedule_day1_open, schedule_day1_close,
                schedule_day2_open, schedule_day3_open,
                schedule_day4_open, schedule_day5_open,
                schedule_day6_open, schedule_day7_open,
                schedule_day1_close, schedule_day2_close,
                schedule_day3_close, schedule_day4_close,
                schedule_day5_close, schedule_day6_close, schedule_day7_close',
                'date',
                'format' => param('unicodeTime')
            ],
            ['serviceLocationTitle, serviceLocationAddress1, serviceLocationType', 'required', 'except' => self::SCENARIO_AUTO],
            [
                'serviceLocationTitle',
                'match',
                'pattern' => Customer::PATTERN_LETTERS_NUMBERS_SPACE_HYPHEN_APOSTROPHE,
                'message' => t(
                    'AlertMessage',
                    'Invalid character(s). May only contain letters, numbers, spaces, hyphens and apostrophes.'
                )
            ],
            ['serviceLocationTitle', 'similarLocation'],
            ['serviceLocationTitle', 'length', 'max' => 250],
            ['serviceLocationAddress2, fax', 'length', 'max'=>250],
            [
                'id, user_id, created_date, updated_date, postal_code, service_location_id, prescription_authority_flag,
                first_name, last_name, middle_name, phone, mobile, workphone, type_id, speciality_id, email, grantdir,
                language, vendor',
                'safe',
                'on' => 'search'
            ],
            ['group_name', 'length', 'max' => self::ATTRIBUTE_LENGTH_GROUP_NAME],
            ['apis', 'safe'],
            ['stateIds', 'safe'],
            ['ltssIds', 'safe'],
            ['pfinIds', 'safe'],
        ];

        if (user()->getIsType(\User::TYPE_ADMINISTRATOR)) {
            $rules = CMap::mergeArray($rules, [
                ['prescription_authority_flag', 'numerical', 'integerOnly' => true],
                ['is_exportable_cce, is_exportable_ace', 'numerical', 'integerOnly' => true],
            ]);
        }

        // Дополняем правила если установлены через конфиг параметров
        if (!empty(param('careprovider')['rules'])) {
            $rules = CMap::mergeArray($rules, param('careprovider')['rules']);
        }

        // так как правила для https://bug.virtualhealth.com/issue/HCSC-243 достаточно объемны, то вынесены в метод.
        if ($this->getScenario() == self::SCENARIO_WITH_USER) {
            $rules = CMap::mergeArray($rules, $this->getValidateLevel2Rules());
        }

        return $this->isNewRecord ? CMap::mergeArray($rules, [
            ['email', 'unique', 'className' => 'User', 'on' => 'profile']
        ]) : $rules;
        */
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
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
}
