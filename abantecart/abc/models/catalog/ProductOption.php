<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
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
 *
 */

namespace abc\models\catalog;

use abc\core\ABC;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\core\lib\AResourceManager;
use abc\core\lib\AttributeManager;
use abc\models\BaseModel;
use abc\models\QueryBuilder;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Validation\Rule;

/**
 * Class ProductOption
 *
 * @property int $product_option_id
 * @property int $attribute_id
 * @property int $product_id
 * @property int $group_id
 * @property int $sort_order
 * @property int $status
 * @property string $element_type
 * @property int $required
 * @property string $regexp_pattern
 * @property string $settings
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property ProductOptionValue $values
 * @property Product $product
 * @property ProductOptionDescription $descriptions
 *
 * @method static ProductOption find(int $product_option_id) ProductOption
 * @method static ProductOption create(array $attributes) ProductOption
 * @method static ProductOption first() ProductOption
 *
 * @package abc\models
 */
class ProductOption extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'values'];
    /**
     * @var string
     */
    protected $primaryKey = 'product_option_id';

    protected $touches = ['product'];

    /**
     * @var array
     */
    protected $casts = [
        'attribute_id' => 'int',
        'product_id'   => 'int',
        'group_id'     => 'int',
        'sort_order'   => 'int',
        'status'       => 'int',
        'required'     => 'int',
        'settings'     => 'serialized',
    ];

    /** @var array */
    protected $dates = [
        'date_added',
        'date_modified',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'attribute_id',
        'group_id',
        'sort_order',
        'status',
        'element_type',
        'required',
        'regexp_pattern',
        'settings',
    ];

    protected $rules = [
        /** @see validate() */
        'product_id'   => [
            'checks'   => [
                'integer',
                'required',
                'exists:products',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product ID is not Integer or absent in the products table!'],
            ],
        ],
        'attribute_id' => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:global_attributes',
            ],
            'messages' => [
                '*' => ['default_text' => 'Attribute ID is not Integer or not presents in global_attributes table!'],
            ],
        ],
        'group_id'     => [
            'checks'   => [
                'integer',
                'nullable',
            ],
            'messages' => [
                '*' => ['default_text' => 'Group ID is not integer!'],
            ],
        ],
        'sort_order'   => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'status'       => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'element_type' => [
            'checks'   => [
                'string',
                'size:1',
                /** @see __construct() method */
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be 1 character length and presents in element_types list of AHtml class!',
                ],
            ],
        ],

        'required' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],

        'regexp_pattern' => [
            'checks'   => [
                'string',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Blurb must be less than 1500 characters!',
                ],
            ],
        ],
    ];

    public function __construct(array $attributes = [])
    {
        $letters = array_keys(HtmlElementFactory::getAvailableElements());
        $this->rules['element_type']['checks'][] = Rule::in($letters);
        parent::__construct($attributes);
    }

    public function setSettings($value)
    {
        $this->attributes['settings'] = serialize($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'product_option_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function description()
    {
        return $this->hasOne(ProductOptionDescription::class, 'product_option_id')
                    ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_option_id');
    }

    /**
     * @return false|array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getAllData()
    {
        $cache_key = 'product.alldata.'.$this->getKey();
        $data = $this->cache->get($cache_key);
        if ($data === null) {
            $this->load('descriptions', 'values');
            $data = $this->toArray();
            foreach ($this->values as $optionValue) {
                $data['values'][] = $optionValue->getAllData();
            }
            $this->cache->put($cache_key, $data);
        }
        return $data;
    }

    public function delete()
    {
        $this->load('values');
        if ($this->values) {
            /**
             * @var AResourceManager $rm
             */
            $rm = ABC::getObjectByAlias('AResourceManager');
            $rm->setType('image');
            foreach ($this->values as $option_value) {
                //Remove previous resources of object
                $rm->unmapAndDeleteResources('product_option_value', $option_value->product_option_value_id);
            }
        }
        parent::delete();
    }

    /**
     * @param $po_ids
     *
     * @return bool|\Illuminate\Support\Collection
     */
    public static function getProductOptionsByIds($po_ids)
    {

        if (!$po_ids || !is_array($po_ids)) {
            return false;
        }
        /**
         * @var QueryBuilder $query
         */
        $query = static::select(
            [
                'product_options.*',
                'product_option_values.*',
                'product_option_descriptions.name as option_name',
                'product_option_value_descriptions.name as option_value_name',

            ]
        );
        $query->whereIn('product_options.product_option_id', $po_ids)
              ->leftJoin(
                  'product_option_descriptions',
                  function ($join) {
                      /** @var JoinClause $join */
                      $join->on('product_option_descriptions.product_option_id', '=',
                          'product_options.product_option_id')
                           ->where('product_option_descriptions.language_id', '=',
                               static::$current_language_id);
                  }
              )->leftJoin(
                'product_option_values',
                'product_options.product_option_id',
                '=',
                'product_option_values.product_option_id'
            )->leftJoin(
                'product_option_value_descriptions',
                function ($join) {
                    /** @var JoinClause $join */
                    $join->on(
                        'product_option_value_descriptions.product_option_value_id',
                        '=',
                        'product_option_values.product_option_value_id'
                    )->where(
                        'product_option_value_descriptions.language_id',
                        '=',
                        static::$current_language_id
                    );
                }
            )->orderBy('product_options.product_option_id');

        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->get();
    }

    /**
     * @param array $indata - must contains product_id, product_option_value_id
     *
     * @return int|null
     * @throws \Exception
     */
    public static function addProductOptionValueAndDescription(array $indata)
    {
        if (empty($indata) || !$indata['product_id'] || !$indata['product_option_id']) {
            return false;
        }

        $optionData = $indata;
        if (is_array($indata['attribute_value_id'])) {
            unset($optionData['attribute_value_id']);
        } else {
            $optionData['attribute_value_id'] = (int)$optionData['attribute_value_id'];
            if (!$optionData['attribute_value_id']) {
                unset($optionData['attribute_value_id']);
            } else {
                $indata['attribute_value_id'] = [$optionData['attribute_value_id']];
            }

        }

        /**
         * @var AttributeManager $am
         */
        $am = ABC::getObjectByAlias('AttributeManager');
        //build grouped attributes if this is a parent attribute
        if (is_array($indata['attribute_value_id'])) {
            //add children option values from global attributes
            $groupData = [];
            foreach ($indata['attribute_value_id'] as $child_option_id => $attribute_value_id) {
                #special data for grouped options. will be serialized in model mutator
                $groupData[] = [
                    'attr_id'   => $child_option_id,
                    'attr_v_id' => $attribute_value_id,
                ];
            }
            $optionData['grouped_attribute_data'] = $groupData;
        }

        $optionValue = ProductOptionValue::create($optionData);
        $optionValueId = $optionValue->getKey();

        //Build options value descriptions
        if (is_array($indata['attribute_value_id'])) {
            //add children option values description from global attributes
            $group_description = [];
            $descr_names = [];
            foreach ($indata['attribute_value_id'] as $child_option_id => $attribute_value_id) {
                #special insert for grouped options
                foreach ($am->getAttributeValueDescriptions($attribute_value_id) as $language_id => $name) {
                    $group_description[$language_id][] = [
                        'attr_v_id' => $attribute_value_id,
                        'name'      => $name,
                    ];
                    $descr_names[$language_id][] = $name;
                }
            }

            // Insert generic merged name
            $grouped_names = null;
            foreach ($descr_names as $language_id => $name) {
                ProductOptionValueDescription::create(
                    [
                        'product_id'              => $optionValue->product_id,
                        'product_option_value_id' => $optionValueId,
                        'language_id'             => $language_id,
                        'name'                    => implode(' / ', $name),
                        //note: serialized data (array)
                        'grouped_attribute_names' => $group_description[$language_id],
                    ]
                );
            }

        } else {
            if (!$indata['attribute_value_id']) {
                //We save custom option value for current language
                if (isset($indata['descriptions'])) {
                    $valueDescriptions = $indata['descriptions'];
                } elseif (isset($indata['description'])) {
                    $valueDescriptions = [
                        static::$current_language_id => [
                            'name' => ($indata['description']['name'] ?? 'Unknown'),
                        ],
                    ];
                } elseif ($indata['name']) {
                    $valueDescriptions = [
                        static::$current_language_id =>
                            [
                                'name' => ($indata['name'] ?? 'Unknown'),
                            ],
                    ];
                } else {
                    $valueDescriptions = [
                        static::$current_language_id =>
                            [
                                'name' => 'Unknown',
                            ],
                    ];
                }
            } else {
                //We have global attributes, copy option value text from there.
                $valueDescriptions = $am->getAttributeValueDescriptions((int)$indata['attribute_value_id']);
            }

            foreach ($valueDescriptions as $language_id => $description) {
                $language_id = (int)$language_id;
                if (!$language_id) {
                    throw new \Exception('Wrong format of input data! Description of value must have the language ID as a key!');
                }

                $desc = $description;
                $desc['product_id'] = $optionValue->product_id;
                $desc['product_option_value_id'] = $optionValueId;
                $desc['language_id'] = $language_id;
                ProductOptionValueDescription::create($desc);
            }
        }
        Registry::cache()->flush('product');
        return $optionValueId;
    }

}

