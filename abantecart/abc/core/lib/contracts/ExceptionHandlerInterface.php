<?php

namespace abc\core\lib\contracts;

use abc\core\lib\AException;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

interface ExceptionHandlerInterface
{
    /**
     * Create a new exception handler instance.
     *
     * @param  bool  $debug
     * @return void
     */
    public function __construct($debug = false);

    /**
     * Report or log an exception.
     *
     * @param  Exception  $e
     * @return null
     *
     * @throws Exception
     */
    public function report(Exception $e);

    /**
     * Determine if the exception should be reported.
     *
     * @param  Exception  $e
     * @return bool
     */
    public function shouldReport(Exception $e);


    /**
     * Render an exception into a response.
     *
     * @param  Exception $e
     * @param  string     $to - can be http, cli, debug
     *
     * @throws AException
     */
    public function render( Exception $e, $to = 'http');


    /**
     * Render an exception to the console.
     *
     * @param  OutputInterface  $output
     * @param  Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e);

    /**
     * @param Exception $exception
     *
     * @return string
     */
    public function getExceptionTraceAsString(Exception $exception);

}