<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PagesLayout
 *
 * @property int $layout_id
 * @property int $page_id
 *
 * @property Layout $layout
 * @property Page $page
 *
 * @package abc\models
 */
class PagesLayout extends BaseModel
{

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'page_id',
        'layout_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'layout_id' => 'int',
        'page_id'   => 'int',
    ];

    public function layout()
    {
        return $this->belongsTo(Layout::class, 'layout_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
