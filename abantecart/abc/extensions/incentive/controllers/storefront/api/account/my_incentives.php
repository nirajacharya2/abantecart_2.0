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

namespace abc\controllers\storefront;

use abc\core\engine\ASecureControllerAPI;
use abc\core\lib\AException;
use abc\extensions\incentive\models\Incentive;
use abc\extensions\incentive\modules\traits\IncentiveTrait;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class ControllerApiAccountMyIncentives
 *
 * @package abc\controllers\storefront
 *
 */
class ControllerApiAccountMyIncentives extends ASecureControllerAPI
{
    use IncentiveTrait;

    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/account/my_incentives",
     *     summary="Get account incentives",
     *     description="Get current active incentives per customer",
     *     tags={"Account"},
     *     security={{"tokenAuth":{}, "apiKey":{}}},
     *
     *    @OA\Parameter(
     *         name="language_id",
     *         in="query",
     *         required=true,
     *         description="Language Id",
     *        @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="Cactual customer's incentives data",
     *         @OA\JsonContent(ref="#/components/schemas/MyIncentivesResponseModel"),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not Found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *      @OA\Response(
     *         response="500",
     *         description="Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     )
     * )
     *
     */
    public function get()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();

        try {
            $this->data['incentives'] = $this->getMyIncentives($request);
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
        $this->rest->setResponseData($this->data['incentives']);
        $this->rest->sendResponse(200);
    }

    /**
     * @param array $params
     * @return array
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    protected function getMyIncentives($params): array
    {
        $params['language_id'] = $params['language_id'] ?: $this->language->getLanguageID();
        Incentive::setCurrentLanguageID($params['language_id']);
        $incentives = Incentive::getCustomerIncentives($this->checkout, $params);

        foreach ($incentives as &$incentive) {
            $incentive = $this->mapIncentiveDataToApiResponse($incentive);
        }

        return [
            'total'      => count($incentives),
            'incentives' => $incentives
        ];
    }
}


/**
 * Class MyIncentivesResponseModel.
 *
 * @OA\Schema (
 *     description="MyIncentives Response",
 *     title="My Incentives Response Model",
 *     schema="MyIncentivesResponseModel"
 * )
 */
class MyIncentivesResponseModel
{
    /**
     * @OA\Property(
     *     description="Actual customer incentives list",
     * )
     *
     * @var int
     */
    public int $total;
    /**
     * @OA\Property(
     *     description="Incentives list",
     *     @OA\Items(
     *     ref="#/components/schemas/IncentiveModel"
     *  )
     * )
     *
     * @var array
     */
    public array $incentives;
}