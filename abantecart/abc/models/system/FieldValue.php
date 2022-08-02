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
 * Class FieldValue
 *
 * @property int $value_id
 * @property int $field_id
 * @property string $value
 * @property int $language_id
 *
 * @property Field $field
 *
 * @package abc\models
 */
class FieldValue extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'value_id';
    public $timestamps = false;

    protected $casts = [
        'field_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'field_id',
        'value',
        'language_id',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
