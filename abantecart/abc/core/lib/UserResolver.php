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
use abc\models\customer\Customer;
use abc\models\user\User;

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
     * @var string - AdminName or Customer FirstName+LastName or SystemUserGroupName
     */
    protected $userName;
    /**
     * @var int - user_id
     */
    protected $userId;
    /**
     * @var int | string - userGroupId or CustomerGroupId or SystemUserGroupName
     */
    protected $userGroupId;

    /**
     * @var int - User Id who click act on behalf from admin side
     */
    protected $actoronbehalf;

    /**
     * @var string - ip
     */
    protected $ip;

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
            $this->userGroupId = $user->getUserGroup();
            $this->userObject = $user;
            $this->userName = $user->getUserName();
            $this->userId = null;
            $this->ip = null;
        }
        elseif (
            ABC::env('IS_ADMIN')
            && $this->registry->get('user') instanceof $userClassName)
        {
            /**
             * @var AUser $user
             */
            $user = $this->registry->get('user');
            $this->userType = $user->getUserGroupId() == 1 ? 'root'  : 'admin';
            $this->userGroupId = $user->getUserGroupId();
            $this->userObject = $this->registry->get('user');
            $this->userId = $this->userObject->getId();
            $this->userName = $this->userObject->getUserName();
            $this->ip = $registry->get('request')->getRemoteIP();
            if (!$this->ip) {
                $user = User::find($this->userId);
                if ($user) {
                    $this->ip = $user->ip;
                }
            }
        }
        elseif ( $this->registry->get('customer') instanceof $customerClassName)
        {
            $customer = $this->registry->get('customer');
            $this->userType = 'customer';
            $this->userGroupId = $customer->getCustomerGroupId();
            $this->userObject = $customer;
            $this->userId = $this->userObject->getId();
            $this->userName = $this->userObject->getFirstName().' '.$this->userObject->getLastName();
            $this->actoronbehalf = (int) Registry::session()->data['actoronbehalf'];

            $this->ip = $registry->get('request')->getRemoteIP();
            if (!$this->ip) {
                $customer = Customer::find($this->userId);
                if ($customer) {
                    $this->ip = $customer->ip;
                }
            }
        }else{
            //TODO: add API-user
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
        return $this->userGroupId ?? 'unknown';
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }
    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName ?? 'unknown';
    }

    /**
     * @return object|false
     */
    public function getUserObject()
    {
        return $this->userObject ?? false;
    }

    /**
     * @return string
     */
    public function getUserIp()
    {
        return $this->ip;
    }

    /**
     * @return int
     */
    public function getActoronbehalf()
    {
        return $this->actoronbehalf;
    }


}