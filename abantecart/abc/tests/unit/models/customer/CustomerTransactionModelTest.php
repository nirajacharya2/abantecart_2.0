<?php
namespace Tests\unit\models\customer;

use abc\core\lib\ACustomer;
use abc\models\customer\CustomerTransaction;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class CustomerTransactionModelTest
 */
class CustomerTransactionModelTest extends ATestCase{

    public function testValidator()
    {
        $customer_id = 9;
        $order_id = 2;
        CustomerTransaction::where(['customer_id' => $customer_id, 'order_id' => $order_id])->forceDelete();

        //test fail
        $customerTransaction = new CustomerTransaction();
        $errors = [];
        try{
            $customerTransaction->validate();
        }catch(ValidationException $e){
            $errors = $customerTransaction->errors()['validation'];
        }

        $this->assertCount(5, $errors);//validate new customer

        $errors = [];

        $validData = [
                            'customer_id' => $customer_id,
                            'order_id' => $order_id,
                            'created_by' => 1,
                            'credit' => '0',
                            'debit' => '121254.2365',
                            'transaction_type' => 'unittest transaction',
                            'comment' => 'test comment',
                            'description' => 'test description',
                        ];
        try{
            $customerTransaction->validate($validData);
        }catch(ValidationException $e){
            $errors = $customerTransaction->errors()['validation'];
        }

        $this->assertCount(0, $errors);

        $customerTransaction->fill($validData)->save();

        //check updating restriction
        try{
            $customerTransaction->update(['transaction_type' => 'blablabla']);
        }catch(\Exception $e){}

        $customerTransaction = CustomerTransaction::find( $customerTransaction->customer_transaction_id );
        $this->assertEquals('unittest transaction', $customerTransaction->transaction_type);

        /**
         * test preventing of duplicates
         * @see ACustomer::debitTransaction()
         * */
        CustomerTransaction::updateOrCreate($validData);
        $ct = CustomerTransaction::where($validData)->get();
        $count = $ct->count();
        $this->assertEquals(1, $count);


    }
}