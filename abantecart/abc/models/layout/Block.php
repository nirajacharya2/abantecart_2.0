<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\models\layout;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\QueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;

/**
 * Class Block
 *
 * @property int $block_id
 * @property string $block_txt_id
 * @property string $controller
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $block_templates
 * @property Collection $custom_blocks
 *
 * @package abc\models
 */
class Block extends BaseModel
{
    protected $cascadeDeletes = ['templates', 'custom_blocks', 'block_layouts'];
    protected $primaryKey = 'block_id';

    protected $casts = [
        'block_text_id' => 'string',
        'controller' => 'string'
    ];

    protected $fillable = [
        'block_txt_id',
        'controller'
    ];

    protected $rules = [
        /** @see validate() */
        'block_text_id' => [
            'checks'   => [
                'string',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Block Text ID is empty!'],
            ],
        ],
        'controller'    => [
            'checks'   => [
                'string',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Controller Route is empty!'],
            ],
        ],
    ];

    public function templates()
    {
        return $this->hasMany(BlockTemplate::class, 'block_id');
    }

    public function custom_blocks()
    {
        return $this->hasMany(CustomBlock::class, 'block_id');
    }

    /**
     * @param $params
     * @return \Illuminate\Support\Collection
     */
    public static function getBlocks($params)
    {
        $params['language_id'] = (int)$params['language_id'] ?: static::$current_language_id;
        $params['sort'] = $params['sort'] ?: 'blocks.date_added';
        $params['order'] = $params['order'] ?: 'ASC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = abs($params['limit']) ?: 200;
        $filter = (array)$params['filter'];

        $db = Registry::db();
        $coalesce = "COALESCE(" . $db->table_name('custom_blocks') . ".date_added, " . $db->table_name('blocks') . ".date_added)";
        $query = Block::selectRaw(
            $db->raw_sql_row_count() . ' ' . $db->table_name("block_descriptions") . '.*'
        )->addSelect('blocks.*')
            ->selectRaw($coalesce . ' as date_added')
            ->selectRaw(
                "(SELECT MAX(status) AS status "
                . "FROM " . $db->table_name("block_layouts") . " bl "
                . "WHERE bl.custom_block_id = " . $db->table_name("custom_blocks") . ".custom_block_id)  as status")
            ->leftJoin('custom_blocks', 'custom_blocks.block_id', '=', 'blocks.block_id')
            ->leftJoin(
                'block_descriptions',
                function ($join) use ($params) {
                    /** @var JoinClause $join */
                    $join->on('block_descriptions.custom_block_id', '=', 'custom_blocks.custom_block_id')
                        ->where('block_descriptions.language_id', '=', $params['language_id']);
                }
            );
        if ($filter['block_txt_id']) {
            $query->where('blocks.block_txt_id', 'like', '%' . $filter['block_txt_id'] . '%');
        }
        if ($filter['name']) {
            $query->where('block_descriptions.name', 'like', '%' . $filter['name'] . '%');
        }

        $sortRawData = [
            'block_id'     => $db->table_name('blocks') . ".block_id",
            'name'         => 'name',
            'block_txt_id' => $db->table_name('blocks') . ".block_txt_id",
            'status'       => 'status',
            'date_added'   => $coalesce
        ];

        $desc = '';

        if (isset($params['sort']) && in_array($params['sort'], array_keys($sortRawData))) {
            $sortBy = $sortRawData[$params['sort']];
        } else {
            $sortBy = $coalesce;
        }

        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $desc = 'desc';
        }

        $query->orderByRaw($sortBy . ' ' . $desc ?: 'asc')
            ->limit($params['limit'])
            ->offset($params['start']);

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static(), __FUNCTION__, $query, $params);
        $items = $query->useCache('layout')->get();
        $items->total = Registry::db()->sql_get_row_count();
        return $items;
    }

    /**
     * @param int $blockId
     * @return QueryBuilder|Model|object|null
     */
    public static function getBlockInfo(int $blockId)
    {
        return Block::select(
            ['pages_layouts.page_id', 'layouts.*', 'blocks.*']
        )->selectRaw(
            "(SELECT group_concat(template)
                 FROM " . Registry::db()->table_name("block_templates") . " 
                 WHERE block_id='" . $blockId . "') AS templates"
        )->leftJoin("block_layouts", "block_layouts.block_id", "=", "blocks.block_id")
            ->leftJoin("layouts", "layouts.layout_id", "=", "block_layouts.layout_id")
            ->leftJoin("pages_layouts", "pages_layouts.layout_id", "=", "layouts.layout_id")
            ->where('blocks.block_id', '=', $blockId)
            ->orderBy('block_layouts.layout_id')
            ->useCache('layout')
            ->get();
    }
}