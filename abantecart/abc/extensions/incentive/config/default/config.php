<?php

use abc\extensions\incentive\modules\bonuses\DiscountProduct;
use abc\extensions\incentive\modules\bonuses\FreeProducts;
use abc\extensions\incentive\modules\bonuses\FreeShipping;
use abc\extensions\incentive\modules\bonuses\OrderDiscount;
use abc\extensions\incentive\modules\conditions\CartWeight;
use abc\extensions\incentive\modules\conditions\CouponCode;
use abc\extensions\incentive\modules\conditions\CustomerGroupsCondition;
use abc\extensions\incentive\modules\conditions\CustomerPostcodes;
use abc\extensions\incentive\modules\conditions\CustomersCondition;
use abc\extensions\incentive\modules\conditions\BrandsCondition;
use abc\extensions\incentive\modules\conditions\CustomerCountry;
use abc\extensions\incentive\modules\conditions\CustomersIdList;
use abc\extensions\incentive\modules\conditions\ProductsCondition;
use abc\extensions\incentive\modules\conditions\OrderProductCount;
use abc\extensions\incentive\modules\conditions\OrderSubtotalCondition;
use abc\extensions\incentive\modules\conditions\PaymentMethod;
use abc\extensions\incentive\modules\conditions\ProductCategories;
use abc\extensions\incentive\modules\conditions\ProductPrice;
use abc\extensions\incentive\modules\conditions\ShippingMethod;

return [
    'incentive_conditions' => [
        //customer related
        CustomerCountry::class,
        CustomerGroupsCondition::class,
        CustomersCondition::class,
        CustomerPostcodes::class,
        CustomersIdList::class,
        //order related
        ProductsCondition::class,
        ProductCategories::class,
        ProductPrice::class,
        BrandsCondition::class,
        OrderSubtotalCondition::class,
        OrderProductCount::class,
        CartWeight::class,
        PaymentMethod::class,
        ShippingMethod::class,
        CouponCode::class
    ],
    'incentive_bonuses'    => [
        OrderDiscount::class,
        FreeProducts::class,
        DiscountProduct::class,
        FreeShipping::class
    ]
];