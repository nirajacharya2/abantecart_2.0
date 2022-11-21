<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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

namespace abc\commands\base;

/**
 * BaseCommand abstract class for the console commands for AbanteCart.
 * Implements common methods.
 *
 */
class BaseCommand implements ABCExecInterface
{
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
     * @var array
     */
    protected $output = [];

    /**
     * @var string
     */
    public static $EOF = "\n";

    /**
     * @var string
     */
    public static $outputType = "cli";

    /**
     * @var int system process ID
     */
    protected static $pid= -1;

    /**
     * BaseCommand constructor.
     */
    public function __construct()
    {
        $this->output = [];
        self::$pid = getmypid();
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return self::$pid;
    }

    /**
     * @param string $action
     * @param array  $options
     */
    public function run(string $action, array $options)
    {
        if ($this->printStartTime) {
            $this->write('Start Time: ' .date('m/d/Y h:i:s a', time()));
            $this->write('Action: ' . $action);
            if (!empty($options)) {
                $this->write('Params: ' . var_export($options, true));
            }
            $this->write('******************');
        }
    }

    /**
     * @param string $action
     * @param array $options
     *
     * @return bool
     */
    public function finish(string $action, array $options)
    {
        if ($this->printEndTime) {
            $this->write('End Time: ' . date('m/d/Y h:i:s a', time()));
            $this->write('******************');
            return true;
        }
        return false;
    }

    /**
     * @param string $action
     * @param array $options
     */
    public function validate(string $action, array &$options)
    {
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function help($options = [])
    {
        return $this->getOptionList();
    }

    /**
     * @return array
     */
    protected function getOptionList()
    {
        return [];
    }

    /**
     * @param mixed $output
     */
    protected function write($output)
    {
        if ($this::$outputType == 'cli') {
            echo $output.$this::$EOF;
        } else {
            if (is_array($output)) {
                $this->output = array_merge($this->output, $output);
            } else {
                $this->output[] = $output;
            }
        }
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return implode($this::$EOF, $this->output);
    }
}
