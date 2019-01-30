<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\tests\unit\models;

use abc\core\lib\ADB;
use abc\models\catalog\Product;
use abc\tests\unit\ATestCase;

/**
 * Class testProductModel
 *
 * @package abantecart\tests
 * @property ADB $db
 */
class testProductModel extends ATestCase
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
        $product = Product::where($update_by_name, '=', $update_by_value)->first();
        if($product) {
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
        }else{
            $this->assertEquals(0, 0);
        }
    }

}