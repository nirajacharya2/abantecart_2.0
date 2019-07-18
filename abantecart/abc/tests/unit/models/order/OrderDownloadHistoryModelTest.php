<?php
namespace abc\tests\unit;

use abc\models\order\OrderDownloadsHistory;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderDownloadHistoryModelTest
 */
class OrderDownloadHistoryModelTest extends ATestCase{


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
            'download_percent' => 'fail',
        ];

        $orderDataType = new OrderDownloadsHistory(  );
        $errors = [];
        try{
            $orderDataType->validate($data);
        }catch(ValidationException $e){
            $errors = $orderDataType->errors()['validation'];
           // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(7, count($errors));

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

        $orderDataType = new OrderDownloadsHistory();
        $errors = [];
        try{
            $orderDataType->validate($data);
        }catch(ValidationException $e){
            $errors = $orderDataType->errors()['validation'];
            //var_Dump($errors);
        }
        $this->assertEquals(4, count($errors));

        //valid data
        $data = [
            'order_id' => 2,
            'order_product_id' => 6,
            'order_download_id' => 1,
            'filename' => 'http://',
            'mask' => 'test-mask',
            'download_id' => 1,
            'download_percent' => 0,
        ];

        $orderDataType = new OrderDownloadsHistory( $data );
        $errors = [];
        try{
            $orderDataType->validate($data);
            $orderDataType->save();
        }catch(ValidationException $e){
            $errors = $orderDataType->errors()['validation'];
           // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
            var_dump($errors);
        }
        $this->assertEquals(0, count($errors));
    }

}