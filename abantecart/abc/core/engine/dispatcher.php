<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\engine;

use abc\core\ABC;
use abc\core\lib\ADebug;
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AWarning;
use H;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Class ADispatcher
 *
 */
final class ADispatcher
{
    /** @var Registry */
    protected $registry;
    /** @var string */
    protected $file;
    /** @var string */
    protected $class;
    /** @var string */
    protected $method;
    /** @var string */
    protected $controller;
    /** @var string */
    protected $controller_type;
    /** @var array */
    protected $args = [];

    /**
     * @param string $rt
     * @param array $args
     *
     */
    public function __construct($rt, $args = [])
    {

        $this->registry = Registry::getInstance();
        $rt = str_replace('../', '', $rt);
        if (!empty($args)) {
            $this->args = $args;
        }

        ADebug::checkpoint('ADispatch: ' . $rt . ' construct start');
        // We always get full RT (route) to dispatcher. Needs to have pages/ or responses/
        if (!$this->processPath($rt)) {
            $warning_txt = 'ADispatch: ' . $rt . ' construct FAILED. '
                . 'Side: ' . (ABC::env('IS_ADMIN') ? 'Admin' : 'StoreFront')
                . ' Missing or incorrect controller route path. '
                . 'Possibly, layout block is enabled for disabled or missing extension! '
                . H::genExecTrace('full');
            $warning = new AWarning($warning_txt);
            $warning->toLog()->toDebug();
        }
        ADebug::checkpoint('ADispatch: ' . $rt . ' construct end. file: class: ' . $this->class . '; method: ' . $this->method);
    }

    public function __destruct()
    {
        $this->clear();
    }

    /**
     * @param string $rt
     *
     * @return bool
     */
    private function processPath($rt)
    {
        // Build the path based on the route, example, rt=information/contact/success
        $path_nodes = explode('/', $rt);

        //looking for controller in admin/storefront section
        if (ABC::env('INSTALL') === true) {
            $dir_app = ABC::env('DIR_INSTALL') . 'controllers' . DS;
            $namespace = '\install\controllers\\';
            $pathFound = $this->detectPath($dir_app, $namespace, $path_nodes);
            if (!$pathFound) {
                $dir_app = ABC::env('DIR_APP') . 'controllers' . DS . 'admin' . DS;
                $namespace = '\abc\controllers\admin\\';
                $pathFound = $this->detectPath($dir_app, $namespace, $path_nodes);
            }
        } elseif (ABC::env('IS_ADMIN') === true) {
            $dir_app = ABC::env('DIR_APP') . 'controllers' . DS . 'admin' . DS;
            $namespace = '\abc\controllers\admin\\';
            $pathFound = $this->detectPath($dir_app, $namespace, $path_nodes);
        } else {
            $dir_app = ABC::env('DIR_APP') . 'controllers' . DS . 'storefront' . DS;
            $namespace = '\abc\controllers\storefront\\';
            $pathFound = $this->detectPath($dir_app, $namespace, $path_nodes);
        }

        //Last part is the method of function to call
        $method_to_call = array_shift($path_nodes);
        if ($method_to_call) {
            $this->method = $method_to_call;
        } else {
            //Set default method
            $this->method = 'main';
        }

        //already found the path, so return. This will optimize performance,
        // and will not allow override core controllers.
        if ($pathFound) {
            return $pathFound;
        }

        // looking for controller in extensions section
        $result = Registry::extensions()?->isExtensionController($rt);
        if ($result) {
            $this->controller = $result['route'];
            $this->file = $result['file'];
            $this->class = $namespace . $result['class'];
            $this->method = $result['method'];
            // if controller was found in admin/storefront section && in extensions section
            // warning will be added to log about controller override
            $warning = new AWarning("Extension <b>" . $result['extension'] . "</b> override controller <b>" . $rt . "</b>");
            $warning->toDebug();
            $pathFound = true;
        }
        return $pathFound;
    }

    private function detectPath($dir_app, $namespace, &$path_nodes)
    {
        $path_build = '';
        $pathFound = false;
        foreach ($path_nodes as $path_node) {
            $path_build .= $path_node;
            if (is_dir($dir_app . $path_build)) {
                $path_build .= DS;
                array_shift($path_nodes);
                continue;
            }

            if (is_file($dir_app . $path_build . '.php')) {
                //Set pure controller route
                $this->controller = $path_build;
                //Set full file path to controller
                $this->file = $dir_app . $path_build . '.php';
                //Build Controller class name
                $this->class = $namespace . 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $path_build);
                array_shift($path_nodes);
                $pathFound = true;
                break;
            }
        }

        return $pathFound;
    }

    // Clear function is public in case controller needs to be cleaned explicitly
    public function clear()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $val) {
            $this->$key = null;
        }
    }

    /**
     * @param string $route
     *
     * @return string
     * @throws ReflectionException|AException
     */
    protected function dispatchPrePost($route)
    {
        $result = '';
        $responseObj = Registry::response();
        if (Registry::extensions()->isExtensionController($route)) {
            //save output
            $output = $responseObj->getOutput();
            //reset to save controller output
            $responseObj->setOutput('');

            $dispatch_pre = new ADispatcher($route, ["instance_id" => null]);
            $dispatch_pre->dispatch();
            $result = $responseObj->getOutput();

            //restore output
            $responseObj->setOutput($output);
        }

        return $result;
    }

    /**
     * This function to dispatch the controller and get and destroy it's output
     *
     * @param string $controller
     *
     * @return string
     * @throws ReflectionException|AException
     */
    public function dispatchGetOutput($controller = '')
    {
        $this->dispatch($controller);
        $responseObj = Registry::response();
        $output = $responseObj->getOutput();
        $responseObj->setOutput('');
        return $output;
    }

    /**
     * @param AController|string $parent_controller
     *
     * @return null|string
     * @throws ReflectionException|AException
     */
    public function dispatch($parent_controller = '')
    {
        ADebug::checkpoint($this->class . '/' . $this->method . ' dispatch START');
        $responseObj = Registry::response();

        //Process the controller, layout and children

        //check if we have missing class or everything
        if (empty($this->class) && H::has_value($this->file)) {
            #Build back trace of calling functions to provide more details
            $backtrace = debug_backtrace();
            $function_stack = '';
            if (is_object($parent_controller) && strlen($parent_controller->rt()) > 1) {
                $function_stack = 'Parent Controller: ' . $parent_controller->rt() . ' | ';
            }
            for ($i = 1; $i < count($backtrace); $i++) {
                $function_stack .= ' < ' . $backtrace[$i]['function'];
            }
            $url = Registry::request()->server['REQUEST_URI'];
            $error = new AError(
                'Error: URL: ' . $url . ' Could not load controller '
                . $this->controller . '! Call stack: ' . $function_stack,
                AC_ERR_CLASS_CLASS_NOT_EXIST);
            $error->toLog()->toDebug();
            return null;
        } else {
            if (empty($this->file) && empty($this->class) || empty($this->method)) {
                $warning_txt = 'ADispatch: skipping unavailable controller …';
                $warning = new AWarning($warning_txt);
                $warning->toDebug();
                return null;
            }
        }

        //check for controller.pre
        $output_pre = $this->dispatchPrePost($this->controller . ABC::env('POSTFIX_PRE'));

        require_once($this->file);
        /**
         * @var $controller AController
         */
        $controller = null;
        if (class_exists($this->class)) {
            $controller = new $this->class(
                $this->registry,
                $this->args["instance_id"],
                $this->controller,
                $parent_controller
            );
            $controller->dispatcher = $this;
        } else {
            $error = new AError('Error: controller class not exist ' . $this->class . '!', AC_ERR_CLASS_CLASS_NOT_EXIST);
            $error->toLog()->toDebug();
        }
        try {
            if (is_callable([$controller, $this->method])) {
                /**
                 * @var $dispatch ADispatcher
                 */
                $r = new ReflectionMethod($controller, $this->method);
                $params = $r->getParameters();
                if (!$params) {
                    $args = [];
                } else {
                    $args = $this->args;
                }

                $rfl = new ReflectionClass($controller);
                $method = $rfl->getMethod($this->method);
                if ($method) {
                    $allParameters = $method->getParameters();
                    if ($allParameters) {
                        $methodParams = [];
                        foreach ($allParameters as $p) {
                            if (isset($args[$p->name])) {
                                $methodParams[$p->name] = $args[$p->name];
                            }
                        }
                        if ($methodParams && H::isAssocArray($args)) {
                            $args = $methodParams;
                        } elseif (!$methodParams && H::isAssocArray($args)) {
                            $args = [];
                        }
                    }
                }

                $dispatch = call_user_func_array([$controller, $this->method], $args);
                //Check if return is a dispatch and need to call new page
                if ($dispatch && is_object($dispatch)) {
                    if (!$this->args["instance_id"]) {
                        //If main controller come back for new dispatch
                        return $dispatch->getController() . '/' . $dispatch->getMethod();
                    } else {
                        // Call new dispatch for new controller and exit
                        $dispatch->dispatch();
                        return null;
                    }
                } else {
                    if ($dispatch == 'completed') {
                        //Check if we have message completed in controller response.
                        //If completed. stop further execution.
                        return 'completed';
                    }
                }
                /**
                 * Load layout and process children controllers
                 * @method AController getChildren()
                 */
                $children = $controller->getChildren();

                ADebug::variable('Processing children of ' . $this->controller, $children);

                //Process each child controller
                foreach ($children as $child) {
                    //Add the highest debug level here with backtrace to review this
                    ADebug::checkpoint(
                        $child['controller']
                        . ' ( child of ' . $this->controller . ', instance_id: ' . $child['instance_id']
                        . ' ) dispatch START'
                    );
                    //Process each child and create dispatch to call recursive
                    $dispatch = new ADispatcher($child['controller'], ["instance_id" => $child['instance_id']]);
                    $dispatch->dispatch($controller);
                    // Append output of child controller to current controller
                    if (isset($child['position'])
                        && $child['position']) { // made for recognizing few custom_blocks in the same placeholder
                        $controller->view->assign(
                            $child['block_txt_id'] . '_' . $child['instance_id'],
                            $responseObj->getOutput()
                        );
                    } else {
                        $controller->view->assign($child['block_txt_id'], $responseObj->getOutput());
                    }
                    //clean up and remove output
                    $responseObj->setOutput('');
                    ADebug::checkpoint($child['controller'] . ' ( child of ' . $this->controller . ' ) dispatch END');
                }
                //Request controller to generate output
                $controller->finalize();

                //check for controller.pre
                $output_post = $this->dispatchPrePost($this->controller . ABC::env('POSTFIX_POST'));

                //add pre and post controllers output
                $responseObj->setOutput($output_pre . $responseObj->getOutput() . $output_post);

                //clean up and destroy the object
                unset($controller, $dispatch);
            } else {
                $err = new AError(
                    'Error: controller method not exist ' . $this->class . '::' . $this->method . '!',
                    AC_ERR_CLASS_METHOD_NOT_EXIST
                );
                $err->toLog()->toDebug();
                if (in_array($this->controller_type, ['responses', 'api', 'task'])) {
                    $dd = new ADispatcher('responses/error/ajaxerror/not_found');
                    $dd->dispatch();
                }
            }
        } //catching output of around hook (it can be only one)
        catch (AException $e) {
            if ($e->getCode() != AC_HOOK_OVERRIDE) {
                throw $e;
            }
        }
        ADebug::checkpoint($this->class . '/' . $this->method . ' dispatch END');

        return null;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->controller_type;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }
}
