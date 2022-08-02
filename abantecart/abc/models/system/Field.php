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
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Field
 *
 * @property int $field_id
 * @property int $form_id
 * @property string $field_name
 * @property string $element_type
 * @property int $sort_order
 * @property string $attributes
 * @property string $settings
 * @property string $required
 * @property int $status
 * @property string $regexp_pattern
 *
 * @property Form $form
 * @property Collection $field_descriptions
 * @property Collection $field_values
 * @property FieldsGroup $fields_group
 *
 * @package abc\models
 */
class Field extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'values', 'group'];

    protected $primaryKey = 'field_id';
    public $timestamps = false;

    protected $casts = [
        'form_id'    => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'form_id',
        'field_name',
        'element_type',
        'sort_order',
        'attributes',
        'settings',
        'required',
        'status',
        'regexp_pattern',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function description()
    {
        return $this->hasOne(FieldDescription::class, 'field_id', 'field_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(FieldDescription::class, 'field_id');
    }

    public function values()
    {
        return $this->hasMany(FieldValue::class, 'field_id');
    }

    public function group()
    {
        return $this->hasOne(FieldsGroup::class, 'field_id');
    }
}
