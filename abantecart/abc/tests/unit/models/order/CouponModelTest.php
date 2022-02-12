<?php

namespace Tests\unit\models\order;

use abc\models\order\Coupon;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class CouponModelTest
 */
class CouponModelTest extends ATestCase
{

    public function testValidator()
    {
        //validate
        $data = [
            'code'          => -0.0000000000009,
            'type'          => 1000,
            'discount'      => 'fail',
            'logged'        => 'fail',
            'shipping'      => 'fail',
            'total'         => 'fail',
            'date_start'    => 'fail',
            'date_end'      => 'fail',
            'uses_total'    => 'fail',
            'uses_customer' => 'fail',
            'status'        => 'fail',
        ];

        $coupon = new Coupon();
        $errors = [];
        try {
            $coupon->validate($data);
        } catch (ValidationException $e) {
            $errors = $coupon->errors()['validation'];
            //var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertCount(11, $errors);

        //validate
        $data = [
            'code'          => 'testcoupon',
            'type'          => 'p',
            'discount'      => 2.25,
            'logged'        => true,
            'shipping'      => true,
            'total'         => 1.01,
            'date_start'    => date('Y-m-d H:i:s'),
            'date_end'      => date('Y-m-d H:i:s'),
            'uses_total'    => 100,
            'uses_customer' => 10000,
            'status'        => true,
        ];

        $coupon = new Coupon($data);
        $errors = [];
        try {
            $coupon->validate($data);
            $coupon->save();
        } catch (ValidationException $e) {
            $errors = $coupon->errors()['validation'];
            var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertCount(0, $errors);

        $coupon->forceDelete();
    }
}