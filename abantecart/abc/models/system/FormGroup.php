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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FormGroup
 *
 * @property int $group_id
 * @property string $group_name
 * @property int $form_id
 * @property int $sort_order
 * @property int $status
 *
 * @property Form $form
 * @property Collection $fields_group_descriptions
 * @property Collection $fields_groups
 *
 * @package abc\models
 */
class FormGroup extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'group_id';
    public $timestamps = false;

    protected $casts = [
        'form_id'    => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'group_name',
        'form_id',
        'sort_order',
        'status',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function fields_group_descriptions()
    {
        return $this->hasMany(FieldsGroupDescription::class, 'group_id');
    }

    public function fields_groups()
    {
        return $this->hasMany(FieldsGroup::class, 'group_id');
    }
}
