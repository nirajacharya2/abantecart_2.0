<?php

namespace abc\models\catalog;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\locale\Language;
use H;

/**
 * Class UrlAlias
 *
 * @property int      $url_alias_id
 * @property string   $query
 * @property string   $keyword
 * @property int      $language_id
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

    private static function getKeyWord(string $query, int $language_id)
    {
        $keyword = self::select('keyword')
            ->where('query', '=', $query)
            ->where('language_id', '=', $language_id)
            ->first();
        if ($keyword && isset($keyword->toArray()['keyword'])) {
            return $keyword->toArray()['keyword'];
        }
        return '';
    }

    public static function getProductKeyword(int $productId, int $language_id)
    {
        return self::getKeyWord('product_id='.$productId, $language_id);
    }

    public static function getCategoryKeyword(int $categoryId, int $language_id)
    {
        return self::getKeyWord('category_id='.$categoryId, $language_id);
    }

    private static function setKeyword(string $keyword, string $objectKeyName, int $objectId)
    {
        $keyword = H::SEOEncode($keyword, $objectKeyName, $objectId);
        $registry = Registry::getInstance();
        if ($keyword) {
            $registry->get('language')->replaceDescriptions('url_aliases',
                ['query' => $objectKeyName."=".(int)$objectId],
                [$registry->get('language')->getContentLanguageID() => ['keyword' => $keyword]]);
        } else {
            self::where('query', '=', $objectKeyName."=".(int)$objectId)
                ->where('language_id', '=', $registry->get('language')->getContentLanguageID())
                ->delete();
        }
    }

    public static function setProductKeyword(string $keyword, int $productId)
    {
        self::setKeyword($keyword, 'product_id', $productId);
    }
}
