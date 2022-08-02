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
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FormDescription
 *
 * @property int $form_id
 * @property int $language_id
 * @property string $description
 *
 * @property Form $form
 * @property Language $language
 *
 * @package abc\models
 */
class FormDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'form_id',
        'language_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'form_id'     => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'description',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
