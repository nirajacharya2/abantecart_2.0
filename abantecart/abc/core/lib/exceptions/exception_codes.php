<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2021 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

if (!defined('E_DEPRECATED')) define('E_DEPRECATED', 8192);
if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

// Error codes
define('AC_HOOK_OVERRIDE', 9999);

define('AC_ERR_CLASS_CLASS_NOT_EXIST', 9000); // class class not exist
define('AC_ERR_CLASS_METHOD_NOT_EXIST', 9001); // class method not exist
define('AC_ERR_CLASS_PROPERTY_NOT_EXIST', 9002); // class method not exist

define('AC_ERR_USER_ERROR', 9100); // Error generated by the software
define('AC_ERR_USER_WARNING', 9101); // Warning generated by the software

// if code >= 10000 - application stop
define('AC_ERR_MYSQL', 10000); //mysql query error
define('AC_ERR_REQUIREMENTS', 10001); // php requirements error
define('AC_ERR_LOAD', 10002); // file load error
define('AC_ERR_CONNECT_METHOD', 10003); // connect methods not available
define('AC_ERR_CONNECT', 10004); // connect to upgrade server error
define('AC_ERR_LOAD_LAYOUT', 10005); // layout load error

// TODO: multilingual descriptions
$error_descriptions = [
    E_ERROR                         => 'error',
    E_WARNING                       => 'warning',
    E_PARSE                         => 'parsing error',
    E_NOTICE                        => 'notice',
    E_CORE_ERROR                    => 'core error',
    E_CORE_WARNING                  => 'core warning',
    E_COMPILE_ERROR                 => 'compile error',
    E_COMPILE_WARNING               => 'compile warning',
    E_USER_ERROR                    => 'user error',
    E_USER_WARNING                  => 'user warning',
    E_USER_NOTICE                   => 'user notice',
    E_STRICT                        => 'runtime notice',
    E_RECOVERABLE_ERROR             => 'recoverable error',
    E_DEPRECATED                    => 'DEPRECATED',

    AC_ERR_CLASS_CLASS_NOT_EXIST    => 'class not exist',
    AC_ERR_CLASS_METHOD_NOT_EXIST   => 'method not exist',
    AC_ERR_CLASS_PROPERTY_NOT_EXIST => 'property not exist',

    AC_ERR_MYSQL        => 'database error',
    AC_ERR_LOAD         => 'load error',
    AC_ERR_REQUIREMENTS => 'requirements error',

    AC_ERR_USER_ERROR   => 'App Error',
    AC_ERR_USER_WARNING => 'App Warning',
];