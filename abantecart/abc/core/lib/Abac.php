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
use abc\core\lib\contracts\AbacInterface;
use PhpAbac\AbacFactory;
use abc\modules\injections\phpabac\Configuration;

class Abac implements AbacInterface
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
     * @var UserResolver
     */
    protected $userResolver;
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
        $ruleSrcDirectory = '';

        $config = ABC::env('ABAC');
        if($config) {
            if ($config['ENABLE_CACHE']) {
                $cacheOptions = [
                    'cache_result' => true,
                    'cache_driver' => $config['CACHE_DRIVER'],
                    'cache_ttl'    => $config['CACHE_TTL'],
                ];
            }
            $ruleSrcDirectory = $config['CONFIG_DIRECTORY'] ?: '';
            $attribute_options = $config['ATTRIBUTE_OPTIONS'] ?: [];
            $ruleSrc = $config['POLICY_RULES'] ?: [];
        }

        //check slash at the end of path
        $ruleSrcDirectory =
            $ruleSrcDirectory && substr((string)$ruleSrcDirectory,-1) != DS
            ? $ruleSrcDirectory.DS
            : $ruleSrcDirectory;

        if(!$ruleSrc && !is_dir($ruleSrcDirectory)){
//???? what to do if no policies at all?
return null;
            throw new \Exception(
                            'Empty rules list for ABAC class!'
                            .'Please check file '.ABC::env('DIR_CONFIG').ABC::getStageName().DS.'config.php');
        }

        //detect user type and use it in configuration loader
        $this->userResolver = ABC::getObjectByAlias('UserResolver', [$this->registry]);
        if(!$this->userResolver){
            throw new \Exception(
                __CLASS__.': Cannot recognize user type! 
                Please check alias UserResolver in config/*/classmap.php file'
            );
        }

        $abac::setConfiguration(new Configuration($ruleSrc, $ruleSrcDirectory.$this->userResolver->getUserType()));
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
// disable for now
//??? return true while all policies will be presents
return true;

//if no abac-factory - allow all
if(!$this->abac){
    return true;
}

        $resource = !$resource ? $this->userResolver->getUserObject() : $resource;
        //prefix needed to separate rules for each user type
        $rulePrefix = $this->userResolver->getUserType()."-".$this->userResolver->getUserGroupId().".";

        $result = $this->abac->enforce(
            $rulePrefix.$rule_name,
            $this->userResolver->getUserObject(),
            $resource,
            $options
        );
        $errors = $this->abac->getErrors();
        if($errors){
            //$this->registry->get('log')->write('ABAC Errors:'.var_export($errors, true));
        }
        return $result;
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