<?php

namespace abc\models\catalog;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\locale\Language;
use H;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

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

    /**
     * @param string $name
     * @param int    $id
     *
     * @return array
     */
    public static function getKeyWordsArray(string $name, $id)
    {
        $result = self::select(['language_id', 'keyword'])
                       ->where('query', '=', $name ."=".(int)$id)
                       ->get();
        return $result->toArray();
    }

    /**
     * @param string $query
     * @param int $language_id
     *
     * @return string
     */
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

    public static function getProductKeyword(int $productId, int $languageId)
    {
        return self::getKeyWord('product_id='.$productId, $languageId);
    }

    public static function getCategoryKeyword(int $categoryId, $languageId)
    {
        return self::getKeyWord('category_id='.$categoryId, $languageId);
    }

    public static function getManufacturerKeyword(int $manufacturerId, int $languageId)
    {
        return self::getKeyWord('manufacturer_id='.$manufacturerId, $languageId);
    }

    private static function setKeyword(string $keyword, string $objectKeyName, int $objectId)
    {
        $keyword = H::SEOEncode($keyword, $objectKeyName, $objectId);

        if ($keyword) {
            Registry::language()->replaceDescriptions(
                'url_aliases',
                ['query' => $objectKeyName."=".(int)$objectId],
                [static::$current_language_id => ['keyword' => $keyword]]);
        } else {
            self::where('query', '=', $objectKeyName."=".(int)$objectId)
                ->where('language_id', '=', static::$current_language_id)
                ->delete();
        }
    }

    public static function setProductKeyword(string $keyword, int $productId)
    {
        self::setKeyword($keyword, 'product_id', $productId);
        $product = Product::find($productId);
        if ($product) {
            $product->touch();
        }
    }

    public static function setCategoryKeyword(string $keyword, int $categoryId)
    {
        self::setKeyword($keyword, 'category_id', $categoryId);
        $category = Category::find($categoryId);
        if ($category) {
            $category->touch();
        }
    }

    public static function setManufacturerKeyword(string $keyword, int $manufacturerId)
    {
        self::setKeyword($keyword, 'manufacturer_id', $manufacturerId);
        $manufacturer = Manufacturer::find($manufacturerId);
        if ($manufacturer) {
            $manufacturer->touch();
        }
    }

    /**
     * @param array $data - ['language_id' => 1, 'keyword' => 'somekeyword']
     * @param string $name - 'product_id', 'category_id' etc
     * @param int $id
     *
     * @throws \Exception
     */
    public static function replaceKeywords( $data, $name, $id)
    {
        $data = (array)$data;
        $id = (int)$id;
        $name = (string)$name;

        $query = $name.'='.$id;
        $urlAlias = new UrlAlias();
        $urlAlias->where('query', '=', $query)->forceDelete();
        unset($urlAlias);

        foreach ((array)$data as $keyword) {
            $urlAlias = new UrlAlias();
            $urlAlias->query = $query;
            $urlAlias->language_id = (int)$keyword['language_id'];
            $urlAlias->keyword = \H::SEOEncode($keyword['keyword'], $name, $id);
            $urlAlias->save();
        }
    }
}
