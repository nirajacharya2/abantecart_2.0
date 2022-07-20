<?php

namespace Tests\unit\models\catalog;

use abc\models\catalog\ProductOptionValue;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class ProductOptionValueModelTest
 */
class ProductOptionValueModelTest extends ATestCase
{

    public function testValidator()
    {
        $productOptionValue = new ProductOptionValue();
        $errors = [];
        try {
            $data = [
                'product_option_id'      => false,
                'product_id'             => false,
                'group_id'               => false,
                'sku'                    => false,
                'quantity'               => false,
                'subtract'               => 0.000111,
                'price'                  => false,
                'prefix'                 => false,
                'weight'                 => false,
                'weight_type'            => false,
                'attribute_value_id'     => false,
                'grouped_attribute_data' => false,
                'sort_order'             => false,
                'default'                => 0.111111111,
            ];
            $productOptionValue->validate($data);
        } catch (ValidationException $e) {
            $errors = $productOptionValue->errors()['validation'];
            //var_Dump(array_keys($errors));
        }
        $this->assertCount(13, $errors);

        $errors = [];
        try {
            $data = [
                'product_option_id'      => 307,
                'product_id'             => 50,
                'group_id'               => null,
                'sku'                    => 'unit test option sku',
                'quantity'               => 1,
                'subtract'               => true,
                'price'                  => 0.25,
                'prefix'                 => '%',
                'weight'                 => 0.1,
                'weight_type'            => 'kg',
                'attribute_value_id'     => 32,
                'grouped_attribute_data' => ['test' => 'ttt'],
                'sort_order'             => 1,
                'default'                => true,
            ];
            $productOptionValue->validate($data);
        } catch (ValidationException $e) {
            $errors = $productOptionValue->errors()['validation'];
            var_Dump($errors);
        }
        $this->assertCount(0, $errors);
    }

    public function testStaticMethods()
    {
        $options = ProductOptionValue::getProductOptionValues(314);
        $this->assertCount(3, $options);
        $this->assertEquals(64, $options[0]['product_id']);
        $this->assertEquals('1.0 oz', $options[0]['descriptions'][0]['name']);

        $option = ProductOptionValue::getProductOptionValue($options[0]['product_option_value_id']);
        $this->assertEquals(64, $option['product_id']);
        $this->assertEquals('1.0 oz', $option['descriptions'][0]['name']);
    }
}