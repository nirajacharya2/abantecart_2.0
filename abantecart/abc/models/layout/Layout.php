<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Layout
 *
 * @property int $layout_id
 * @property string $template_id
 * @property string $layout_name
 * @property int $layout_type
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $pages_layouts
 *
 * @package abc\models
 */
class Layout extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['pages_layouts', 'block_layouts'];
    protected $primaryKey = 'layout_id';
    public $timestamps = false;

    protected $casts = [
        'layout_type' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'template_id',
        'layout_name',
        'layout_type',
        'date_added',
        'date_modified',
    ];

    public function pages_layouts()
    {
        return $this->hasMany(PagesLayout::class, 'layout_id');
    }

    public function block_layouts()
    {
        return $this->hasMany(BlockLayout::class, 'layout_id');
    }
}
