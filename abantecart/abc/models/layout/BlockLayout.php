<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BlockLayout
 *
 * @property int $instance_id
 * @property int $layout_id
 * @property int $block_id
 * @property int $custom_block_id
 * @property int $parent_instance_id
 * @property int $position
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class BlockLayout extends BaseModel
{

    protected $primaryKey = 'instance_id';
    public $timestamps = false;

    protected $casts = [
        'layout_id'          => 'int',
        'block_id'           => 'int',
        'custom_block_id'    => 'int',
        'parent_instance_id' => 'int',
        'position'           => 'int',
        'status'             => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'layout_id',
        'block_id',
        'custom_block_id',
        'parent_instance_id',
        'position',
        'status',
        'date_added',
        'date_modified',
    ];

    public function children()
    {
        return $this->HasMany(BlockLayout::class, 'instance_id', 'parent_instance_id');
    }

    public function layout()
    {
        return $this->belongsTo(Layout::class, 'layout_id');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    public function custom_block()
    {
        return $this->belongsTo(CustomBlock::class, 'custom_block_id');
    }

}
