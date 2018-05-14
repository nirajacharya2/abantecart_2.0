<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcCustomBlock
 *
 * @property int                                      $custom_block_id
 * @property int                                      $block_id
 * @property \Carbon\Carbon                           $date_added
 * @property \Carbon\Carbon                           $date_modified
 *
 * @property \abc\models\AcBlock                      $block
 * @property \Illuminate\Database\Eloquent\Collection $block_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $custom_lists
 *
 * @package abc\models
 */
class CustomBlock extends AModelBase
{
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

    public function block_descriptions()
    {
        return $this->hasMany(BlockDescription::class, 'custom_block_id');
    }

    public function custom_lists()
    {
        return $this->hasMany(CustomList::class, 'custom_block_id');
    }
}
