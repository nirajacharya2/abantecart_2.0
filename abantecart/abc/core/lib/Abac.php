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
use abc\modules\injections\phpabac\Configuration;

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
     * @var AUser | OSUser | ACustomer
     */
    protected $userObject;
    /**
     * @var string - can be admin, customer or cli
     */
    protected $userType;
    /**
     * @var int | string - userGroupId or CustomerGroupId or SystemUserGroupName
     */
    protected $userGroupID;

    /**
     * Abac constructor.
     *
     * @param Registry $registry
     *
     * @throws \Exception
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
        /**
         * @var AbacFactory $abac
         */
        $abac = ABC::getFullClassName('ABACFactory');

        if(!$abac){
            throw new \Exception(
                'Alias "ABAC" not found in classmap config! '
                .'Please check file '.ABC::env('DIR_CONFIG').'classmap.php');
        }

        $ruleSrc = $cacheOptions = $attribute_options = [];
        $ruleSrcDirectory = null;

        $config = ABC::env('ABAC');
        if($config) {
            if ($config['ENABLE_CACHE']) {
                $cacheOptions = [
                    'cache_result' => true,
                    'cache_driver' => $config['CACHE_DRIVER'],
                    'cache_ttl'    => $config['CACHE_TTL'],
                ];
            }
            $ruleSrcDirectory = $config['CONFIG_DIRECTORY'] ?: null;
            $attribute_options = $config['ATTRIBUTE_OPTIONS'] ?: [];
            $ruleSrc = $config['POLICY_RULES'] ?: [];
        }

        //check slash at the end of path
        $ruleSrcDirectory =
            $ruleSrcDirectory && substr($ruleSrcDirectory,-1) != DS
            ? $ruleSrcDirectory.DS
            : $ruleSrcDirectory;

        if(!$ruleSrc && !is_dir($ruleSrcDirectory)){
return null;
            throw new \Exception(
                            'Empty rules list for ABAC class!'
                            .'Please check file '.ABC::env('DIR_CONFIG').ABC::getStageName().DS.'config.php');
        }

        //detect user type and use it in configuration loader
        $this->userObject = $this->getUserObject();
        if(!$this->userObject){
            throw new \Exception('Unknown user type!');
        }

        $abac::setConfiguration(new Configuration($ruleSrc, $ruleSrcDirectory.$this->userType));
        $this->abac = $abac::getAbac(
            $ruleSrc,
            $ruleSrcDirectory,
            $attribute_options,
            $cacheOptions
        );

        return $this->abac;
    }

    /**
     * @param string $rule_name
     *
     * @param object|null $resource
     *
     * @param array $options
     *
     * @return bool
     */
    public function hasPermission(string $rule_name, $resource = null, $options = [])
    {
//if no rules - allow all
if(!$this->abac){
    return true;
}

        $resource = !$resource ? $this->userObject : $resource;
        //prefix needed to separate rules for each user type
        $rulePrefix = $this->userType."-".$this->userGroupID.".";

        $result = $this->abac->enforce($rulePrefix.$rule_name, $this->userObject, $resource, $options);
        $errors = $this->abac->getErrors();
        if($errors){
            $this->registry->get('log')->write('ABAC Errors:'.var_export($errors, true));
        }
        return $result;
    }

    public function getUserType()
    {
        return $this->userType;
    }
    public function getUserGroupId()
    {
        return $this->userGroupID;
    }

    public function getUserObject()
    {
        $userClassName = ABC::getFullClassName('AUser');
        $customerClassName = ABC::getFullClassName('ACustomer');

        if(php_sapi_name() == 'cli' && $this->registry->get('os_user') instanceof OSUser){
            $this->userType = 'system';
            /**
             * @var OSUser $user
             */
            $user = $this->registry->get('os_user');
            $this->userGroupID = $user->getUserGroup();
            echo "CLI-mode: username: ".$user->getUserName()." userGroup: ".$user->getUserGroup()."\n";
            return $user;
        } elseif (
            ABC::env('IS_ADMIN')
            && $this->registry->get('user') instanceof $userClassName)
        {
            /**
             * @var AUser $user
             */
            $user = $this->registry->get('user');
            $this->userType = $user->getUserGroupId() == 1 ? 'root'  : 'admin';
            $this->userGroupID = $user->getUserGroupId();
            return $this->registry->get('user');
        } elseif (
            ABC::env('IS_ADMIN')
            && $this->registry->get('customer') instanceof $customerClassName)
        {
            $customer = $this->registry->get('customer');
            $this->userType = 'customer';
            $this->userGroupID = $customer->getCustomerGroupId();
            return $customer;
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