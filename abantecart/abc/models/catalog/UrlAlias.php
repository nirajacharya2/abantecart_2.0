<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;

/**
 * Class UrlAlias
 *
 * @property int $url_alias_id
 * @property string $query
 * @property string $keyword
 * @property int $language_id
 *
 * @property Language $language
 *
 * @package abc\models
 */
class UrlAlias extends BaseModel
{
    protected $primaryKey = 'url_alias_id';
    public $timestamps = false;

    protected $casts = [
        'language_id' => 'int',
    ];

    protected $fillable = [
        'query',
        'keyword',
        'language_id',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public static function getProductKeyword(int $productId, int $language_id)
    {
        $productKeyword = self::select('keyword')
        ->where('query', '=', 'product_id='.$productId)
        ->where('language_id', '=', $language_id)
        ->first();
        if ($productKeyword) {
            return $productKeyword->toArray()['keyword'];
        } else {
            return '';
        }
    }
}
