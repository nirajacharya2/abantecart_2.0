<?php
namespace abc\banner_manager\tests\unit;

use Tests\unit\ATestCase;
use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\Abac;
use abc\core\lib\AUser;
use abc\models\catalog\Product;
use PhpAbac\AbacFactory;

/**
 * Class $testClassName
 */
class ABACTest extends ATestCase{

    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Abac | AbacFactory
     */
    protected $abac;

    protected function SetUp():void{
        //init
        /**
         * @var Registry
         */
        $this->registry = Registry::getInstance();
        $this->abac = $this->registry->get('abac');
    }

    protected function reInitAbacLib(){
        /**
         * @var Abac $abac
         */
        $abac = ABC::getObjectByAlias('ABAC', [ $this->registry ]);
        if(is_object($abac)) {
            $this->registry->set('abac', $abac);
        }else{
            throw new \Exception('Class with alias "ABAC" not found in the classmap!');
        }
    }

    public function testIsAbacPresents(){
        $this->assertEquals(true, $this->abac instanceof Abac);
    }

    //tests for policy group
    public function testSystemUserProductPolicyGroup()
    {

        ABC::env('ABAC',
                [
                    'POLICY_RULES' => [],
                    'CONFIG_DIRECTORY' => ABC::env('DIR_TESTS').DS
                                            .'phpunit'.DS
                                            .'abc'.DS
                                            .'config'.DS
                                            .ABC::getStageName().DS
                                            .'abac'.DS,
                    'CACHE_ENABLE' => true,
                ],
                //override
                true
        );
        $this->reInitAbacLib();

        $result = false;

        try {
            $product = new Product();
            $result = $product->hasPermission('read', ['model', 'sku']);
        }catch(\Error $e){
            $error_msg = $e->getMessage().": file: ".$e->getFile().":".$e->getLine()."\n";
            $this->fail($error_msg.$e->getTraceAsString());
        }

        $this->assertEquals(true, $result);
    }

    public function testAllowToTopAdmin(){
        $result = false;
        //login user as topAdmin
        $this->registry->get('session')->data['user_id'] = 1;
        $this->registry->set('user', ABC::getObjectByAlias('AUser',[$this->registry]));

        //unset object to run test aas admin
        $this->registry->set('os_user', null);
        try {
            $product = new Product();
            $result = $product->hasPermission('read', ['model', 'sku']);
        }catch(\Error $e){
            $this->fail($e->getMessage().": file: ".$e->getFile().":".$e->getLine()."\n");
        }

        $this->assertEquals(true, $result);
    }
/*
    public function testDenyTopAdmin(){
        $result = true;
        //login user as Demonstration Admin
        $this->registry->get('session')->data['user_id'] = 1;
        $this->registry->set('user', new AUser($this->registry));
        $this->registry->set('os_user', null);
        /**
         * @var Abac $abac
         */
/*
        $abac = ABC::getObjectByAlias('ABAC', [ $this->registry ]);
        $this->registry->set('abac', $abac);
        $this->abac = $abac;

        try {
            $product = new Product();
            $result = $product->hasPermission('read', ['model', 'someNonExistColumn']);
        }catch(\Error $e){
            $this->fail($e->getMessage().": file: ".$e->getFile().":".$e->getLine()."\n");
        }

        $this->assertEquals(false, $result);
    }
*/
}