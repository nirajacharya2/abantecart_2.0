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

/**
 * Class OSUser
 *
 * @package abc\core\lib
 */
class OSUser
{
    protected $userId;
    protected $userName;
    protected $userGroup;

    public function __construct()
    {
        $this->userId = function_exists('posix_geteuid') ? posix_geteuid() : '1000';
        $this->userName = function_exists('posix_getpwuid')
            ? posix_getpwuid($this->userId)['name']
            : 'system user';
        $groupId = function_exists('posix_getegid') ? posix_getegid() : 0;
        $groupInfo = function_exists('posix_getgrgid') ? posix_getgrgid($groupId) : ['name' => 'unknown'];
        $this->userGroup = $groupInfo['name'];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->userId;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return (string)$this->userName;
    }

    /**
     * @return string
     */
    public function getUserGroup()
    {
        return (string)$this->userGroup;
    }
}