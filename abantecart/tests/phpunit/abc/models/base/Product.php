<?php
/**
 * AbanteCart auto-generated phpunit test file
 */

namespace abantecart\tests;

use abc\core\lib\ADB;
use abc\models\base\Product;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class testProductModel
 *
 * @package abantecart\tests
 * @property ADB $db
 */
class testProductModel extends AbanteCartTest
{

    protected function tearDown()
    {
        //init
    }

    public function testUpdateProduct()
    {

        $update_by_name = 'model';
        $update_by_value = '558003';
        $data = ['price' => rand(1, 40)];
        /**
         * @var Product $product
         */
        $product = Product::where([$update_by_name => $update_by_value])->first();
        //$product = Product::find(205);

        //expand fillable columns for extensions
        if ($this->data['fillable']) {
            $product->addFillable($this->data['fillable']);
        }

        $fills = $product->getFillable();
        foreach ($fills as $fillable) {
            if (isset($data[$fillable])) {
                $product->{$fillable} = urldecode($data[$fillable]);
            }
        }

        $product->save();

        //check result
        $updated_products_count = $this->db->table('products')->where('price', '=', $data['price'])->count();
        $this->assertEquals(1, $updated_products_count);
    }

}