<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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

class AException extends \Exception
{

    public $registry;
    protected $error;

    public function __construct($errstr = '', $errno = 0, $file = '', $line = '')
    {
        $this->code = $errno ? $errno : $this->code;
        $this->message = $errstr ? $errstr : $this->message;
        $this->file = $file ? $file : $this->file;
        $this->line = $line ? $line : $this->line;

        parent::__construct();
    }
}
