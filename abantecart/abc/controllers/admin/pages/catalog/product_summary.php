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

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\models\order\Order;

class ControllerPagesCatalogProductSummary extends AController
{
    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');

        $product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
        $this->data['product'] = $product_info;
        $this->data['product']['product_id'] = '#'.$this->data['product']['product_id'];
        $this->data['product']['price'] = $this->currency->format($this->data['product']['price']);
        $this->data['product']['condition'] = $this->model_catalog_product->getProductCondition(
            $this->request->get['product_id']
        );

        $this->data['text_product_condition'] = $this->language->get('text_product_condition');
        $this->data['text_product_available'] = $this->language->get('text_product_available');

        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb(
            'products',
            $this->request->get['product_id'],
            $this->config->get('config_image_grid_width'),
            $this->config->get('config_image_grid_height'),
            true
        );
        $this->data['product']['image'] = $thumbnail;
        $this->data['product']['preview'] = $this->html->getCatalogURL(
            'product/product',
            '&product_id='.$product_info['product_id']
        );

        $this->data['auditLog'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'text'  => $this->language->get('text_audit_log'),
                'href'  => $this->html->getSecureURL(
                    'tool/audit_log',
                    '&modal_mode=1'
                    .'&auditable_type=Product'
                    .'&auditable_id='.$this->request->get['product_id']
                ),
                //quick view port URL
                'vhref' => $this->html->getSecureURL(
                    'r/common/viewport/modal',
                    '&viewport_rt=tool/audit_log'
                    .'&modal_mode=1'
                    .'&auditable_type=Product'
                    .'&auditable_id='.$this->request->get['product_id']
                ),
            ]
        );

        $this->data['product']['orders'] = Order::search(
            [
                'filter' => [
                    'product_id' => $product_info['product_id'],
                ],
                'mode'   => 'total_only',
            ]
        );

        $this->data['product']['orders_url'] = $this->html->getSecureURL(
            'sale/order',
            '&product_id='.$product_info['product_id']);

        $this->view->assign('help_url', $this->gen_help_url('product_summary'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/catalog/product_summary.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

}
