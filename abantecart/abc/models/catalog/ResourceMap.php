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
namespace abc\models\catalog;

use abc\models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ResourceMap
 *
 * @property int $resource_id
 * @property string $object_name
 * @property int $object_id
 * @property bool $default
 * @property int $sort_order
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property ResourceLibrary $resource_library
 *
 * @package abc\models
 */
class ResourceMap extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'resource_id',
        'object_id',
        'object_name',
    ];

    protected $table = 'resource_map';
    public $timestamps = false;

    protected $casts = [
        'resource_id' => 'int',
        'object_id'   => 'int',
        'default'     => 'bool',
        'sort_order'  => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'default',
        'sort_order',
        'date_added',
        'date_modified',
    ];

    public function resource_library()
    {
        return $this->belongsTo(ResourceLibrary::class, 'resource_id');
    }
}
