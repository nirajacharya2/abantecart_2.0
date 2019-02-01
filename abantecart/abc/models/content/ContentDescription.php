<?php

namespace abc\models\content;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ContentDescription
 *
 * @property int $content_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Content $content
 * @property Language $language
 *
 * @package abc\models
 */
class ContentDescription extends BaseModel
{
    use SoftDeletes;
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'content_id'  => 'int',
        'language_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'name',
        'title',
        'description',
        'content',
        'date_added',
        'date_modified',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
