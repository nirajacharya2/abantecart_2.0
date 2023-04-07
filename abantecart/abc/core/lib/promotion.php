<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\ALoader;
use abc\core\engine\Registry;
use abc\models\catalog\Product;
use abc\models\catalog\ProductDiscount;
use abc\models\catalog\ProductSpecial;
use abc\models\order\Coupon;
use abc\models\order\CouponDescription;
use abc\models\order\Order;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class APromotion
 *
 * @property AbcCache $cache
 * @property ADB $db
 * @property ALoader $load
 */
class APromotion
{
    /** @var AConfig */
    protected $config;
    /** @var ACart */
    protected $cart;
    /** @var ADB */
    protected $db;
    /** ACache */
    protected $cache;
    /** @var ACustomer */
    protected $customer;
    /** @var int */
    protected $customer_group_id;
    /** @var array */
    public $condition_objects = [];
    /** @var array */
    public $bonus_objects = [];

    /**
     * @param ACustomer|null $customer
     * @param ACart|null $cart
     */
    public function __construct(ACustomer $customer = null, ACart $cart = null)
    {
        $this->customer = $customer ?: Registry::customer();
        $this->cart = $cart ?: Registry::cart();
        $this->config = Registry::config();
        $this->cache = Registry::cache();
        $this->db = Registry::db();

        //set customer group
        $this->customer_group_id = $this->customer?->isLogged()
            ? $this->customer->getCustomerGroupId()
            : $this->config->get('config_customer_group_id');

        $this->condition_objects = [
            'product_price',
            'categories',
            'brands',
            'products',
            'customers',
            'customer_groups',
            'customer_country',
            'customer_postcode',
            'order_subtotal',
            'order_product_count',
            'order_product_weight',
            'payment_method',
            'shipping_method',
            'coupon_code',
        ];
        $this->bonus_objects = [
            'order_discount',
            'free_shipping',
            'discount_products',
            'free_products',

        ];
    }

    /**
     * @return array
     * @deprecated
     */
    public function getConditionObjects()
    {
        return $this->condition_objects;
    }

    /**
     * @return array
     */
    public function getConditionList($section = 'storefront')
    {
        $indx = $output = [];
        foreach (ABC::env('incentive_conditions') as $class) {
            try {
                /** @var BaseIncentiveCondition $node */
                $node = new $class();
                if (in_array($node->getSection(), ['both', $section])) {
                    $indx[] = $output[$node->getKey()] = $node->getName();
                }
            } catch (Exception $e) {
                Registry::log()->error($e->getMessage());
            }
        }
        array_multisort($indx, SORT_STRING, $output);
        return $output;
    }

    /**
     * @return array
     */
    public function getBonusList($section = 'both')
    {
        $indx = $output = [];
        foreach (ABC::env('incentive_bonuses') as $class) {
            try {
                /** @var BaseIncentiveBonus $node */
                $node = new $class();
                if (!in_array($node->getSection(), ['both', $section])) {
                    continue;
                }
                $indx[] = $output[$node->getKey()] = $node->getName();
            } catch (Exception $e) {
                Registry::log()->error($e->getMessage());
            }
        }
        array_multisort($indx, SORT_STRING, $output);
        return $output;
    }

    /**
     * @param string $id
     * @return BaseIncentiveCondition|false
     */
    public function getConditionObjectByKey(string $id)
    {
        foreach (ABC::env('incentive_conditions') as $class) {
            try {
                /** @var BaseIncentiveCondition $node */
                $node = new $class();
                if ($node->getKey() == $id) {
                    return $node;
                }
            } catch (Exception $e) {
                Registry::log()->error($e->getMessage());
            }
        }
        return false;
    }

    /**
     * @param string $id
     * @return BaseIncentiveBonus|false
     */
    public function getBonusObjectByKey(string $id)
    {
        foreach (ABC::env('incentive_bonuses') as $class) {
            try {
                /** @var BaseIncentiveBonus $node */
                $node = new $class();
                if ($node->getKey() == $id) {
                    return $node;
                }
            } catch (Exception $e) {
                Registry::log()->error($e->getMessage());
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getBonusObjects()
    {
        return $this->bonus_objects;
    }

    /**
     * @param int $productId
     * @param int $discountQuantity
     *
     * @return float
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getProductQtyDiscount($productId, $discountQuantity)
    {
        $productId = (int)$productId;
        $discountQuantity = (int)$discountQuantity;
        $customerGroupId = (int)$this->customer_group_id;

        if (!$productId && !$discountQuantity) {
            return 0.00;
        }

        /** @var ProductDiscount $result */
        $result = ProductDiscount::select('price')
            ->where('product_id', '=', $productId)
            ->where('customer_group_id', '=', $customerGroupId)
            ->where('quantity', '<=', $discountQuantity)
            ->where(function ($query) {
                $query->where('date_start', '<', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->where(function ($query) {
                $query->where('date_end', '>', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })->orderBy('quantity', 'desc')
            ->orderBy('priority')
            ->orderBy('price')
            ->useCache('product')
            ->first();
        return (float)$result->price;
    }

    /**
     * @param int $productId
     *
     * @return float
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getProductDiscount($productId)
    {
        $productId = (int)$productId;
        $customerGroupId = (int)$this->customer_group_id;

        /** @var ProductDiscount $result */
        $result = ProductDiscount::select('price')
            ->where('product_id', '=', $productId)
            ->where('customer_group_id', '=', $customerGroupId)
            ->where('quantity', '=', 1)
            ->where(function ($query) {
                $query->where('date_start', '<', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->where(function ($query) {
                $query->where('date_end', '>', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->orderBy('priority')
            ->orderBy('price')
            ->useCache('product')
            ->first();
        return (float)$result->price;

    }

    /**
     * @param int $productId
     *
     * @return array
     * @throws AException|Exception
     * @throws InvalidArgumentException
     */
    public function getProductDiscounts($productId)
    {
        $productId = (int)$productId;
        $customerGroupId = (int)$this->customer_group_id;
        /** @var ProductDiscount $result */
        $result = ProductDiscount::where('product_id', '=', $productId)
            ->where('customer_group_id', '=', $customerGroupId)
            ->where('quantity', '>', 1)
            ->where(function ($query) {
                $query->where('date_start', '<', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->where(function ($query) {
                $query->where('date_end', '>', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })->orderBy('quantity')
            ->orderBy('priority')
            ->orderBy('price')
            ->useCache('product')
            ->get();
        return (array)$result?->toArray();
    }

    /**
     * @param $productId
     * @return null|float
     */
    public function getProductSpecial($productId)
    {
        $productId = (int)$productId;
        $customerGroupId = (int)$this->customer_group_id;

        /** @var ProductSpecial $result */
        $result = ProductSpecial::select('price')
            ->where('product_id', '=', $productId)
            ->where('customer_group_id', '=', $customerGroupId)
            ->where(function ($query) {
                $query->where('date_start', '<', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->where(function ($query) {
                $query->where('date_end', '>', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->orderBy('priority')
            ->orderBy('price')
            ->useCache('product')
            ->first();
        return (float)$result->price;
    }

    /**
     * @param string $sort
     * @param string $order
     * @param int $start
     * @param int $limit
     *
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getProductSpecials($sort = 'p.sort_order', $order = 'ASC', $start = 0, $limit = 20)
    {
        $start = abs((int) $start);
        $limit = abs((int) $limit);
        $language_id = (int) $this->config->get('storefront_language_id');
        $store_id = (int) $this->config->get('config_store_id');
        $customer_group_id = (int) $this->customer_group_id;

        $cache_key = 'product.specials.'.$customer_group_id;
        $cache_key .= $this->cache->paramsToString(
            [
                'sort'  => $sort,
                'order' => $order,
                'start' => (int) $start,
                'limit' => (int) $limit,
            ]
        );
        $cache_key .= '.store_'.$store_id.'.lang_'.$language_id;

        $cache = $this->cache->get($cache_key);
        if ($cache !== null) {
            return $cache;
        }

        $sql = "SELECT DISTINCT ps.product_id, p.*, pd.name, pd.description, pd.blurb, ss.name AS stock,
                    (SELECT AVG(rating)
                    FROM ".$this->db->table_name("reviews")." r1
                    WHERE r1.product_id = ps.product_id
                        AND r1.status = '1'
                    GROUP BY r1.product_id) AS rating
                FROM ".$this->db->table_name("product_specials")." ps
                LEFT JOIN ".$this->db->table_name("products")." p ON (ps.product_id = p.product_id)
                LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                    ON (p.product_id = pd.product_id AND language_id=".$language_id.")
                LEFT JOIN ".$this->db->table_name("products_to_stores")." p2s
                    ON (p.product_id = p2s.product_id)
                LEFT JOIN ".$this->db->table_name("stock_statuses")." ss
                    ON (p.stock_status_id = ss.stock_status_id AND ss.language_id = '".$language_id."')
                WHERE p.status = '1'
                    AND p.date_available <= NOW() AND p2s.store_id = '".$store_id."'
                    AND ps.customer_group_id = '".$customer_group_id."'
                    AND ((ps.date_start IS NULL OR ps.date_start < NOW())
                    AND (ps.date_end IS NULL OR ps.date_end > NOW()))
                GROUP BY ps.product_id";

        $sort_data = [
            'pd.name',
            'p.sort_order',
            'ps.price',
            'rating',
            'date_modified',
        ];

        if (in_array($sort, $sort_data)) {
            if ($sort == 'pd.name') {
                $sql .= " ORDER BY LCASE(".$sort.")";
            } else {
                $sql .= " ORDER BY ".$this->db->escape($sort);
            }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }

        if ($order == 'DESC') {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if ($start < 0) {
            $start = 0;
        }
        if ((int) $limit) {
            $sql .= " LIMIT ".(int) $start.",".(int) $limit;
        }

        $query = $this->db->query($sql);
        $output = $query->rows;

        $this->cache->put($cache_key, $output);

        return $output;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getSpecialProducts($data = [])
    {
        $data['sort'] = !isset($data['sort']) ? 'p.sort_order' : $data['sort'];
        $data['order'] = !isset($data['order']) ? 'ASC' : $data['order'];
        $data['start'] = !isset($data['start']) ? 0 : $data['start'];
        $data['limit'] = !isset($data['limit']) ? 20 : $data['limit'];

        $language_id = (int) $this->config->get('storefront_language_id');
        $store_id = (int) $this->config->get('config_store_id');
        $customer_group_id = (int) $this->customer_group_id;

        $cache_key = 'product.specials.'.$customer_group_id;
        $cache_key .= $this->cache->paramsToString($data);
        $cache_key .= '.store_'.$store_id.'.lang_'.$language_id;

        $cache = $this->cache->get($cache_key);
        if ($cache !== null) {
            return $cache;
        }

        $sql = "SELECT DISTINCT ps.product_id, p.*, pd.name, pd.description, pd.blurb, ss.name AS stock";
        if ($data['avg_rating']) {
            $sql
                .= ", (SELECT AVG(rating)
                    FROM ".$this->db->table_name("reviews")." r1
                    WHERE r1.product_id = ps.product_id AND r1.status = '1'
                    GROUP BY r1.product_id) AS rating\n";
        }
        $sql
            .= ", (SELECT price
                    FROM ".$this->db->table_name("product_discounts")." rd
                    WHERE rd.product_id = ps.product_id
                        AND customer_group_id = '".$customer_group_id."'
                        AND quantity = '1'
                        AND ((date_start IS NULL OR date_start < NOW())
                        AND (date_end IS NULL OR date_end > NOW()))
                    ORDER BY priority ASC, price ASC
                    LIMIT 1) as discount_price\n ";

        $sql .= "FROM ".$this->db->table_name("product_specials")." ps
                LEFT JOIN ".$this->db->table_name("products")." p ON (ps.product_id = p.product_id)
                LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                    ON (p.product_id = pd.product_id AND language_id=".$language_id.")
                LEFT JOIN ".$this->db->table_name("products_to_stores")." p2s
                    ON (p.product_id = p2s.product_id)
                LEFT JOIN ".$this->db->table_name("stock_statuses")." ss
                    ON (p.stock_status_id = ss.stock_status_id AND ss.language_id = '".$language_id."')
                WHERE p.status = '1'
                    AND p.date_available <= NOW() AND p2s.store_id = '".$store_id."'
                    AND ps.customer_group_id = '".$customer_group_id."'
                    AND ((ps.date_start IS NULL OR ps.date_start < NOW())
                    AND (ps.date_end IS NULL OR ps.date_end > NOW()))
                GROUP BY ps.product_id";

        $sort_data = [
            'pd.name',
            'p.sort_order',
            'ps.price',
            'rating',
            'date_modified',
        ];

        if (in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'pd.name') {
                $sql .= " ORDER BY LCASE(".$data['sort'].")";
            } else {
                $sql .= " ORDER BY ".$this->db->escape($data['sort']);
            }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }

        if ($data['order'] == 'DESC') {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if ($data['start'] < 0) {
            $data['start'] = 0;
        }
        if ((int) $data['limit']) {
            $sql .= " LIMIT ".(int) $data['start'].",".(int) $data['limit'];
        }

        $query = $this->db->query($sql);
        $output = $query->rows;

        $this->cache->put($cache_key, $output);

        return $output;
    }

    /**
     * @return int
     * @throws AException|Exception
     * @throws InvalidArgumentException
     */
    public function getTotalProductSpecials()
    {
        $storeId = (int)$this->config->get('config_store_id');

        return ProductSpecial::where('product_specials.customer_group_id', '=', $this->customer_group_id)
            ->where(function ($query) {
                $query->where('date_start', '<', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->where(function ($query) {
                $query->where('date_end', '>', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->join(
                'products',
                'products.product_id',
                '=',
                'product_specials.product_id'
            )->where('products.date_available', '<=', date("Y-m-d H:i:s"))
            ->where('products.status', '=', 1)
            ->join(
                'products_to_stores',
                'products_to_stores.product_id',
                '=',
                'product_specials.product_id'
            )
            ->where('products_to_stores.store_id', '=', $storeId)
            ->count();
    }

    /**
     * @param string $couponCode
     *
     * @return array
     * @throws AException|ReflectionException|InvalidArgumentException
     */
    public function getCouponData($couponCode)
    {
        if (empty ($couponCode)) {
            return [];
        }

        Coupon::setCurrentLanguageID($this->config->get('storefront_language_id'));
        /** @var Coupon|CouponDescription $coupon */
        $coupon = Coupon::with('description')
            ->where('code', '=', $couponCode)
            ->where(function ($query) {
                $query->where('date_start', '<', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->where(function ($query) {
                $query->where('date_end', '>', date("Y-m-d H:i:s"))
                    ->orWhereNull('date_start');
            })
            ->active()->first();

        if (!$coupon) {
            return [];
        }


        if ($coupon->total > $this->cart->getSubTotal()) {
            return [];
        }

        $couponRedeemCount = Order::where('order_status_id', '>', 0)
            ->where('coupon_id', '=', $coupon->coupon_id)
            ->count();

        if ($couponRedeemCount >= $coupon->uses_total
            && $coupon->uses_total > 0
        ) {
            return [];
        }

        if ($coupon->logged && !is_null($this->customer) && !$this->customer->getId()
        ) {
            return [];
        }
        if (!is_null($this->customer) && $this->customer->getId()) {
            $couponRedeemCount = Order::where('order_status_id', '>', 0)
                ->where('coupon_id', '=', $coupon->coupon_id)
                ->where('customer_id', '=', $this->customer->getId())
                ->count();
            if ($couponRedeemCount >= $coupon->uses_customer && $coupon->uses_customer > 0) {
                return [];
            }
        }

        $couponProductIds = Coupon::with('products')
            ->where('coupon_id', '=', $coupon->coupon_id)
            ->get()
            ?->pluck('products.product_id')
            ->toArray();
        $couponProductIds = array_filter($couponProductIds);

        if ($couponProductIds) {
            $couponProduct = false;
            foreach ($this->cart->getProducts() as $product) {
                if (in_array($product['product_id'], $couponProductIds)) {
                    $couponProduct = true;
                    break;
                }
            }
            if (!$couponProduct) {
                return [];
            }
        }

        $coupon_data = $coupon->toArray();
        $coupon_data = array_merge($coupon_data['description'], $coupon_data);
        $coupon_data['product'] = $couponProductIds;
        return $coupon_data;
    }

    /**
     * @param array $total_data
     * @param array $total
     *
     * @return array
     */
    public function apply_promotions($total_data, $total)
    {
        $registry = Registry::getInstance();
        if ($registry->has('extensions')) {
            $result = $registry->get('extensions')->hk_apply_promotions($this, $total_data, $total);
        } else {
            $result = $this->_apply_promotions($total_data, $total);
        }
        return $result;
    }

    //adding native promotions
    /**
     * @param array $total_data
     * @param array $total
     *
     * @return array
     */
    public function _apply_promotions($total_data, $total)
    {
        return [];
    }
}