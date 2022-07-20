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
use abc\core\lib\AException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class Test
 *
 * @package abc\commands
 */
class Test extends BaseCommand
{
    public $results = [];

    protected $phpUnitBin;
    protected $phpUnitTestTemplate;
    protected $phpUnitTestsResult;

    public function __construct()
    {
        $this->phpUnitTestTemplate = ABC::env('DIR_APP').'commands'.DS.'base'.DS.'phpunit.test.template.txt';
        $this->phpUnitBin = ABC::env('DIR_VENDOR').'bin'.DS.'phpunit';
        parent::__construct();
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @return array
     */
    public function validate(string $action, array &$options)
    {
        $errors = [];
        if ($action == 'phpunit') {
            if (!isset($options['stage']) && !isset($options['help'])) {
                return ["Please provide stage name! For example: --stage=default"];
            }

            if (!is_file($this->phpUnitBin)) {
                return [
                    "PhpUnit not found! (looking for '".$this->phpUnitBin."')\n"
                    ."Please install composer packages in development and testing environments."
                ];
            }

            if (isset($options['create'])) {
                foreach($options as $optName=>$optValue){
                    if(!in_array($optName,['stage', 'create'])){
                        if(\H::isCamelCase($optName)){
                            $options['test_class'] = $optName;
                            break;
                        }
                    }
                }

                if (!isset($options['test_class'])) {
                    return [
                        "Please provide correct (CamelCase) test class name! For example:\n"
                        ."abcexec test:phpunit --create YourClassNameTest --stage=".$options['stage']
                    ];
                }elseif(substr($options['test_class'],-4) != 'Test') {
                    return [
                        "Please provide correct (CamelCase) test class name with \"Test\" suffix! For example:\n"
                        ."abcexec test:phpunit --create YourClassNameTest --stage=".$options['stage']
                    ];
                }

                //then check is file already exists

                $options['file'] = $options['test_class'].".php";

                //so choose directory

                //otherwise include all paths (core + extensions tests)
                $dirs = [ABC::env('DIR_TESTS').'unit'];
                $dirs = array_merge($dirs, glob(ABC::env('DIR_APP_EXTENSIONS').'*'.DS.'tests'.DS.'unit', GLOB_ONLYDIR));
                $message = "Which path would you like to use?:\n";
                $i = 0;
                $exists = [];
                foreach($dirs as $k=>$path){
                    $message .= '[ '.$i.' ] '.$path."\n";
                    $dirs[$i] = $path;
                    $i++;

                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            $path,
                            RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST,
                        RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
                    );

                    foreach($iterator as $file) {
                        $dirPath = $file->getRealpath();
                        if($file->isDir() && !is_int(strpos($dirPath,'config'))) {
                            $message .= '[ '.$i.' ] '.$dirPath."\n";
                            $dirs[$i] = $dirPath;
                            $i++;
                        }elseif($file->isFile()){
                            $exists[basename($dirPath)] = $dirPath;
                        }
                    }
                }

                if (isset($exists[$options['file']])) {
                    return ["File ".$options['test_class'].".php is already exists! See ".$exists[$options['file']]];
                }

                $message .= ">\n";
                $chosen = $this->getSTDIN($message,(count($dirs)+1));
                $options['file'] = $dirs[$chosen].DS.$options['file'];

                if (!is_dir(dirname($options['file']))) {
                    //try to create directory
                    $makeDirResult = \H::MakeNestedDirs(dirname($options['file']), 0775);
                    if ($makeDirResult['result'] === false) {
                        return [
                            "Cannot to create directory ".dirname($options['file'])."."
                            ."Please create it manually or check permissions.\n"
                            .$makeDirResult['message']
                        ];
                    }
                }
                if (is_file($options['file'])) {
                    return ["File ".$options['test_class'].".php is already exists!"];
                }
                $_SERVER['argv'][] = 'file='.$options['file'];
            }
        }
        return $errors;
    }


    protected function getSTDIN($message, $max){
        ob_end_clean();
        $resSTDIN = fopen("php://stdin","r");
        echo($message);
        $strChar = trim(fgets($resSTDIN));

        if(!preg_match('/^([0-9])+$/',$strChar) || $strChar<0 || $strChar>$max){
            echo "Please input correct number!\n";
            fclose($resSTDIN);
            return $this->getSTDIN($message,$max);
        }
        fclose($resSTDIN);
        return $strChar;
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
     * @param string $action
     * @param array $options
     */
    public function finish(string $action, array $options){
        parent::finish($action, $options);
        //do exit at the end to let shell know about test-batch results
       exit($this->phpUnitTestsResult);
    }

    /**
     * @param array $options
     *
     * @return array|true
     * @throws AException
     */
    public function help($options = [])
    {
        //show basic suggestion for usage via abcexec
        if (!$options) {
            return $this->getOptionList();
        }
        //show phpunit help
        $this->callPhpUnit('help', $options);
        return true;
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
                        ."php ./vendor/bin/phpunit \n\n"
                        ."\t  To run some testsuite:\n\n\t\t   "
                        ."php ./vendor/bin/phpunit --testsuite models-unit-tests-catalog\n\n"
                        ."\t  To create new phpunit test:\n\n\t\t   "
                        ."php abcexec test:phpunit --create YourClassNameTest --stage=default\n\n",
                ],
        ];
    }

    protected function callPhpUnit($action, $options)
    {
        if ($options['create']) {
            $testClassContent = file_get_contents($this->phpUnitTestTemplate);
            $testClassContent = str_replace('%s', $options['test_class'], $testClassContent);
            if (!@file_put_contents($options['file'], $testClassContent)) {
                throw new AException(
                    'Cannot create file '.$options['file']."\n",
                    AC_ERR_LOAD
                );
            }
        } /*else {

            $this->adaptPhpUnitArgv($action);
            //call phpunit with arguments


            $output_arr = [];
            $command = 'php '.$this->phpUnitBin.' --color -c '.ABC::env('DIR_TESTS').'unit'.DS.'phpunit.xml';
            $this->write("Run shell command:\n".$command);

            exec(
                $command,
                $output_arr,
                $this->phpUnitTestsResult
            );
            $this->write( implode("\n", $output_arr));
        }*/
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

}