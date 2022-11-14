<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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
use abc\models\catalog\Category;

class ControllerCommonSeoUrl extends AController
{
    protected $is_set_canonical = false;

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $get =& $this->request->get;
        if (isset($get['_route_'])) {
            $parts = explode('/', $get['_route_']);
            //Possible area for improvement. Only need to check last node in the path
            foreach ($parts as $part) {
                $query = $this->db->query("SELECT query
											FROM ".$this->db->table_name('url_aliases')."
											WHERE keyword = '".$this->db->escape($part)."'");
                //Add caching of the result.
                if ($query->num_rows) {
                    //Note: query is a field containing area=id to identify location
                    $url = explode('=', $query->row['query']);

                    if ($url[0] == 'product_id') {
                        $get['product_id'] = $url[1];
                    }

                    if ($url[0] == 'category_id') {
                        if (!isset($get['path'])) {
                            $category = Category::find($url[1]);
                            $path = $category ? $category->path : '';
                            $get['path'] = $path;
                        } else {
                            $get['path'] .= '_'.$url[1];
                        }
                    }

                    if ($url[0] == 'manufacturer_id') {
                        $get['manufacturer_id'] = $url[1];
                    }

                    if ($url[0] == 'content_id') {
                        $get['content_id'] = $url[1];
                    }
                } else {
                    $get['rt'] = 'pages/error/not_found';
                }
            }

            if (isset($get['product_id'])) {
                $get['rt'] = 'pages/product/product';
            } elseif (isset($get['path'])) {
                $get['rt'] = 'pages/product/category';
            } elseif (isset($get['manufacturer_id'])) {
                $get['rt'] = 'pages/product/manufacturer';
            } elseif (isset($get['content_id'])) {
                $get['rt'] = 'pages/content/content';
            }
            $this->extensions->hk_ProcessData($this, 'seo_url');
            if (isset($get['rt'])) {
                //build canonical seo-url
                if (sizeof($parts) > 1) {
                    $this->_add_canonical_url(
                        'url',
                        (ABC::env('HTTPS') ? ABC::env('HTTPS_SERVER') : ABC::env('HTTP_SERVER')
                        )
                        .end($parts));
                }

                $rt = $get['rt'];
                //remove pages prefix from rt for use in new generated urls
                if (str_starts_with($get['rt'], 'pages/')) {
                    $get['rt'] = substr($get['rt'], 6);
                }
                unset($get['_route_']);
                $this->_add_canonical_url('seo');
                //Update router with new RT
                $this->router->resetController($rt);
                return $this->dispatch($rt, $get);
            }
        } else {
            $this->_add_canonical_url('seo');
        }

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function _add_canonical_url($mode = 'seo', $url = '')
    {
        if ($this->is_set_canonical || !$this->config->get('enable_seo_url')) {
            return false;
        }
        if (!$url) {
            $method = $mode == 'seo' ? 'getSecureSEOURL' : 'getSecureURL';
            $get =& $this->request->get;
            if (isset($get['product_id'])) {
                $url = $this->html->{$method}('product/product', '&product_id='.$get['product_id']);
            } elseif (isset($get['path'])) {
                $url = $this->html->{$method}('product/category', '&path='.$get['path']);
            } elseif (isset($get['manufacturer_id'])) {
                $url = $this->html->{$method}('product/manufacturer', '&manufacturer_id='.$get['manufacturer_id']);
            } elseif (isset($get['content_id'])) {
                $url = $this->html->{$method}('content/content', '&content_id='.$get['content_id']);
            }
        }

        if ($url) {
            $this->document->addLink(
                [
                    'rel'  => 'canonical',
                    'href' => $url,
                ]
            );
            $this->is_set_canonical = true;
            return true;
        }
        return false;
    }
}
