<?php

namespace abc\models\customer;

use abc\core\engine\Registry;
use abc\core\lib\ADataEncryption;
use abc\models\BaseModel;
use abc\models\locale\Country;
use abc\models\locale\Zone;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Address
 *
 * @property int $address_id
 * @property int $customer_id
 * @property string $company
 * @property string $firstname
 * @property string $lastname
 * @property string $address_1
 * @property string $address_2
 * @property string $postcode
 * @property string $city
 * @property int $country_id
 * @property int $zone_id
 *
 * @property Customer $customer
 * @property Country $country
 * @property Zone $zone
 *
 * @package abc\models
 */
class Address extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'address_id';

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    protected $casts = [
        'customer_id' => 'int',
        'country_id'  => 'int',
        'zone_id'     => 'int',
    ];

    protected $fillable = [
        'customer_id',
        'company',
        'firstname',
        'lastname',
        'address_1',
        'address_2',
        'postcode',
        'city',
        'country_id',
        'zone_id',
    ];

    protected $rules = [
        'address_id'        => 'integer',
        'customer_id'       => 'integer',
        'company'           => 'string|max:32',
        'firstname'         => [
                                'string',
                                //required only when new customer creating
                                'required_without:address_id',
                                'between:1,32'
                               ],
        'lastname'          => [
                                'string',
                                //required only when new customer creating
                                'required_without:address_id',
                                'between:1,32'
                               ],
        'address_1'         => [
                                'string',
                                //required only when new customer creating
                                'required_without:address_id',
                                'between:3,128'
                               ],
        'address_2'         => 'string|max:128',
        'postcode'          => [
                                'string',
                                //required only when new customer creating
                                'required_without:address_id',
                                'between:3,10'
                               ],
        'city'              => [
                                'string',
                                //required only when new customer creating
                                'required_without:address_id',
                                'between:3,128'
                               ],
        'country_id'        => 'integer|required_without:address_id',
        'zone_id'           => 'integer|required_without:address_id'
    ];

    /**
     * @param array $options
     *
     * @return bool
     * @throws \abc\core\lib\AException
     */
    public function save($options = [])
    {

        $data = $this->attributes;
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();
        if ($dcrypt->active) {
            $data = $dcrypt->encrypt_data($data, 'addresses');
        }

        $this->attributes = $data;
        return parent::save($options);

    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /**
     * @param int $customer_id
     *
     * @return array
     * @throws \abc\core\lib\AException
     * @throws \Exception
     */
    public static function getAddressesByCustomerId(int $customer_id)
    {
        if(!$customer_id){
            return [];
        }

        $output = [];
        $rows = static::where('customer_id', '=', $customer_id)->get()->toArray();
        if(!$rows){
            return [];
        }
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();

        foreach ($rows as $row) {
            $row = $dcrypt->decrypt_data($row, 'addresses');
            /**
             * @var Country $country
             */
            $country = Country::find($row['country_id']);
            if ($country->toArray()) {
                $country_name = $country->name;
                $iso_code_2 = $country->iso_code_2;
                $iso_code_3 = $country->iso_code_3;
                $address_format = $country->address_format;
            } else {
                $country_name = '';
                $iso_code_2 = '';
                $iso_code_3 = '';
                $address_format = '';
            }

            /**
             * @var Zone $zone
             */
            $zone = Zone::find($row['zone_id']);
            if ($zone->toArray()) {
                $zone_name = $zone->name;
                $zone_code = $zone->code;
            } else {
                $zone_name = '';
                $zone_code = '';
            }

            $output[$row['address_id']] = [
                'address_id'     => $row['address_id'],
                'firstname'      => $row['firstname'],
                'lastname'       => $row['lastname'],
                'company'        => $row['company'],
                'address_1'      => $row['address_1'],
                'address_2'      => $row['address_2'],
                'postcode'       => $row['postcode'],
                'city'           => $row['city'],
                'zone_id'        => $row['zone_id'],
                'zone'           => $zone_name,
                'zone_code'      => $zone_code,
                'country_id'     => $row['country_id'],
                'country'        => $country_name,
                'iso_code_2'     => $iso_code_2,
                'iso_code_3'     => $iso_code_3,
                'address_format' => $address_format,
            ];
        }
        return $output;
    }

}
