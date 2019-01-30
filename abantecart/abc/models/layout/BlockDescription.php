<?php

namespace abc\models\layout;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BlockDescription
 *
 * @property int $block_description_id
 * @property int $custom_block_id
 * @property int $language_id
 * @property string $block_wrapper
 * @property bool $block_framed
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $content
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property CustomBlock $custom_block
 * @property Language $language
 *
 * @package abc\models
 */
class BlockDescription extends BaseModel
{
    use SoftDeletes;
    public $timestamps = false;

    protected $casts = [
        'custom_block_id' => 'int',
        'language_id'     => 'int',
        'block_framed'    => 'bool',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'block_wrapper',
        'block_framed',
        'name',
        'title',
        'description',
        'content',
        'date_added',
        'date_modified',
    ];

    public function custom_block()
    {
        return $this->belongsTo(CustomBlock::class, 'custom_block_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
