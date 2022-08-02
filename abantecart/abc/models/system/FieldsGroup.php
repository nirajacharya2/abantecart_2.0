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
namespace abc\models\system;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FieldsGroup
 *
 * @property int $field_id
 * @property int $group_id
 * @property int $sort_order
 *
 * @property Field $field
 * @property FormGroup $form_group
 *
 * @package abc\models
 */
class FieldsGroup extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'field_id',
        'group_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'field_id'   => 'int',
        'group_id'   => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'group_id',
        'sort_order',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function form_group()
    {
        return $this->belongsTo(FormGroup::class, 'group_id');
    }
}
