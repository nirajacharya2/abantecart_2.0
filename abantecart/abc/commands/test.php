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

use abc\core\ABC;
use abc\core\lib\AException;

include_once('base/BaseCommand.php');

class Test extends BaseCommand
{
    public $results = [];

    protected $phpUnitPhar;
    protected $phpUnitConfigFile;
    protected $phpUnitTestTemplate;

    public function __construct()
    {
        $this->phpUnitTestTemplate = ABC::env('DIR_APP').'commands'.DS.'base'.DS.'phpunit.test.template.txt';
        $this->phpUnitPhar = dirname(__DIR__).DS.'system'.DS.'temp'.DS.'phpunit-7.2.5.phar';
    }

    public function validate(string $action, array $options)
    {
        $errors = [];
        if ($action == 'phpunit'
            && !isset($options['stage'])
            && !isset($options['help'])
        ) {
            return ["Please provide stage name! For example: --stage=default"];
        }
        if ($action == 'phpunit'
            && isset($options['create'])
        ) {
            if (!isset($options['file'])) {
                return ["Please provide file name! For example: --file=path/to/your/file"];
            }
            if (!is_dir(dirname($options['file']))) {
                return ["Please create directory ".dirname($options['file'])." for file first."];
            }
        }
        return $errors;
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @throws AException
     */
    public function run(string $action, array $options)
    {
        parent::run($action, $options);
        $action = !$action ? 'help' : $action;
        if ($action == 'phpunit') {
            $this->callPhpUnit($action, $options);
        } else {
            $this->help();
        }
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws AException
     */
    public function help($options = [])
    {
        //show basic suggestion for usage via abcexec
        if (!$options) {
            return $this->getOptionList();
        }
        //show phpunit help
        return $this->callPhpUnit('help', $options);
    }

    protected function getOptionList()
    {
        return [
            'phpunit' =>
                [
                    'description' => 'Run phpunit test(s)',
                    'arguments'   => [
                        '--stage' => [
                            'description'   => 'stage name',
                            'default_value' => 'default',
                            'required'      => true,
                        ],
                    ],
                    'example'     => "php abcexec test:phpunit --help\n".
                        "\t  To run all tests:\n\n\t\t   "
                        ."php abcexec test:phpunit --run --all --stage=default\n\n"
                        ."\t  To run test by filename:\n\n\t\t   "
                        ."php abcexec test:phpunit --run --file=/full/Path/Of/YourTestFileName.php --stage=default\n\n"
                        ."\t  To run all tests from directory:\n\n\t\t   "
                        ."php abcexec test:phpunit --run --dir=/full/Path/Of/Your/Directory/With/Tests/ --stage=default\n\n"
                        ."\t  To reate new phpunit test file:\n\n\t\t   "
                        ."php abcexec test:phpunit --create --file=/full/Path/Of/YourTestFileName.php --stage=default\n\n",
                ],
        ];
    }

    protected function callPhpUnit($action, $options)
    {
        $stage_name = $options['stage'] ? $options['stage'] : 'default';
        $this->phpUnitConfigFile = ABC::env('DIR_CONFIG').'phpunit_'.$stage_name.'.xml';
        $result = $this->createPhpUnitConfigurationFile($options);
        if (!$result) {
            throw new AException(AC_ERR_LOAD, implode("\n", $this->results)."\n");
        }

        if ($options['create']) {
            if (!@copy($this->phpUnitTestTemplate, $options['file'])) {
                throw new AException(
                    AC_ERR_LOAD,
                    'Cannot copy '.$this->phpUnitTestTemplate.' to '.$options['file']."\n"
                );
            }
        } else {

            $this->adaptPhpUnitArgv($action);
            //call phpunit with arguments
            if (!is_file($this->phpUnitPhar)) {
                echo "phpunit phar-package not found.\n"
                    ."Trying to download phpunit package into abc/system/temp directory. Please wait..\n";
            }
            if (!copy(
                'https://phar.phpunit.de/'.basename($this->phpUnitPhar),
                $this->phpUnitPhar
            )) {
                exit("Error: Tried to download phpunit phar-file"
                    ." from ".'https://phar.phpunit.de/'.basename($this->phpUnitPhar)." but failed.\n".
                    " Please download it manually into "
                    .dirname($this->phpUnitPhar).DS." directory\n");
            }

            $output_arr = [];
            exec('php '.$this->phpUnitPhar.' --configuration '.$this->phpUnitConfigFile, $output_arr, $result);
            echo implode("\n", $output_arr)."\n";
        }
    }

    protected function adaptPhpUnitArgv($action)
    {
        //do the trick for help output
        $_SERVER['PHP_SELF'] = 'abcexec test:phpunit';

        $argv = $_SERVER['argv'];
        //remove abcexec
        array_shift($argv);
        array_shift($argv);

        switch ($action) {
            case 'help':
                //$argv[] = '-h';
                break;
            default:
                if ($action != 'phpunit') {
                    $argv[] = $action;
                }
        }

        //add migration template parameter for new migrations
        if (in_array('create', $argv)) {
            $template = $this->phpUnitTestTemplate;
            if (!is_file($template) || !is_readable($template)) {
                $this->results[] = 'Cannot to find phpunit template file '.$template.'!';
                return false;
            }
        }

        foreach ($argv as $k => $v) {
            if (is_int(strpos($v, '--stage='))) {
                unset($argv[$k]);
                break;
            }
        }

        //add configuration file
        $_SERVER['argv'] = $argv;

        return true;
    }

    public function createPhpUnitConfigurationFile(array $data)
    {
        $stage_name = $data['stage'];
        $phpunit_config_file = $this->phpUnitConfigFile;

        $app_config_file = ABC::env('DIR_CONFIG').$stage_name.'.config.php';
        @unlink($phpunit_config_file);
        if (!$stage_name || !is_file($app_config_file)) {
            $this->results[] = 'Cannot to create phpunit configuration. Unknown stage name!';
            return false;
        }
        $app_config = @include $app_config_file;
        if (!$app_config) {
            $this->results[] = 'Cannot to create phpunit configuration. Empty stage environment!';
            return false;
        }

        if ($data['dir']) {
            $dirs = [$data['dir']];
        } elseif ($data['file']) {
            $dirs = [$data['file']];
        } else {
            //otherwise include all paths (core + extensions tests)
            $dirs = [ABC::env('DIR_TESTS').'phpunit'.DS.'abc'];
            $dirs = array_merge($dirs, glob(ABC::env('DIR_APP_EXTENSIONS').'*'.DS.'tests'.DS.'phpunit', GLOB_ONLYDIR));
        }
        $phpunit_bootstrap = ABC::env('DIR_TESTS').'phpunit/AbanteCartTestBootstrap.php';
        $content = <<<EOD
<phpunit
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:noNamespaceSchemaLocation="http://schema.phpunit.de/4.5/phpunit.xsd"
		bootstrap="{$phpunit_bootstrap}">
	<php>
	  <ini name="display_startup_errors" value="On"/>
	  <ini name="display_errors" value="On"/>
	</php>
	<testsuites>
		<testsuite name="abcexec_tests">
EOD;

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $content .= "\n			<directory>".$dir."</directory>\n";
            } elseif (is_file($dir)) {
                $content .= "\n			<file>".$dir."</file>\n";
            }
        }
        $content .= <<<EOD
		</testsuite>
	</testsuites>
</phpunit>
EOD;

        $file = fopen($phpunit_config_file, 'w');
        if (!fwrite($file, $content)) {
            $this->results[] = 'Cannot to write file '.$file;
            return false;
        }
        fclose($file);
        return true;
    }

}