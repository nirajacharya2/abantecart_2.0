<?php
namespace abc\tests\unit;

use abc\models\catalog\ProductDescription;
use Illuminate\Validation\ValidationException;

/**
 * Class ProductDescriptionModelTest
 */
class ProductDescriptionModelTest extends ATestCase{


    protected function setUp(){
        //init
    }

    public function testValidator()
    {
        $productDescription = new ProductDescription();
        $errors = [];
        try {
            $data = [
                'product_id'       => false,
                'language_id'      => false,
                'name'             => 'e',
                'meta_keywords'    => false,
                'meta_description' => false,
                'description'      => false,
                'blurb'            => false,
            ];
            $productDescription->validate( $data );
        } catch (ValidationException $e) {
            $errors = $productDescription->errors()['validation'];
        }

        $this->assertEquals(7, count($errors));


        $errors = [];
        try {
            $data = [
                'product_id'       => 50,
                'language_id'      => 1,
                'name'             => 'test',
                'meta_keywords'    => 'test',
                'meta_description' => 'test',
                'description'      => 'test',
                'blurb'            => 'test',
            ];
            $productDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productDescription->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'       => 50,
                'language_id'      => 1,
                'name'             => 'test',
                'meta_keywords'    => '',
                'meta_description' => '',
                'description'      => '',
                'blurb'            => '',
            ];
            $productDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productDescription->errors()['validation'];
            //var_dump($errors);
        }
        $this->assertEquals(0, count($errors));
        $errors = [];
        try {
            $data = [
                'product_id'       => 50,
                'language_id'      => 1,
                'name'             => 'test',
            ];
            $productDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productDescription->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

    }
}