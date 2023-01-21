<?php
/*  ------------------------------------------------------------------------------
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

use abc\core\engine\AController;
use abc\core\engine\HtmlElementFactory;
use abc\models\content\Content;

class ControllerPagesContentContent extends AController
{
    public function main()
    {
        $request = $this->request->get;

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );

        $content_id = (int)$request['content_id'];
        $content_info = Content::getContent($content_id)?->toArray();
        if (!$content_info) {
            abc_redirect($this->html->getURL('error/not_found'));
        }

        $this->loadModel('tool/seo_url');
        $seoKey = $this->model_tool_seo_url->getSEOKeyword(
            'content_id',
            'content_id',
            $content_info['content_id'],
            $content_info['language_id']
        );
        $seoKey .= ' content-page-class-' . $content_info['content_id'];
        $this->view->assign('page_class', $seoKey);

        $this->document->setTitle($content_info['title']);
        $this->document->setKeywords($content_info['meta_keywords']);
        $this->document->setDescription($content_info['meta_description']);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSEOURL('content/content', '&content_id=' . $request['content_id'], true),
                'text'      => $content_info['title'],
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->view->assign('heading_title', $content_info['title']);
        $this->view->assign('hide_title', $content_info['hide_title']);
        $this->view->assign('button_continue', $this->language->get('button_continue'));

        $this->view->assign('description', html_entity_decode($content_info['description']));
        $this->view->assign('content', html_entity_decode($content_info['content']));
        $continue = HtmlElementFactory::create(
            [
                'type'  => 'button',
                'name'  => 'continue_button',
                'text'  => $this->language->get('button_continue'),
                'style' => 'button',
            ]
        );
        $this->view->assign('button_continue', $continue);
        $this->view->assign('continue', $this->html->getHomeURL());
        $this->view->setTemplate('pages/content/content.tpl');
        $this->processTemplate();
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}