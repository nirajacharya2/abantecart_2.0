<?php
namespace abc\tests\unit;

use abc\models\order\OrderDownload;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderDownloadModelTest
 */
class OrderDownloadModelTest extends ATestCase{

    protected function setUp(){
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'order_id' => 'fail',
            'order_product_id' => 'fail',
            'name' => -0.000000000123232,
            'filename' => -0.000000000123232,
            'mask' => -0.000000000123232,
            'download_id' => 'fail',
            'status' => 'fail',
            'remaining_count' => 'fail',
            'percentage' => 'fail',
            'expire_date' => 'fail',
            'sort_order' => 'fail',
            'activate' => -0.000000000123232,
            'activate_order_status_id' => 'fail',
            'attributes_data' => 'fail'
        ];

        $orderDownload = new OrderDownload(  );
        $errors = [];
        try{
            $orderDownload->validate($data);
        }catch(ValidationException $e){
            $errors = $orderDownload->errors()['validation'];
           // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(14, count($errors));

        //check validation of presence in database
        $data = [
            'order_id' => 10000000,
            'order_product_id' => 10000000,
            'download_id' => 1000000,
            'activate_order_status_id' => 1000000000,
            // fill required junk
            'activate' => 'test',
            'mask' => 'test',
            'filename' => 'test',
            'name' => 'test',
        ];

        $orderDownload = new OrderDownload();
        $errors = [];
        try{
            $orderDownload->validate($data);
        }catch(ValidationException $e){
            $errors = $orderDownload->errors()['validation'];
            //var_Dump($errors);
        }
        $this->assertEquals(4, count($errors));

        //check validation of nullables
        $data = [
            'download_id' => null,
            'remaining_count' => null,
            'percentage' => null,
            'expire_date' => null,
            // fill required junk
            'order_id' => 2,
            'order_product_id' => 6,
            'activate_order_status_id' => 1,
            'activate' => 'test',
            'mask' => 'test',
            'filename' => 'test',
            'name' => 'test',
        ];

        $orderDownload = new OrderDownload();
        $errors = [];
        try{
            $orderDownload->validate($data);
        }catch(ValidationException $e){
            $errors = $orderDownload->errors()['validation'];
            //var_Dump($errors);
        }
        $this->assertEquals(0, count($errors));


        //valid data
        $data = [
            'order_id' => 2,
            'order_product_id' => 6,
            'name' => 'test-download',
            'filename' => 'http://',
            'mask' => 'test-mask',
            'download_id' => 1,
            'status' => 0,
            'remaining_count' => 458,
            'percentage' => 0,
            'expire_date' => '2019-05-01 00:00:00',
            'sort_order' => 1,
            'activate' => 'sssssss',
            'activate_order_status_id' => 1,
            'attributes_data' => ['somedata' => 'somevalue']
        ];

        $orderDownload = new OrderDownload( $data );
        $errors = [];
        try{
            $orderDownload->validate($data);
            $orderDownload->save();
        }catch(ValidationException $e){
            $errors = $orderDownload->errors()['validation'];
           // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
            var_dump($errors);
        }
        $this->assertEquals(0, count($errors));
        $orderDownload->forceDelete();
    }

}