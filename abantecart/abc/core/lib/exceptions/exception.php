<?php

namespace abc\core\lib;

use abc\core\lib\contracts\ExceptionHandlerInterface;
use ErrorException;
use Exception;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class AHandleExceptions
{
    /**
     * The ABC instance.
     */
    protected $registry;
    protected $handler;

    /**
     * Bootstrap the given application.
     *
     * @param array                      $config
     *
     * @param ExceptionHandlerInterface $handler
     *
     * @return void
     */
    public function __construct(array $config, $handler)
    {
        $this->registry = $config;
        $this->handler = $handler;

        error_reporting(E_ALL & ~E_NOTICE);
        set_error_handler([$this, 'handleError'], E_ALL & ~E_NOTICE);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);

    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param  int    $level
     * @param  string $message
     * @param  string $file
     * @param  int    $line
     *
     * @return void
     *
     * @throws Exception
     */
    public function handleError($level, $message, $file = '', $line = 0)
    {
        if (error_reporting() & $level) {
            $this->getExceptionHandler()->report(new ErrorException($message, 0, $level, $file, $line));
            //throw
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param  \Throwable $e
     *
     * @return void
     */
    public function handleException($e)
    {
        if (!$e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        try {
            $this->getExceptionHandler()->report($e);
        } catch (Exception $e) {
            //
        }

        if (php_sapi_name() == 'cli') {
            $this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
        } else {
            $debug_bar = ADebug::$debug_bar;
            if ($debug_bar) {
                $debug_bar['exceptions']->addException($e);
            }
            $this->renderHttpResponse($e);
        }
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Exception $e
     *
     * @return void
     */
    protected function renderForConsole(Exception $e)
    {
        $this->getExceptionHandler()->render($e, 'cli');
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param  \Exception $e
     *
     * @return void
     */
    protected function renderHttpResponse(Exception $e)
    {
        $this->getExceptionHandler()->render($e, 'http');
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * @param  array    $error
     * @param  int|null $traceOffset
     *
     * @return \Symfony\Component\Debug\Exception\FatalErrorException
     */
    protected function fatalExceptionFromError(array $error, $traceOffset = null)
    {
        return new FatalErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
        );
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int $type
     *
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * @return ExceptionHandlerInterface
     */
    protected function getExceptionHandler()
    {
        return $this->handler;
    }
}
