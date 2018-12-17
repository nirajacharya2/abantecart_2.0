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
 * Class CliUser
 *
 * @package abc\core\lib
 */
class CliUser
{
    protected $userId;
    protected $userName;

    public function __construct() {
        $this->userId = function_exists('posix_geteuid') ? posix_geteuid() : '1000';
        $this->userName = function_exists('posix_getpwuid')
                        ? posix_getpwuid($this->userId)['name']
                        : 'system user';
    }

    /**
     * @return int
     */
    public function getId(){
        return (int)$this->userId;
    }

    /**
     * @return string
     */
    public function getName(){
        return (string)$this->userName;
    }
}