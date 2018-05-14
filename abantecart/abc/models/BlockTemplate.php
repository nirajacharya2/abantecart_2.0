<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcBlockTemplate
 *
 * @property int                 $block_id
 * @property int                 $parent_block_id
 * @property string              $template
 * @property \Carbon\Carbon      $date_added
 * @property \Carbon\Carbon      $date_modified
 *
 * @property \abc\models\AcBlock $block
 *
 * @package abc\models
 */
class BlockTemplate extends AModelBase
{
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
