<?php
namespace abc\tests\unit;

use abc\models\catalog\ProductOption;
use Illuminate\Validation\ValidationException;

/**
 * Class ProductOptionModelTest
 */
class ProductOptionModelTest extends ATestCase{


    protected function setUp(){
        //init
    }

    public function testValidator()
    {
        $productOption = new ProductOption();
        $errors = [];
        try {
            $data = [
                'product_id'     => false,
                'attribute_id'   => false,
                'group_id'       => false,
                'sort_order'     => false,
                'status'         => 0.00001,
                'element_type'   => '_',
                'required'       => 0.000001,
                'regexp_pattern' => false,
                'settings'       => false,
            ];
            $productOption->validate( $data );
        } catch (ValidationException $e) {
            $errors = $productOption->errors()['validation'];
        }
        $this->assertEquals(8, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'     => 50,
                'attribute_id'   => null,
                'group_id'       => null,
                'sort_order'     => 1,
                'status'         => true,
                'element_type'   => 'S',
                'required'       => true,
                'regexp_pattern' => '',
                'settings'       => [],
            ];
            $productOption->validate( $data );
        } catch (ValidationException $e) {
            $errors = $productOption->errors()['validation'];
            var_Dump($errors);
        }
        $this->assertEquals(0, count($errors));
    }
}