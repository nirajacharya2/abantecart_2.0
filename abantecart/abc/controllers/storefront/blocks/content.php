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

use abc\core\engine\AController;
use abc\core\lib\AException;
use abc\models\content\Content;
use Psr\SimpleCache\InvalidArgumentException;

class ControllerBlocksContent extends AController
{
    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->data['heading_title'] = $this->language->get('heading_title', 'blocks/content');
        $this->data['text_home'] = $this->language->get('text_home');
        $this->loadLanguage('common/header');
        // build static links
        $this->data['text_special'] = $this->language->get('text_special');
        $this->data['text_contact'] = $this->language->get('text_contact');
        $this->data['text_sitemap'] = $this->language->get('text_sitemap');
        $this->data['text_bookmark'] = $this->language->get('text_bookmark');
        $this->data['text_account'] = $this->language->get('text_account');
        $this->data['text_login'] = $this->language->get('text_login');
        $this->data['text_logout'] = $this->language->get('text_logout');
        $this->data['text_cart'] = $this->language->get('text_cart');
        $this->data['text_checkout'] = $this->language->get('text_checkout');

        $this->data['home'] = $this->html->getHomeURL();
        $this->data['special'] = $this->html->getNonSecureURL('product/special');
        $this->data['contact'] = $this->html->getNonSecureURL('content/contact');
        $this->data['sitemap'] = $this->html->getNonSecureURL('content/sitemap');
        $this->data['account'] = $this->html->getSecureURL('account/account');
        $this->data['logged'] = $this->customer->isLogged();
        $this->data['login'] = $this->html->getSecureURL('account/login', '', true);
        $this->data['logout'] = $this->html->getSecureURL('account/logout');
        $this->data['cart'] = $this->html->getSecureURL('checkout/cart');
        $this->data['checkout'] = $this->html->getSecureURL('checkout/shipping');

        $allContents = (array)Content::getContents(['filter' => ['status' => 1]])?->toArray();
        $this->data['contents'] = $this->_buildTree($allContents);
        $this->data['contact'] = $this->html->getURL('content/contact');
        $this->data['sitemap'] = $this->html->getNonSecureURL('content/sitemap');

        $this->view->batchAssign($this->data);
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    /**
     * Recursive function for building tree of content.
     *
     * @param array $all_contents
     * @param int $parent_id
     * @param int $level
     *
     * @return array
     * @throws InvalidArgumentException|AException
     */
    protected function _buildTree($all_contents, $parent_id = 0, $level = 0)
    {
        $output = [];
        foreach ($all_contents as $content) {
            if ($content['parent_id'] == $parent_id) {
                $output[] = [
                    'id'    => $content['content_id'],
                    'title' => str_repeat('&nbsp;&nbsp;', $level) . $content['title'],
                    'href'  => $this->html->getSEOURL(
                        'content/content',
                        '&content_id=' . $content['content_id'],
                        '&encode'
                    ),
                    'level' => $level,
                ];
                $output = array_merge($output, $this->_buildTree($all_contents, $content['content_id'], $level + 1));
            }
        }
        return $output;
    }
}
