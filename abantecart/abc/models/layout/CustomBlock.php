<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomBlock
 *
 * @property int $custom_block_id
 * @property int $block_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Block $block
 * @property \Illuminate\Database\Eloquent\Collection $block_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $custom_lists
 *
 * @package abc\models
 */
class CustomBlock extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'custom_lists'];
    protected $primaryKey = 'custom_block_id';

    public $timestamps = false;

    protected $casts = [
        'block_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'date_added',
        'date_modified',
    ];

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    public function descriptions()
    {
        return $this->hasMany(BlockDescription::class, 'custom_block_id');
    }

    public function custom_lists()
    {
        return $this->hasMany(CustomList::class, 'custom_block_id');
    }
}
