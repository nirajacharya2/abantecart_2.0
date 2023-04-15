<?php

namespace abc\controllers\storefront;

use abc\core\engine\ASecureControllerAPI;
use abc\core\lib\AException;
use abc\extensions\incentive\models\Incentive;
use abc\extensions\incentive\modules\traits\IncentiveTrait;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class ControllerApiIncentiveIncentive
 *
 * @package abc\controllers\storefront
 *
 */
class ControllerApiIncentiveIncentive extends ASecureControllerAPI
{
    use IncentiveTrait;

    public function get()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();

        if (!isset($request['incentive_id']) || (int)$request['incentive_id'] === 0) {
            $this->rest->sendResponse(400, [
                'error_code' => 400,
                'error_text' => 'Bad request',
            ]);
            return;
        }

        try {
            $incentive = Incentive::with('description')->find((int)$request['incentive_id'])?->toArray();

            if (!$incentive) {
                $this->rest->sendResponse(404, [
                    'error_code' => 404,
                    'error_text' => 'Not found',
                ]);
                return;
            }

            $this->data['incentive'] = $this->mapIncentiveDataToApiResponse($incentive);
        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->rest->setResponseData([
                'error_code' => 500,
                'error_text' => 'Server error',
            ]);
            $this->rest->sendResponse(500);
            return;
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->rest->setResponseData($this->data['incentive']);
        $this->rest->sendResponse(200);
    }

}
