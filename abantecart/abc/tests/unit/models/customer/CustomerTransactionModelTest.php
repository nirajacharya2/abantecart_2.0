<?php
namespace abc\tests\unit;

use abc\core\lib\ACustomer;
use abc\models\customer\CustomerTransaction;
use Illuminate\Validation\ValidationException;

/**
 * Class CustomerTransactionModelTest
 */
class CustomerTransactionModelTest extends ATestCase{



    public function testValidator()
    {
        //test fail
        $customerTransaction = new CustomerTransaction();
        $errors = [];
        try{
            $customerTransaction->validate();
        }catch(ValidationException $e){
            $errors = $customerTransaction->errors()['validation'];
        }

        $this->assertEquals(5, count($errors));//validate new customer

        $errors = [];

        $validData = [
                            'customer_id' => 9,
                            'order_id' => 2,
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

        $this->assertEquals(0, count($errors));

        $customerTransaction->fill($validData)->save();

        //check updating restriction
        $caught = false;
        try{
            $customerTransaction->update(['transaction_type' => 'blablabla']);
        }catch(\Exception $e){
            $caught = true;
        }

        $this->assertEquals(true, $caught);

        /**
         * test preventing of duplicates
         * @see ACustomer::debitTransaction()
         * */
        CustomerTransaction::firstOrCreate($validData);
        $count = CustomerTransaction::where($validData)->get()->count();
        $this->assertEquals(1, $count);

        $customerTransaction->forceDelete();

    }
}