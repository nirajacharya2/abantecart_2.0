<?php

namespace abc\controllers\admin;


use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\APromotion;

class ControllerResponsesExtensionIncentiveBonusFields extends AController
{

    public function main($params = [])
    {
        $this->loadLanguage('incentive/incentive');

        $bonusId = $this->request->post['bonus_id'];
        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');

        $bonus = $promo->getBonusObjectByKey($bonusId);
        if (!$bonus) {
            $error = new AError('Bonus with key ' . $bonusId . ' not found');
            $error->toJSONResponse('VALIDATION_ERROR_406',
                [
                    'error_text' => $error->msg
                ]
            );
            return;
        }
        $response = $bonus->renderSubform([]);
        $this->response->setOutput(AJson::encode($response));
    }
}