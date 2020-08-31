<?php

namespace abc\core\lib\exceptions;

use abc\core\ABC;
use abc\core\engine\ARouter;
use abc\core\engine\Registry;
use abc\core\lib\ALog;
use abc\core\lib\contracts\ExceptionHandlerInterface;
use ErrorException;
use Exception;
use Symfony\Component\Console\Output\ConsoleOutput;
use Whoops\Handler\PrettyPageHandler;
use Symfony\Component\Console\Application as ConsoleApplication;
use Whoops\Run;

class AExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * A list of the internal exception types that should not be reported.
     *
     * @var array
     */
    protected $internalDontReport = [];

    protected $debug;

    /**
     * Create a new exception handler instance.
     *
     * @param  bool  $debug
     * @return void
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @return null
     *
     * @throws \Exception
     */
    public function report(Exception $e)
    {
        if ($this->shouldNotReport($e)) {
            return null;
        }

        if (method_exists($e, 'report')) {
            return $e->report();
        }

        if($e instanceof ErrorException){
            $logger_message_type = 'error';
        }else{
            $logger_message_type = 'critical';
        }

        if($logger_message_type == 'error'
            && class_exists('\abc\core\engine\Registry')
            && Registry::getInstance()->get('log')
        ) {
            Registry::getInstance()->get('log')->{$logger_message_type}(
                $e->getMessage().' in '.$e->getFile().':'.$e->getLine()."\n".$this->getExceptionTraceAsString($e)
            );
        }else {
            /**
             * @var ALog $log
             */
            try {
                $log = ABC::getObjectByAlias('ALog', [[
                            'app'      => 'application.log'
                        ]]);
                if($log) {
                    $log->{$logger_message_type}($e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                }
            } catch (Exception $ex) {
                throw $e; // throw the original exception
            }
        }
        return null;
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Exception  $e
     * @return bool
     */
    public function shouldReport(Exception $e)
    {
        return ! $this->shouldNotReport($e);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function shouldNotReport(Exception $e)
    {
        return false;
    }

    /**
     * Render an exception into a response.
     *
     * @param  \Exception $e
     * @param  string $to - can be http, cli, debug
     *
     * @throws \abc\core\lib\AException
     */
    public function render( Exception $e, $to = 'http')
    {
        $e = $this->prepareException($e);

        if($to == 'http' && $this->debug){
            $whoops = new Run;
            $whoops->pushHandler(new PrettyPageHandler);
            $whoops->register();
            $whoops->handleException($e);
        }elseif( $to == 'cli'){
            //echo output_to_console
            $this->renderForConsole(new ConsoleOutput, $e);
        }else{
            //http output when debug is disabled
            if(class_exists('\abc\core\engine\Registry')) {
                $registry = Registry::getInstance();
                if ( $registry->has( 'router' ) && $registry->get( 'router' )->getRequestType() != 'page' ) {
                    $router = new ARouter( $registry );
                    $router->processRoute( 'error/ajaxerror' );
                    $registry->get( 'response' )->output();
                    exit();
                }
            }
            $url = "static_pages/index.php";
            $url .= (ABC::env('IS_ADMIN') === true) ? '?mode=admin' : '';
            header("Location: $url");
            exit();
        }
    }

    /**
     * Prepare exception for rendering.
     *
     * @param  \Exception  $e
     * @return \Exception
     */
    protected function prepareException(Exception $e)
    {
        return $e;
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        (new ConsoleApplication)->renderException($e, $output);
    }

    /**
     * @param Exception $exception
     *
     * @return string
     */
    public function getExceptionTraceAsString(Exception $exception) {
        $rtn = "";
        $count = 0;
        foreach ($exception->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = [];
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (is_array($arg)) {
                        $args[] = "Array";
                    } elseif (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_bool($arg)) {
                        $args[] = ($arg) ? "true" : "false";
                    } elseif (is_object($arg)) {
                        $args[] = get_class($arg);
                    } elseif (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = join(", ", $args);
            }
            $rtn .= sprintf( "#%s %s(%s): %s(%s)\n",
                                     $count,
                                     $frame['file'],
                                     $frame['line'],
                                     $frame['function'],
                                     $args );
            $count++;
        }
        return $rtn;
    }

}
