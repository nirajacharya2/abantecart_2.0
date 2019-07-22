<?php

namespace abc\tests\unit;

use abc\models\order\Coupon;
use abc\models\order\CouponDescription;
use Illuminate\Validation\ValidationException;

/**
 * Class CouponDescriptionModelTest
 */
class CouponDescriptionModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'coupon_id'   => 'fail',
            'language_id' => 'fail',
            'name'        => -0.00000000999,
            'description' => -0.00000000999,
        ];

        $coupon = new CouponDescription();
        $errors = [];
        try {
            $coupon->validate($data);
        } catch (ValidationException $e) {
            $errors = $coupon->errors()['validation'];
            //var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(4, count($errors));

        $data = [
            'coupon_id'   => 232323,
            'language_id' => 3333333,
            'name'        => 'unittest coupon',
            'description' => 'unittest coupon description',
        ];

        $coupon = new CouponDescription();
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
            'coupon_id'   => 4,
            'language_id' => 1,
            'name'        => 'UnitTest Coupon',
            'description' => 'Unittest coupon description',
        ];

        $coupon = new CouponDescription($data);
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