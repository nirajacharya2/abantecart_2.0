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

namespace abc\core\helper;

class AHelper
{
    private static $overload = [];

    /**
     * function extends libraries
     *
     * @param string $new_method_name
     * @param string | array $callback (function name or array($object, $method_name))
     */
    static function addMethod($new_method_name, $callback)
    {
        self::$overload[$new_method_name] = $callback;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    static function __callStatic($name, $arguments)
    {
        if (isset(self::$overload[$name])) {
            return call_user_func_array(self::$overload[$name], $arguments);
        }
    }
}