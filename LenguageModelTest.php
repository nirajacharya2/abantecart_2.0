<?php
namespace abc\tests\unit;
use abc\models\locale\Language;
use Illuminate\Validation\ValidationException;
/**
 * Class LenguageModelTest
 */
class LenguageModelTest extends ATestCase{


    public function testValidator()
    {

        $language = new Language(
            [
                'name' => 43647890965,
                'code' => 456456,
                'locale'=> '',
                'image'=> 'dfgdsxfhgxfdghsfghjfgdhfgjhdfjdfhjfdgjcfgjhdgfhdfhgdfgdfgdfgdfgdfgdfgdfgdfgdfgdfgdfgdf',
                'directory'=>'',
                'filename'=>'sdhjfdzgfsdfgghfgfdggdfgdfghfdtgdsfgfdghfghbdfhfghdfgdfgdfgdfgdfgdfgdfgdfgdfgdfgdfgdfg',
                'sort_order'=>'fdjfgfh',
                'status'=>'dfsgdfgdfgdfgdfg'
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors =$language->errors()['validation'];
        }
        $this->assertEquals(8, count($errors));

        $language= new Language(
            [

                'name' => 'somestring',
                'code' => 'g',
                'locale'=> 'somestring',
                'image'=> 'somestring',
                'directory'=>'somestring',
                'filename'=>'somestring',
                'sort_order'=>1,
                'status'=>1
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors = $language->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

    }
}