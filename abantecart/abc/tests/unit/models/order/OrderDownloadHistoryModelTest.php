<?php

namespace Tests\unit\models\order;

use abc\models\order\OrderDownload;
use abc\models\order\OrderDownloadsHistory;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class OrderDownloadHistoryModelTest
 */
class OrderDownloadHistoryModelTest extends ATestCase
{

    protected function setUp():void
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'order_id'         => 'fail',
            'order_product_id' => 'fail',
            'name'             => -0.000000000123232,
            'filename'         => -0.000000000123232,
            'mask'             => -0.000000000123232,
            'download_id'      => 'fail',
            'download_percent' => 'fail',
        ];

        $orderDownloadHistory = new OrderDownloadsHistory();
        $errors = [];
        try {
            $orderDownloadHistory->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderDownloadHistory->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertCount(7, $errors);

        //check validation of presence in database
        $data = [
            'order_id'                 => 10000000,
            'order_product_id'         => 10000000,
            'download_id'              => 1000000,
            'activate_order_status_id' => 1000000000,
            // fill required junk
            'activate'                 => 'test',
            'mask'                     => 'test',
            'filename'                 => 'test',
            'name'                     => 'test',
        ];

        $orderDownloadHistory = new OrderDownloadsHistory();
        $errors = [];
        try {
            $orderDownloadHistory->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderDownloadHistory->errors()['validation'];
            //var_Dump($errors);
        }
        $this->assertCount(4, $errors);

        //valid data
        $data = [
            'order_id'                 => 2,
            'order_product_id'         => 6,
            'name'                     => 'test-download',
            'filename'                 => 'http://',
            'mask'                     => 'test-mask',
            'download_id'              => 1,
            'status'                   => 0,
            'remaining_count'          => 458,
            'percentage'               => 0,
            'expire_date'              => '2019-05-01 00:00:00',
            'sort_order'               => 1,
            'activate'                 => 'sssssss',
            'activate_order_status_id' => 1,
            'attributes_data'          => ['somedata' => 'somevalue'],
        ];

        $orderDownload = new OrderDownload($data);
        $orderDownload->save();

        //valid data
        $data = [
            'order_id'          => 2,
            'order_product_id'  => 6,
            'order_download_id' => $orderDownload->order_download_id,
            'filename'          => 'http://',
            'mask'              => 'test-mask',
            'download_id'       => 1,
            'download_percent'  => 0,
        ];

        $orderDownloadHistory = new OrderDownloadsHistory($data);
        $errors = [];
        try {
            $orderDownloadHistory->validate($data);
            $orderDownloadHistory->save();
        } catch (ValidationException $e) {
            $errors = $orderDownloadHistory->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
            var_dump($errors);
        }
        $this->assertCount(0, $errors);
        $orderDownloadHistory->forceDelete();
        $orderDownload->forceDelete();
    }

}