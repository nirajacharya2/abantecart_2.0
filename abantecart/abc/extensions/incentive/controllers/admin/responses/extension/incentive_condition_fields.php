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

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\APromotion;
use abc\extensions\incentive\models\Incentive;

class ControllerResponsesExtensionIncentiveConditionFields extends AController
{
    public function main()
    {
        $this->loadLanguage('incentive/incentive');

        $conditionId = $this->request->post['condition_id'];
        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');

        $condition = $promo->getConditionObjectByKey($conditionId);
        if (!$condition) {
            $error = new AError('Condition with key ' . $conditionId . ' not found');
            $error->toJSONResponse('VALIDATION_ERROR_406',
                [
                    'error_text' => $error->msg
                ]
            );
            return;
        }
        $condition->setIncentiveInfo((array)Incentive::with('description')
            ->find($this->request->get['incentive_id'])?->toArray());
        $response = $condition->renderSubform(['idx' => (int)$this->request->post['idx']]);
        $this->response->setOutput(AJson::encode($response));
    }

    public function getConditionsListField()
    {
        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');
        $conditionSection = $this->request->get['section'];
        $conditionSection = !in_array($conditionSection, ['storefront', 'background'])
            ? 'storefront'
            : $conditionSection;

        $form = new AForm ('HT');
        $form->setForm(
            [
                'form_name' => 'incentiveFrm',
            ]
        );

        $this->response->setOutput(
            (string)$form->getFieldHtml(
                [
                    'type'    => 'selectbox',
                    'name'    => 'condition_object',
                    'options' => ['' => $this->language->get('text_select')]
                        + $promo->getConditionList($conditionSection),
                    'value'   => 'noexistvalue!',
                ]
            )
        );
    }
}