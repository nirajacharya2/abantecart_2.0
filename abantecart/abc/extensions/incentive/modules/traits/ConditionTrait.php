<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\extensions\incentive\modules\traits;

use abc\core\engine\Registry;

trait ConditionTrait
{
    protected function getInNotInField($idx, $value)
    {
        return Registry::html()->buildElement
        (
            [
                'type'    => 'selectbox',
                'name'    => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][operator]',
                'options' => [
                    'in'    => Registry::language()->get('text_in'),
                    'notin' => Registry::language()->get('text_not_in'),
                ],
                'value'   => $value,
            ]
        );
    }
}