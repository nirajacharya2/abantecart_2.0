<?php


namespace abantecart\tests;

use abc\core\engine\Registry;
use abc\core\lib\AUser;

/**
 * Class $testClassName
 */
class ABACTest extends ABCTestCase{


    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \PhpAbac\Abac
     */
    protected $abac;

    protected function SetUp(){
        //init
        /**
         * @var Registry
         */
        $this->registry = Registry::getInstance();
        $this->abac = $this->registry->get('abac');
    }

    public function testIsAbacPresents(){
        $this->assertEquals(true, $this->abac instanceof \PhpAbac\Abac);
    }

    public function testTopAdminAccess(){
        //login user as topAdmin
        $this->registry->get('session')->data['user_id'] = 1;
        $user = new AUser($this->registry);
        try {
            $result = $this->abac->enforce('top-admin-access', $user);
        }catch(\Error $e){
            $this->fail($e->getMessage());
        }

        $this->assertEquals(true, $result);
    }

    public function testTopAdminForbid(){
        //login user as Demonstration Admin
        $this->registry->get('session')->data['user_id'] = 2;
        $user = new AUser($this->registry);
        try {
            $result = $this->abac->enforce('top-admin-access', $user);
        }catch(\Error $e){
            $this->fail($e->getMessage());
        }

        $this->assertEquals(false, $result);
    }
}