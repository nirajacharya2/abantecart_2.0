<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use PhpAbac\AbacFactory;

class Abac
{
    /**
     * @var \PhpAbac\Abac
     */
    protected $abac;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var AUser | CliUser | ACustomer
     */
    protected $userObject;
    /**
     * @var string - can be admin, customer or cli
     */
    protected $userType;

    /**
     * Abac constructor.
     *
     * @param Registry $registry
     *
     * @throws \Exception
     */
    public function __construct(Registry $registry)
    {
        /**
         * @var AbacFactory $abac
         */
        $abac = ABC::getFullClassName('ABACFactory');
        if(!$abac){
            throw new \Exception(
                'Alias "ABAC" not found in classmap config! '
                .'Please check file '.ABC::env('DIR_CONFIG').'classmap.php');
        }

        $rules = $cacheOptions = $attribute_options = [];
        $config_directory = null;

        $config = ABC::env('ABAC');
        if($config) {
            if ($config['ENABLE_CACHE']) {
                $cacheOptions = [
                    'cache_result' => true,
                    'cache_driver' => $config['CACHE_DRIVER'],
                    'cache_ttl'    => $config['CACHE_TTL'],
                ];
            }
            $config_directory = $config['CONFIG_DIRECTORY'] ?: null;
            $attribute_options = $config['ATTRIBUTE_OPTIONS'] ?: [];
            $rules = $config['POLICY_RULES'];
        }

        if(!$rules){
            throw new \Exception(
                            'Empty rules lis for ABAC class!'
                            .'Please check file '.ABC::env('DIR_CONFIG').ABC::getStageName().DS.'config.php');
        }
        $this->abac = $abac::getAbac(
            $rules,
            $config_directory,
            $attribute_options,
            $cacheOptions
        );
        $this->registry = $registry;

        $this->userObject = $this->getUserObject();
        if(!$this->userObject){
            throw new \Exception('Unknown user type!');
        }
        return $this->abac;
    }

    /**
     * @param string $rule_name
     * @param null $object
     *
     * @return bool
     */
    public function hasPermission(string $rule_name, $object = null)
    {
        $obj = !$object ? $this->userObject : $object;
        //prefix needed to separate rules for each user type
        $rulePrefix = $this->userType.".";
        return $this->abac->enforce($rulePrefix.$rule_name, $obj);
    }

    protected function getUserObject()
    {
        $userClassName = ABC::getFullClassName('AUser');
        $customerClassName = ABC::getFullClassName('ACustomer');

        if(php_sapi_name() == 'cli' && $this->registry->get('cli_user') instanceof CliUser){
            $this->userType = 'cli';
            return $this->registry->get('cli_user');
        } elseif (
            ABC::env('IS_ADMIN')
            && $this->registry->get('user') instanceof $userClassName)
        {
            $this->userType = 'admin';
            return $this->registry->get('user');
        } elseif (
            ABC::env('IS_ADMIN')
            && $this->registry->get('customer') instanceof $customerClassName)
        {
            $this->userType = 'customer';
            return $this->registry->get('customer');
        }

        return false;
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @return \PhpAbac\AbacFactory
     */
    public function __call($method, $parameters)
    {
        //check if we can call method of abac-factory
        if(method_exists($this->abac, $method)){
            return call_user_func_array([$this->abac,$method], $parameters);
        }
        //also check static method of abac-factory
        return forward_static_call_array([$this->abac, $method], $parameters);
    }
}