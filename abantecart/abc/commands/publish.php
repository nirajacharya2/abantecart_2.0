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

namespace abc\commands;

use abc\commands\base\BaseCommand;
use abc\core\ABC;
use abc\core\lib\AssetPublisher;

/**
 * Class Publish
 *
 * @package abc\commands
 */
class Publish extends BaseCommand
{
    public $errors = [];

    public function validate(string $action, array &$options)
    {
        $action = !$action ? 'all' : $action;
        //if now options - check action
        if (!$options) {
            if (!in_array($action, ['all', 'help', 'core', 'extensions', 'vendors'])) {
                return ['Error: Unknown Action Parameter!'];
            }
        } elseif ($options && $action == 'extensions') {
            if (isset($options['extension'])) {
                $extension_name = trim($options['extension']);
                $dir_name = ABC::env('DIR_APP_EXTENSIONS').$extension_name;
                if (!is_dir($dir_name)) {
                    return ['Error: directory of extension "'.$extension_name.'" not found!'];
                }
            }
        } elseif ($options && $action == 'vendors') {
            if (isset($options['package'])) {
                list($vendor_name, $package_name) = explode(':', $options['package']);
                $vendor_name = trim($vendor_name);
                $package_name = trim($package_name);
                $dir_name = ABC::env('DIR_VENDOR').'assets'.DS.$vendor_name.DS.$package_name;
                if (!$vendor_name || !$package_name) {
                    return ['Error: Incorrect vendor package name "'.$options['package'].'"!'];
                } elseif (!is_dir($dir_name)) {
                    return ['Error: directory of vendor package "'.$dir_name.'" not found!'];
                }
            }
        }
        return [];
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @return array|true
     * @throws \abc\core\lib\AException
     */
    public function run(string $action, array $options)
    {
        parent::run($action, $options);
        $action = !$action ? 'all' : $action;
        $result = false;
        if (in_array($action, ['all', 'core', 'extensions', 'vendors'])) {
            $ap = new AssetPublisher();
            $result = $ap->publish($action, $options);
            $this->errors = $ap->errors;
        } else {
            $this->errors = ['Error: unknown public action!'];
        }

        return $result ? true : $this->errors;
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @return bool|void
     */
    public function finish(string $action, array $options)
    {
        $this->write('Success: Assets have been published.');
        parent::finish($action, $options);
    }

    /**
     * @return array
     */
    protected function getOptionList()
    {
        return [
            'all'        =>
                [
                    'description' => 'publish all asset files',
                    'arguments'   => [],
                    'example'     => 'php abcexec publish:all',
                ],
            'core'       =>
                [
                    'description' => 'publish only default template asset files',
                    'arguments'   => [],
                    'example'     => 'php abcexec publish:core',
                ],
            'extensions' =>
                [
                    'description' => 'publish only extensions asset files',
                    'arguments'   => [
                        '--extension' => [
                            'description'   => 'Publish only assets of extension with given text ID',
                            'default_value' => 'your_extension_txt_id',
                            'required'      => false,
                        ],
                    ],
                    'example'     => 'php abcexec publish:extensions --extension=your_extension_txt_id',
                ],
            'vendors'    =>
                [
                    'description' => 'publish only vendors asset files',
                    'arguments'   => [
                        '--package' => [
                            'description'   => 'Publish only assets of vendor package with given package name',
                            'default_value' => 'vendor_name:package_name',
                            'required'      => false,
                        ],
                    ],
                    'example'     => 'php abcexec publish:vendors --vendor=vendor_name:package_name',
                ],
        ];
    }

}