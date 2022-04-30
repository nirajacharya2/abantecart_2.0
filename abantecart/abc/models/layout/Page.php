<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Page
 *
 * @property int $page_id
 * @property int $parent_page_id
 * @property string $controller
 * @property string $key_param
 * @property string $key_value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $page_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $pages_forms
 * @property \Illuminate\Database\Eloquent\Collection $pages_layouts
 *
 * @package abc\models
 */
class Page extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'forms', 'layouts'];

    protected $primaryKey = 'page_id';
    public $timestamps = false;

    protected $casts = [
        'parent_page_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'parent_page_id',
        'controller',
        'key_param',
        'key_value',
        'date_added',
        'date_modified',
    ];

    public function descriptions()
    {
        return $this->hasMany(PageDescription::class, 'page_id');
    }

    public function forms()
    {
        return $this->hasMany(PagesForm::class, 'page_id');
    }

    public function layouts()
    {
        return $this->hasMany(PagesLayout::class, 'page_id');
    }
}
