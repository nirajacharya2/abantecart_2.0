<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

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

use abc\core\engine\Registry;

/**
 * Class OrderProcessing
 *
 * @package abc\core\lib
 *
 *
 */
class CheckOutAdmin extends CheckoutBase
{

    public function __construct(Registry $registry, array $data)
    {
        parent::__construct($registry, $data);
        //allow to add disabled products and
        $this->conciergeMode = true;
    }

}
