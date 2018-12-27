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

class UserResolver
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var AUser | OSUser | ACustomer
     */
    protected $userObject;
    /**
     * @var string - can be admin, customer or system(cli)
     */
    protected $userType;
    /**
     * @var int | string - userGroupId or CustomerGroupId or SystemUserGroupName
     */
    protected $userGroupID;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;

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
            $this->userObject = $user;
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
            $this->userObject = $this->registry->get('user');
        } elseif (
            ABC::env('IS_ADMIN')
            && $this->registry->get('customer') instanceof $customerClassName)
        {
            $customer = $this->registry->get('customer');
            $this->userType = 'customer';
            $this->userGroupID = $customer->getCustomerGroupId();
            $this->userObject = $customer;
        }
        return $this;
    }

    /**
     * @return bool|false
     */
    public function getUserType()
    {
        return $this->userType ?? false;
    }

    /**
     * @return string
     */
    public function getUserGroupId()
    {
        return $this->userGroupID ?? 'unknown';
    }

    /**
     * @return object|false
     */
    public function getUserObject()
    {
        return $this->userObject ?? false;
    }
}