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

namespace abc\modules\traits;

use abc\core\ABC;
use abc\core\lib\AException;
use abc\models\layout\Block;
use abc\models\layout\Layout;

trait LayoutTrait
{
    /**
     * @param int $layout_id
     *
     * @return array|null
     * @throws AException
     */
    public function getAllLayoutBlocks(int $layout_id)
    {
        if (!$layout_id) {
            throw new AException(
                'No layout specified for getLayoutBlocks!' . $layout_id,
                AC_ERR_LOAD_LAYOUT
            );
        }

        $query = Block::select(
            [
                'blocks.*',
                'block_layouts.*'
            ]
        )->join(
            'block_layouts',
            'block_layouts.block_id',
            '=',
            'blocks.block_id'
        )->where('block_layouts.layout_id', '=', $layout_id);

        if (ABC::env('IS_ADMIN') !== true) {
            $query->where('block_layouts.status', '=', 1);
        }
        return $query->orderBy('block_layouts.parent_instance_id')
            ->orderBy('block_layouts.position')
            ->useCache('layout')->get()?->toArray();
    }

    /**
     * @param string $templateTextId
     * @param int|null $pageId
     * @param int|null $layoutType
     *
     * @return array
     */
    public function getLayouts(string $templateTextId, ?int $pageId, ?int $layoutType = null)
    {
        //No page id, not need to be here
        if (!$templateTextId) {
            return [];
        }

        $query = Layout::select('layouts.*')
            ->join(
                'pages_layouts',
                'pages_layouts.layout_id',
                '=',
                'layouts.layout_id'
            )
            ->where('layouts.template_id', '=', $templateTextId);

        if ($pageId) {
            $query->where('pages_layouts.page_id', '=', $pageId);
        }

        if (isset($layoutType)) {
            $query->where('layouts.layout_type', '=', $layoutType);
        }
        return (array)$query
            ->orderBy('layouts.layout_id')
            ->useCache('layout')
            ->get()?->toArray();
    }
}