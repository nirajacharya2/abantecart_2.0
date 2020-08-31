<?php
namespace abc\tests\unit;

use abc\models\catalog\ProductOption;
use abc\models\catalog\ProductOptionDescription;
use abc\models\catalog\ProductOptionValue;
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

    public function testStaticMethods()
    {
        $option = ProductOption::first();

        $sku = time();
        $data = [
            'product_id'         => $option->product_id,
            'product_option_id'  => $option->product_option_id,
            'sku'                => $sku,
            'quantity'           => 100,
            'subtract'           => 1,
            'price'              => 19.99,
            'prefix'             => '%',
            'weight'             => 1.02,
            'weight_type'        => 'lb',
            'attribute_value_id' => '',
            'sort_order'         => 15,
            'default'            => false,
            'descriptions'       => [
                1 => [
                    'name' => 'some option value name',
                ],
            ],

        ];
        $valueId = ProductOption::addProductOptionValueAndDescription($data);
        $this->assertIsInt($valueId);

        $optionValue = ProductOptionValue::with('description')->find($valueId);

        $optionCheckData = $data;
        $optionCheckData['product_option_value_id'] = $valueId;
        unset($optionCheckData['descriptions']);
        $diff = array_diff_assoc($optionCheckData, $optionValue->toArray());
        $this->assertGreaterThan(0, count($diff));
        $this->assertEquals($data['descriptions'][1]['name'], $optionValue->description->name);
        $optionValue->forceDelete();

        //with another format
        $data = [
            'product_id'         => $option->product_id,
            'product_option_id'  => $option->product_option_id,
            'sku'                => $sku,
            'quantity'           => 101,
            'subtract'           => 1,
            'price'              => 20.99,
            'prefix'             => '%',
            'weight'             => 1.03,
            'weight_type'        => 'lb',
            'attribute_value_id' => '',
            'sort_order'         => 16,
            'default'            => false,
            'description'        =>
                [
                    'name' => 'some option value name - 2',
                ],
        ];
        $valueId = ProductOption::addProductOptionValueAndDescription($data);
        $this->assertIsInt($valueId);

        $optionValue = ProductOptionValue::with('description')->find($valueId);

        $optionCheckData = $data;
        $optionCheckData['product_option_value_id'] = $valueId;
        unset($optionCheckData['descriptions']);
        $diff = array_diff_assoc($optionCheckData, $optionValue->toArray());
        $this->assertGreaterThan(0, count($diff));
        $this->assertEquals($data['description']['name'], $optionValue->description->name);
        $optionValue->forceDelete();

        //with another format
        $data = [
            'product_id'         => $option->product_id,
            'product_option_id'  => $option->product_option_id,
            'sku'                => $sku,
            'quantity'           => 102,
            'subtract'           => 1,
            'price'              => 21.99,
            'prefix'             => '%',
            'weight'             => 1.04,
            'weight_type'        => 'lb',
            'attribute_value_id' => '',
            'sort_order'         => 17,
            'default'            => false,
            'name'               => 'some option value name - 3',
        ];
        $valueId = ProductOption::addProductOptionValueAndDescription($data);
        $this->assertIsInt($valueId);

        $optionValue = ProductOptionValue::with('description')->find($valueId);

        $optionCheckData = $data;
        $optionCheckData['product_option_value_id'] = $valueId;
        unset($optionCheckData['descriptions']);
        $diff = array_diff_assoc($optionCheckData, $optionValue->toArray());
        $this->assertGreaterThan(0, count($diff));
        $this->assertEquals($data['name'], $optionValue->description->name);
        $optionValue->forceDelete();

        //test with global attribute ids
        $data = [
            'product_id'         => $option->product_id,
            'product_option_id'  => $option->product_option_id,
            'sku'                => $sku,
            'quantity'           => 103,
            'subtract'           => 1,
            'price'              => 22.99,
            'prefix'             => '%',
            'weight'             => 1.05,
            'weight_type'        => 'lb',
            'attribute_value_id' => [42, 43, 44],
            'sort_order'         => 18,
            'default'            => false,
        ];
        $valueId = ProductOption::addProductOptionValueAndDescription($data);
        $this->assertIsInt($valueId);

        $optionValue = ProductOptionValue::with('description')->find($valueId);

        $optionCheckData = $data;
        $optionCheckData['product_option_value_id'] = $valueId;
        $diff = array_diff_assoc($optionCheckData, $optionValue->toArray());
        $this->assertGreaterThan(0, count($diff));
        $this->assertEquals('1.7 oz / 3.4 oz / 100ml', $optionValue->description->name);

        $optionValue->forceDelete();

        $data = [
            'product_id'         => $option->product_id,
            'product_option_id'  => $option->product_option_id,
            'sku'                => $sku,
            'quantity'           => 104,
            'subtract'           => 1,
            'price'              => 23.99,
            'prefix'             => '%',
            'weight'             => 1.06,
            'weight_type'        => 'lb',
            'attribute_value_id' => 42,
            'sort_order'         => 19,
            'default'            => false,
        ];
        $valueId = ProductOption::addProductOptionValueAndDescription($data);
        $this->assertIsInt($valueId);

        $optionValue = ProductOptionValue::with('description')->find($valueId);

        $optionCheckData = $data;
        $optionCheckData['product_option_value_id'] = $valueId;
        $diff = array_diff_assoc($optionCheckData, $optionValue->toArray());
        $this->assertGreaterThan(0, count($diff));
        $this->assertEquals('1.7 oz', $optionValue->description->name);

        $optionValue->forceDelete();
    }
}