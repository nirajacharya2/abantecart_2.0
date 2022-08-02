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
use abc\models\layout\PagesForm;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Form
 *
 * @property int $form_id
 * @property string $form_name
 * @property string $controller
 * @property string $success_page
 * @property int $status
 *
 * @property Collection $fields
 * @property Collection $form_descriptions
 * @property Collection $form_groups
 * @property Collection $pages_forms
 *
 * @package abc\models
 */
class Form extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['fields', 'descriptions', 'groups', 'pages'];

    protected $primaryKey = 'form_id';
    public $timestamps = false;

    protected $casts = [
        'status' => 'int',
    ];

    protected $fillable = [
        'form_name',
        'controller',
        'success_page',
        'status',
    ];

    public function fields()
    {
        return $this->hasMany(Field::class, 'form_id');
    }

    public function descriptions()
    {
        return $this->hasMany(FormDescription::class, 'form_id');
    }

    public function groups()
    {
        return $this->hasMany(FormGroup::class, 'form_id');
    }

    public function pages()
    {
        return $this->hasMany(PagesForm::class, 'form_id');
    }
}
