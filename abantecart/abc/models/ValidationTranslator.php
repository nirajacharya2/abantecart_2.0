<?php
/**
 * Created by PhpStorm.
 * User: desss
 * Date: 18.06.18
 * Time: 19:19
 */

namespace abc\models;

use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use Illuminate\Contracts\Translation\Translator;

class ValidationTranslator implements Translator
{
    /**
     * @var ALanguage
     */
    protected $language;
    public function __construct(string $language_rt = '')
    {
        $this->language = Registry::getInstance()->get('language');
        if($language_rt){
            $this->language->load($language_rt);
        }
    }

    /**
     * Get the translation for a given key.
     *
     * @param  string $key
     * @param  array $replace
     * @param  string $locale
     *
     * @return mixed
     */
    public function trans($key, array $replace = [], $locale = null)
    {
        $parts = explode('.',$key);
        $field_name = isset($parts[2]) ? $parts[2] : null;
        if($field_name) {
            return $this->language->get('error_'.$field_name);
        }else{
            return $key;
        }

    }

    /**
     * Get a translation according to an integer value.
     *
     * @param  string $key
     * @param  int|array|\Countable $number
     * @param  array $replace
     * @param  string $locale
     *
     * @return string
     */
    public function transChoice($key, $number, array $replace = [], $locale = null)
    {
        return $key;
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale()
    {

    }

    /**
     * Set the default locale.
     *
     * @param  string $locale
     *
     * @return void
     */
    public function setLocale($locale)
    {
    }

}