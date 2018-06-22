<?php
/**
* AbanteCart auto-generated phpunit test file
*/

namespace abantecart\tests;

use abc\core\ABC;

/**
 * Class $testClassName
 */
class testClassName extends AbanteCartTest{


    protected function tearDown(){
        //init

    }

    public function testSomething1(){

        $result = $this->db->table('products')->where('status','>',0)->get()->toArray();

        $this::assertEquals($result, 'ddd');
    }

}