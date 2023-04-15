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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\core\lib\AException;
use abc\extensions\incentive\models\Incentive;
use abc\extensions\incentive\modules\traits\IncentiveTrait;
use Exception;
use H;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ControllerPagesAccountMyIncentives extends AController
{
    use IncentiveTrait;

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('incentive/incentive');
        $headingTitle = $this->language->t('incentive_heading_title', 'Incentives');
        $this->document->setTitle($headingTitle);
        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/my_incentives'),
                'text'      => $this->language->get('incentive_heading_title'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $request = $this->request->get;
        $incentivesList = [];
        try {
            $incentivesList = $this->getMyIncentives($request);
            foreach ($incentivesList['incentives'] as &$item) {
                $item['details_url'] = $this->html->getSecureURL('r/account/incentive', '&incentive_id=' . $item['incentive_id']);
            }
        } catch (Exception $e) {
            $this->log->error($e->getMessage());
        }

        $this->view->assign('text_empty_list', $this->language->get('incentive_text_empty_list'));
        $this->view->assign('incentivesList', $incentivesList);
        $this->view->assign('heading_title', $headingTitle);

        $this->processTemplate('pages/account/my_incentives.tpl');

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
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
        $output = [];
        $params['language_id'] = $params['language_id'] ?: $this->language->getLanguageID();
        Incentive::setCurrentLanguageID($params['language_id']);
        $incentives = Incentive::getCustomerIncentives($this->checkout, $params);
        foreach ($incentives as &$incentive) {
            $this->getIncentiveResource($incentive);
            $output[$incentive['incentive_id']] = $incentive;
        }

        return [
            'total'      => count($output),
            'incentives' => $output
        ];
    }
}
