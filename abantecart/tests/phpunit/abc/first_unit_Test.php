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
        $result = ABC::env('DIR_ROOT');
        $this::assertEquals($result, 'ddd');
    }

}