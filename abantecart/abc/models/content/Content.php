<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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

namespace abc\models\content;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use H;

/**
 * Class Content
 *
 * @property int $content_id
 * @property int $parent_content_id
 * @property int $sort_order
 * @property int $status
 *
 * @property ContentDescription $description
 * @property ContentDescription|Collection $descriptions
 * @property ContentsToStore|Collection $stores
 *
 * @package abc\models
 */
class Content extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'stores'];
    public $timestamps = false;

    protected $primaryKey = 'content_id';
    protected $casts = [
        'parent_content_id' => 'int',
        'sort_order'        => 'int',
        'status'            => 'int',
    ];

    protected $fillable = [
        'sort_order',
        'status',
    ];

    public static function getList($data=[])
    {
        $result = [
            'items' => [],
            'total' => 0,
        ];

        $db = Registry::db();

        $arSelect = [
            $db->raw('SQL_CALC_FOUND_ROWS '.$db->table_name('contents').'.content_id'),
            'contents.parent_content_id',
            'contents.status',
            'cd.title',
            'cd.content',
            'cd.date_added',
            'cd.date_modified',
        ];

        $query = self::select($arSelect);
        $query->leftJoin(
            'contents as b',
            'b.content_id',
            '=',
            'contents.parent_content_id'
        );
        $query->Join(
            'content_descriptions as cd',
            'cd.content_id',
            '=',
            'contents.content_id'
        );

        if (H::has_value($data['sort'])) {
            $query = $query->orderBy($data['sort'], H::has_value($data['order']) ? $data['order'] : 'asc');
        }

        if (H::has_value($data['start'])) {
            $query = $query->offset((int)$data['start']);
        }

        if (H::has_value($data['limit'])) {
            $query = $query->limit((int)$data['limit'] ? (int)$data['limit'] : 20);
        }

        if (H::has_value($data['filter']['name'])) {
            $query = $query->where('cd.title', 'like', '%'.$data['filter']['name'].'%');
        }

        if (H::has_value($data['filter']['id'])) {
            $query = $query->where('contents.content_id', $data['filter']['id']);
        }

        if (H::has_value($data['filter']['parent_id'])) {
            $query = $query->where('contents.parent_content_id', $data['filter']['parent_id']);
        }

        if (H::has_value($data['filter']['status'])) {
            $query = $query->where('contents.status', $data['filter']['status']);
        }

        $resultSet = $query->get();

        if ($resultSet) {
            $result['items'] = $resultSet->toArray();
            $result['total'] = $db->sql_get_row_count();
        }
        return $result;
    }
    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(ContentDescription::class, 'content_id', 'content_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(ContentDescription::class, 'content_id');
    }

    public function stores()
    {
        return $this->hasMany(ContentsToStore::class, 'content_id');
    }
}
