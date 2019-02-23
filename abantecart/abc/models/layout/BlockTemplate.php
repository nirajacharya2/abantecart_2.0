<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BlockTemplate
 *
 * @property int $block_id
 * @property int $parent_block_id
 * @property string $template
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 *
 * @package abc\models
 */
class BlockTemplate extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $primaryKey = 'block_id';
    public $timestamps = false;

    protected $casts = [
        'parent_block_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'template',
        'date_added',
        'date_modified',
    ];

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }
}
