<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class CustomList
 *
 * @property int                     $rowid
 * @property int                     $custom_block_id
 * @property string                  $data_type
 * @property int                     $id
 * @property int                     $sort_order
 * @property \Carbon\Carbon          $date_added
 * @property \Carbon\Carbon          $date_modified
 *
 * @property \abc\models\CustomBlock $custom_block
 *
 * @package abc\models
 */
class CustomList extends AModelBase
{
    protected $primaryKey = 'rowid';
    public $timestamps = false;

    protected $casts = [
        'custom_block_id' => 'int',
        'id'              => 'int',
        'sort_order'      => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'custom_block_id',
        'data_type',
        'id',
        'sort_order',
        'date_added',
        'date_modified',
    ];

    public function custom_block()
    {
        return $this->belongsTo(CustomBlock::class, 'custom_block_id');
    }
}
