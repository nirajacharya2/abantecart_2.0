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

namespace abc\core\engine;

use abc\core\ABC;
use abc\core\lib\AConfig;
use abc\core\lib\AException;
use abc\core\lib\AWarning;


/**
 * Class ALoader
 *
 * @property AConfig $config
 * @property ALanguage $language
 * @property ExtensionsAPI $extensions
 */
final class ALoader
{
    /**
     * @var Registry
     */
    public $registry;

    /**
     * @param $registry Registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    /**
     * @param string $library
     *
     * @throws AException
     */
    public function library($library)
    {
        //try to find in core libs
        $file = ABC::env('DIR_LIB').$library.'.php';
        if (!file_exists($file)) {
            $file = '';
        }

        //looking for library inside extensions.
        //Note: library must be defined in main.php file inside $libraries array!
        if (!$file) {
            $extensions = $this->extensions->getDbExtensions();
            $libs = $this->extensions->getExtensionLibraries();
            foreach ($extensions as $extension_text_id) {
                if (isset($libs[$extension_text_id]) && in_array($library, $libs[$extension_text_id])) {
                    $file = ABC::env('DIR_EXTENSIONS').$extension_text_id.'/lib/'.$library.'.php';
                    break;
                }
            }
        }

        if ($file) {
            include_once($file);
        } else {
            throw new AException(
                'Error: Could not load library '.$library.'!',
                AC_ERR_LOAD
            );
        }
    }

    /**
     * @param string $model - rt to model class
     * @param string $mode - can be 'storefront','force'
     *
     * @return bool | object
     * @throws AException
     */
    public function model($model, $mode = '')
    {

        //force mode allows to load models for ALL extensions to bypass extension enabled only status
        //This might be helpful in storefront. In admin all installed extensions are available
        $force = '';
        if ($mode == 'force') {
            $force = 'all';
        }

        //mode to force load storefront model
        if (ABC::env('INSTALL') && $model == 'install') {
            $section = ABC::env('DIR_INSTALL').'models/';
            $namespace = "\\install\\models";
        } elseif ($mode == 'storefront' || ABC::env('IS_ADMIN') !== true) {
            $section = ABC::env('DIR_APP').'models/storefront/';
            $namespace = "\\abc\\models\\storefront";
        } else {
            $section = ABC::env('DIR_APP').'models/admin/';
            $namespace = "\\abc\\models\\admin";
        }

        $file = $section.$model.'.php';
        if ($this->registry->has('extensions')
            && $result = $this->extensions->isExtensionResource('M', $model, $force, $mode)
        ) {

            if (is_file($file)) {
                $warning = new AWarning("Extension <b>{$result['extension']}</b> override model <b>$model</b>");
                $warning->toDebug();
            }
            $file = $result['file'];
            $extNamespacePath = str_replace(DS, '\\', dirname($result['base_path']));
            $namespace = "\abc\\extensions\\".$result['extension'].$extNamespacePath;
        }

        $class = $namespace.'\Model'.preg_replace('/[^a-zA-Z0-9]/', '',
                ucfirst(preg_replace('/[^a-zA-Z0-9]/', ' ', $model)));
        $obj_name = 'model_'.str_replace('/', '_', $model);

        //if model is loaded return it back
        if (is_object($this->registry->get($obj_name))) {
            return $this->registry->get($obj_name);
        } else {
            if (file_exists($file)) {
                include_once($file);
                if (!class_exists($class)) {
                    if ($mode != 'silent') {
                        throw new AException(
                            'Error: Class '.$class.' not found in file '.$file,
                            AC_ERR_LOAD
                        );
                    } else {
                        return false;
                    }
                }
                $this->registry->set($obj_name, new $class($this->registry));

                return $this->registry->get($obj_name);
            } else {
                if ($mode != 'silent') {
                    $backtrace = debug_backtrace();
                    $file_info = $backtrace[0]['file'].' on line '.$backtrace[0]['line'];
                    throw new AException(
                        'Error: Could not load model '
                        .$model.' (file '.$file.', namespace '.$namespace.')  from '.$file_info,
                        AC_ERR_LOAD
                    );
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * @param string $helper
     *
     * @throws AException
     */
    public function helper($helper)
    {
        $file = ABC::env('DIR_CORE').'helper/'.$helper.'.php';

        if (file_exists($file)) {
            include_once($file);
        } else {
            throw new AException(
                'Error: Could not load helper '.$helper.'!',
                AC_ERR_LOAD
            );
        }
    }

    /**
     * @param string $config
     *
     * @throws AException
     */
    public function config($config)
    {
        $this->config->load($config);
    }

    /**
     * @param string $language
     * @param string $mode
     *
     * @return array|null
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function language($language, $mode = '')
    {
        return $this->language->load($language, $mode);
    }
}
