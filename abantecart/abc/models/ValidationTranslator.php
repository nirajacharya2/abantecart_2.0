<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\models;

use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use Countable;
use Illuminate\Contracts\Translation\Translator;
use ReflectionException;

class ValidationTranslator implements Translator
{
    /**
     * @var ALanguage
     */
    protected $language;

    public function __construct(string $language_rt = '')
    {
        $this->language = Registry::getInstance()->get('language');
        if ($language_rt) {
            $this->language->load($language_rt);
        }
    }

    /**
     * Get the translation for a given key.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     *
     * @return mixed
     */
    public function get($key, array $replace = [], $locale = null)
    {
        return $this->trans($key, $replace, $locale);
    }

    /**
     * Get a translation according to an integer value.
     *
     * @param string $key
     * @param \Countable|int|array $number
     * @param array $replace
     * @param string|null $locale
     *
     * @return string
     */
    public function choice($key, $number, array $replace = [], $locale = null)
    {
    }

    /**
     * Get the translation for a given key.
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     *
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    public function trans($key, array $replace = [], $locale = null)
    {
        $parts = explode('.',$key);
        $field_name = $parts[2] ?? null;
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
     * @param  int|array|Countable $number
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