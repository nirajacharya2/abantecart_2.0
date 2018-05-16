<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class Block
 *
 * @property int $block_id
 * @property string $block_txt_id
 * @property string $controller
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $block_templates
 * @property \Illuminate\Database\Eloquent\Collection $custom_blocks
 *
 * @package abc\models
 */
class Block extends AModelBase
{
    protected $primaryKey = 'block_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'block_txt_id',
        'controller',
        'date_added',
        'date_modified',
    ];

    public function block_templates()
    {
        return $this->hasMany(BlockTemplate::class, 'block_id');
    }

    public function custom_blocks()
    {
        return $this->hasMany(CustomBlock::class, 'block_id');
    }
}
