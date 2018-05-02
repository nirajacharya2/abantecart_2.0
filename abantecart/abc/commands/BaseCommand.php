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

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * BaseCommand abstract class for the console commands for AbanteCart.
 * Implements common methods.
 *
 */
class BaseCommand extends CConsoleCommand
{
    use \application\components\helpers\ContainerHelper;

    /**
     * @var bool Whether to migrate online. Enabled by default.
     * Set to false when you migrate to the crown or the background process.
     *
     */
    public $interactive = true;

    /**
     * show start time before execution of a command
     *
     * @var bool
     */
    public $printStartTime = true;

    /**
     * show end time after execution of a command
     *
     * @var bool
     */
    public $printEndTime = true;

    /**
     * @var boolean Property that specifies to print or not. By default, no output.
     */
    public $verbose = 0;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutputInterface
     */
    protected $output;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Устанавливаем таймзону для всех консольных комманд
        // чтобы правильно записывались даты в БД.
        setUserTimeZone(app()->getTimeZone());

        $this->output = new ConsoleOutput();

        parent::init();
    }

    protected function beforeAction($action, $params)
    {
        if ($this->printStartTime) {
            $output = new ConsoleOutput();
            $output->writeln(gmdate('Y-m-d H:i:s') . ' (UTC) ');
            $output->writeln('Action: ' . $action);
            if (!empty($params)) {
                $output->writeln('Params: ' . var_export($params, true));
            }
            $output->writeln('---------------');
        }

        return parent::beforeAction($action, $params);
    }

    /**
     * @inheritdoc
     */
    protected function afterAction($action, $params, $exitCode = 0)
    {
        if ($this->printEndTime){
            $output = new ConsoleOutput();
            $output->writeln('End Time: ' . gmdate('Y-m-d H:i:s') . ' (UTC) ');
            $output->writeln('---------------');
        }

        return parent::afterAction($action, $params, $exitCode);
    }

    /**
     * Collects line with all global options.
     *
     * @see CConsoleCommand::getHelp()
     * @see getGlobalOptions
     * @return string
     */
    public function getHelp()
    {
        $help = parent::getHelp();
        $global_options = $this->getGlobalOptions();
        if (!empty($global_options)) {
            // Recursive options printing
            $printOption = function ($name, $value, $padding) use (&$help, &$printOption) {
                if (is_array($value)) {
                    $help .= PHP_EOL . $padding . $name . ':';
                    foreach ($value as $n => $v) {
                        $printOption($n, $v, "  " . $padding);
                    }
                } else {
                    $help .= PHP_EOL . $padding . '[' . $name . '=' . $value . ']';
                }
            };

            $printOption('Global options', $global_options, '');
        }

        return $help;
    }

    /**
     * Get the list of global options (console commands)
     *
     * @return array
     */
    protected function getGlobalOptions()
    {
        $options = [];
        $refl = new ReflectionClass($this);
        $properties = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            if ($property->getName() != 'defaultAction') {
                $options[$property->getName()] = $property->getValue($this);
            }
        }

        return $options;
    }

    /**
     * Action showing a hint of all console commands that are there.
     *
     * @see printf
     * @return void
     */
    public function actionHelp()
    {
        $this->printf("Info: " . $this->getHelp());
    }

    /**
     * Gets any number of arguments and prints them function {@link printf()}
     * Sharing with each new line.
     *
     * @see printf()
     * @return void
     */
    protected function printf()
    {
        $args = func_get_args(); // PHP 5.2 workaround
        call_user_func_array('printf', $args);
        printf(PHP_EOL);
    }

    /**
     * Print Text only when {@link $verbose} true
     *
     * @see printf
     * @return void
     */
    protected function verbose()
    {
        if ($this->verbose) {
            $args = func_get_args(); // PHP 5.2 workaround
            call_user_func_array([$this, 'printf'], $args);
        }
    }

    /**
     * Method for Confirming the message in the output terminal, or if it passes {@link $interactive} will true.
     *
     * @param string $message Display a message for Confirm the terminal. Waiting for data entry.
     * @param bool $default This value is returned if no selection is made.
     *
     * @return bool If {@link $interactive} true, then performs the parent method, otherwise it returns true.
     */
    public function confirm($message, $default = false)
    {
        return $this->interactive ? parent::confirm($message, $default) : true;
    }
}
