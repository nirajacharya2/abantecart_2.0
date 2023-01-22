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

use abc\core\engine\AController;
use abc\extensions\banner_manager\models\admin\extension\ModelExtensionBannerManager;
use abc\extensions\banner_manager\models\Banner;
use abc\extensions\banner_manager\models\BannerDescription;
use abc\extensions\banner_manager\models\BannerStat;

/**
 * Class ControllerPagesExtensionBannerManagerStat
 *
 */
class ControllerPagesExtensionBannerManagerStat extends AController
{
    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('banner_manager/banner_manager');
        $this->document->setTitle($this->language->get('banner_manager_name_stat'));

        $this->data['delete_button'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'title' => $this->language->get('text_delete_statistic'),
                'href'  => $this->html->getSecureURL('extension/banner_manager_stat/delete', '&delete=all'),
            ]
        );

        $grid_settings = [
            'table_id'       => 'banner_stat_grid',
            'url'            => $this->html->getSecureURL('listing_grid/banner_manager_stat'),
            'sortname'       => 'date_end',
            'columns_search' => false,
            'multiselect'    => 'false',
            'actions'        => [
                'view' => [
                    'text' => $this->language->get('text_view'),
                    'href' => $this->html->getSecureURL('extension/banner_manager_stat/details', '&banner_id=%ID%'),
                ],
            ],
        ];

        $grid_settings['colNames'] = [
            $this->language->get('column_banner_name'),
            $this->language->get('column_banner_group'),
            $this->language->get('column_clicked'),
            $this->language->get('column_viewed'),
            $this->language->get('column_percent'),
        ];

        $grid_settings['colModel'] = [
            [
                'name'  => 'name',
                'index' => 'name',
                'width' => 250,
                'align' => 'left',
            ],
            [
                'name'  => 'group_name',
                'index' => 'banner_group_name',
                'width' => 160,
                'align' => 'left',
            ],
            [
                'name'  => 'clicked',
                'index' => 'clicked',
                'width' => 40,
                'align' => 'center',
            ],
            [
                'name'  => 'viewed',
                'index' => 'viewed',
                'width' => 120,
                'align' => 'center',
            ],
            [
                'name'  => 'percents',
                'index' => 'percents',
                'width' => 60,
                'align' => 'center',
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('extension/banner_manager_stat'),
                'text'      => $this->language->get('banner_manager_name_stat'),
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/extension/banner_manager_stat.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function details()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('common/header');
        $this->loadLanguage('common/home');
        $this->loadLanguage('banner_manager/banner_manager');
        $this->document->setTitle($this->language->get('banner_manager_name_stat'));

        $bannerId = (int)$this->request->get['banner_id'];

        $this->data['delete_button'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'title' => $this->language->get('text_delete_statistic'),
                'href'  => $this->html->getSecureURL(
                    'extension/banner_manager_stat/delete',
                    '&delete=1&banner_id=' . $bannerId
                ),
            ]
        );

        $this->document->initBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('index/home'),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('extension/banner_manager'),
                'text'      => $this->language->get('banner_manager_name'),
                'separator' => ' :: ',
            ]
        );

        /** @var BannerDescription $bannerDesc */
        $bannerDesc = BannerDescription::where('banner_id', '=', $bannerId)
            ->where('language_id', '=', $this->language->getContentLanguageID())
            ->first();

        $this->data['heading_title'] = $this->language->get('banner_manager_name_stat') . ':  ' . $bannerDesc->name;

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('extension/banner_manager_stat', '&banner_id=' . $bannerId),
                'text'      => $this->data['heading_title'],
                'separator' => ' :: ',
                'current'   => true,
            ]
        );
        $this->data['chart_url'] = $this->html->getSecureURL('extension/banner_manager_chart', '&banner_id=' . $bannerId);

        $this->data['select_range'] = $this->html->buildElement(
            [
                'type'    => 'selectbox',
                'name'    => 'range',
                'options' => [
                    'day'   => $this->language->get('text_day'),
                    'week'  => $this->language->get('text_week'),
                    'month' => $this->language->get('text_month'),
                    'year'  => $this->language->get('text_year'),
                ],
                'value'   => 'day',
            ]
        );

        $this->data['text_count'] = $this->language->get('text_count');
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/extension/banner_manager_stat_details.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function delete()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('banner_manager/banner_manager');

        //prevent random click
        if ($this->request->get['delete'] != '1' && $this->request->get['delete'] != 'all') {
            abc_redirect($this->html->getSecureURL('extension/banner_manager_stat'));
        }

        $bannerId = (int)$this->request->get['banner_id'];
        if ($bannerId) {
            BannerStat::where('banner_id', '=', $bannerId)->delete();
        } else {
            BannerStat::query()->delete();
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->session->data['success'] = $this->language->get('text_delete_success');
        abc_redirect($this->html->getSecureURL('extension/banner_manager_stat'));
    }
}