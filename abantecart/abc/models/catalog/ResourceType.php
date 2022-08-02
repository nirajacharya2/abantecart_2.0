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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ResourceType
 *
 * @property int $type_id
 * @property string $type_name
 * @property string $default_directory
 * @property string $default_icon
 * @property string $file_types
 * @property bool $access_type
 *
 * @property Collection $resource_libraries
 *
 * @method static ResourceType find(int $resource_id) ResourceType
 * @method static ResourceType select(mixed $select) Builder
 *
 * @package abc\models
 */
class ResourceType extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $casts = [
        'access_type' => 'bool',
    ];

    protected $fillable = [
        'type_name',
        'default_directory',
        'default_icon',
        'file_types',
        'access_type',
    ];

    public function resource_libraries()
    {
        return $this->hasMany(ResourceLibrary::class, 'type_id');
    }
}
