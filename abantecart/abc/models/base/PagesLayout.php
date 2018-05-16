<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class PagesLayout
 *
 * @property int $layout_id
 * @property int $page_id
 *
 * @property \abc\models\Layout $layout
 * @property \abc\models\Page $page
 *
 * @package abc\models
 */
class PagesLayout extends AModelBase
{
    public $incrementing = false;
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
