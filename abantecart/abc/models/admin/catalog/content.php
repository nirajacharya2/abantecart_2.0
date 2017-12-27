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
namespace abc\models\admin;
use abc\core\engine\Model;
use abc\lib\AContentManager;

if (!class_exists('abc\ABC') || !\abc\ABC::env('IS_ADMIN')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ModelCatalogContent extends Model {
	/**
	 * @return array
	 */
	public function getContents() {
		$contents = array();
		$content_manager = new AContentManager();
        $results = $content_manager->getContents();
        $contents[] = array(
                'content_id' => '0',
                'title' => $this->language->get('text_none')
        );
        foreach( $results as $r ) {
			$contents[] = array(
                                'content_id' => $r['content_id'],
                                'title' => $r['title']
            );
		}
		return $contents;
	}

}