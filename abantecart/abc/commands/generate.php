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
use abc\models\BaseModel;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Krlove\EloquentModelGenerator\Config;
use Krlove\EloquentModelGenerator\EloquentModelBuilder;
use Krlove\EloquentModelGenerator\Helper\EmgHelper;
use Krlove\EloquentModelGenerator\Processor\TableNameProcessor;


class Generate extends BaseCommand
{
    /**
     * @var Publish
     */
    protected $publish;
    public $results = [];

    public function validate(string $action, array &$options)
    {
        $errors = [];
        if (
            $action == 'model' && !class_exists('\Krlove\EloquentModelGenerator\EloquentModelBuilder')
        ) {
            return [
                'Error: vendor package "krlove/eloquent-model-generator" required! '
                . 'Please run "composer update" command',
            ];
        }

        if ($action == 'model' && !isset($options['stage']) && !isset($options['help'])) {
            return ["Please provide stage name! For example: --stage=default"];
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
        $action = $action ?: 'help';
        if ($action == 'model') {
            $this->createModel($action, $options);
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
        return $this->createModel('help', $options);
    }

    protected function getOptionList()
    {
        return [
            'model' =>
                [
                    'description' => 'Run eloquent mode creation command.',
                    'arguments'   => [
                        '--stage' => [
                            'description'   => 'stage name',
                            'default_value' => 'default',
                            'required'      => true,
                        ],
                    ],
                    'example'     => "php abcexec generate:help\n" .
                        "\t  To create new model:\n\n\t\t   "
                        . "php abcexec generate:model YourModelClassName --stage=default\n\n"
                ],
        ];
    }

    protected function createModel($action, $options)
    {
        $stage_name = $options['stage'] ?: 'default';
        $this->adaptArgv($action);
        $config = new Config(
            [
                'namespace'       => 'abc\extensions\ufs_rio\models',
                'base_class_name' => BaseModel::class,
                'output_path'     => '/var/www/clients/rio/abc/extensions/ufs_rio/models',
            ]
        );
        $emgHelper = new EmgHelper();
        $processor = new TableNameProcessor($emgHelper);
        $builder = new EloquentModelBuilder([$processor]);
        $model = $builder->createModel($config);
        $model->addProperty('id');
        $model->render();
        $str = $model->toLines();
        var_Dump($str);

    }

    protected function adaptArgv($action)
    {
        //do the trick for help output
        $_SERVER['PHP_SELF'] = 'abcexec generate:model';

        $argv = $_SERVER['argv'];
        //remove abcexec
        array_shift($argv);
        array_shift($argv);

        switch ($action) {
            case 'help':
            case 'init':
                //$argv[] = '-h';
                break;
            default:
                if ($action != 'phinx') {
                    $argv[] = $action;
                }
        }

        foreach ($argv as $k => $v) {
            if (is_int(strpos($v, '--stage='))) {
                unset($argv[$k]);
                break;
            }
        }

        //array_unshift($argv, 'model');

        //add configuration file
        $_SERVER['argv'] = $argv;

        return true;
    }

}