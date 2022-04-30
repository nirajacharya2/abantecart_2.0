<?php

namespace abc\tests\unit;

use abc\models\order\CouponsProduct;
use Illuminate\Validation\ValidationException;

/**
 * Class CouponProductTest
 */
class CouponProductModelTest extends ATestCase
{

    protected function setUp():void
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'coupon_id'  => 'fail',
            'product_id' => 'fail',
        ];

        $coupon = new CouponsProduct();
        $errors = [];
        try {
            $coupon->validate($data);
        } catch (ValidationException $e) {
            $errors = $coupon->errors()['validation'];
            //var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(2, count($errors));

        //validate
        $data = [
            'coupon_id'  => 121212121,
            'product_id' => 2323232323,
        ];

        $coupon = new CouponsProduct();
        $errors = [];
        try {
            $coupon->validate($data);
        } catch (ValidationException $e) {
            $errors = $coupon->errors()['validation'];
            //var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(2, count($errors));

        //validate
        $data = [
            'coupon_id'  => 4,
            'product_id' => 50,
        ];

        $coupon = new CouponsProduct($data);
        $errors = [];
        try {
            $coupon->validate($data);
            $coupon->save();
        } catch (ValidationException $e) {
            $errors = $coupon->errors()['validation'];
            var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(0, count($errors));
        $coupon->forceDelete();
    }
}