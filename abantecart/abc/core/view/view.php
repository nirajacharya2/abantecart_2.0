<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\view;

use abc\core\ABC;
use abc\core\engine\ExtensionsApi;
use abc\core\lib\AbcCache;
use abc\core\lib\AConfig;
use abc\core\lib\ADebug;
use abc\core\lib\AError;
use abc\core\lib\AResponse;
use abc\core\lib\AWarning;
use H;

/**
 * Class AView
 *
 * @property AConfig          $config
 * @property ExtensionsAPI $extensions
 * @property AResponse        $response
 * @property AbcCache         $cache
 *
 */
class AView
{
    /**
     * @var $registry \abc\core\engine\Registry
     */
    protected $registry;
    /**
     * @var
     */
    protected $id;
    /**
     * @var string
     */
    protected $template = '';
    /**
     * @var string
     */
    protected $default_template;
    /**
     * @var int
     */
    protected $instance_id;
    /**
     * @var bool
     */
    protected $enableOutput = false;
    /**
     * @var string
     */
    protected $output = '';
    /**
     * @var array
     */
    protected $hook_vars = [];
    /**
     * @var array
     */
    public $data = [];
    /**
     * @var false | AViewRender | \abc\core\view\AViewDefaultRender
     */
    protected $render;
    /**
     * @var bool
     */
    protected $has_extensions;
    /**
     * @var string
     */
    protected $html_cache_key;
    /**
     * @var boolean
     */
    protected $is_admin;

    /**
     * @param \abc\core\engine\Registry $registry
     * @param int $instance_id
     *
     * @param null|bool $is_admin
     *
     * @throws \abc\core\lib\AException
     */
    public function __construct($registry, $instance_id, $is_admin = null)
    {
        require_once __DIR__.DS.'ViewRenderBase.php';
        $this->registry = $registry;
        $this->has_extensions = $this->registry->has('extensions');
        $this->is_admin = $is_admin === null ? ABC::env('IS_ADMIN') : $is_admin;
        if ($this->config) {
            if($this->is_admin){
                $this->template = $this->config->get('admin_template');
                $this->default_template = $this->config->get('admin_template');
            }else {
                $this->default_template = $this->config->get('config_storefront_template');
            }
        }
        $this->data['template_dir'] = ABC::env('RDIR_TEMPLATE');
        $this->data['tpl_common_dir'] = ABC::env('DIR_APP').ABC::env('RDIR_TEMPLATE').'common'.DS;
        $this->instance_id = $instance_id;

        $view_render_class = ABC::getFullClassName('AViewRender');
        /**
         * @var AViewRenderInterface $render_instance
         */
        $render_instance = H::getInstance(
                                                    $view_render_class,
                                                    [$this, $instance_id],
                                                    '\abc\core\view\AViewDefaultRender',
                                                    [$this, $instance_id]
        );
        //Note: this call will cause fatal error if class is not implements AViewRender interface!
        $this->setRender($render_instance);
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    /**
     * To allow to call methods of render from any point of code
     *
     * @param string $function_name
     * @param array  $args
     *
     * @return mixed|null
     */
    public function __call($function_name, $args)
    {
        if (method_exists($this->render, $function_name) && is_callable([$this->render, $function_name])) {
            return call_user_func_array([$this->render, $function_name], $args);
        } else {
            return null;
        }
    }

    /**
     * @param AViewRenderInterface $render
     */
    public function setRender(AViewRenderInterface $render)
    {
        $this->render = $render;
    }

    /**
     * @return AViewDefaultRender|AViewRender|false
     */
    public function getRender()
    {
        return $this->render;
    }

    /**
     * @void
     */
    public function enableOutput()
    {
        $this->enableOutput = true;
    }

    /**
     * @void
     */
    public function disableOutput()
    {
        $this->enableOutput = false;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        //clear output if template has been changed!
        $this->output = '';
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Return array with available variables and types in the view
     *
     * @param string $key - optional parameter to specify variable type of array.
     *
     * @return array
     */
    public function getVariables($key = '')
    {
        $variables = [];
        /**
         * @var array $scope
         */
        $scope = $key ? $this->data[$key] : $this->data;
        if (is_array($scope)) {
            foreach (array_keys($scope) as $var) {
                $variables[$var] = gettype($scope[$var]);
            }
        }

        return $variables;
    }

    /**
     * @param string $key - optional parameter for better access from hook that called by "_UpdateData".
     *
     * @return array | mixed - reference to $this->data
     */
    public function &getData($key = '')
    {
        if ($key) {
            return $this->data[$key];
        } else {
            return $this->data;
        }
    }

    /**
     * @param string $template_variable
     * @param string $value
     * @param string $default_value
     *
     * @return null
     */
    public function assign($template_variable, $value = '', $default_value = '')
    {
        if (empty($template_variable)) {
            return false;
        }
        if (!is_null($value)) {
            $this->data[$template_variable] = $value;
        } else {
            $this->data[$template_variable] = $default_value;
        }

        return true;
    }

    /**
     * Call append if you need to add values to earlier assigned value
     *
     * @param string $template_variable
     * @param string $value
     * @param string $default_value
     *
     * @return bool
     */
    public function append($template_variable, $value = '', $default_value = '')
    {
        if (empty($template_variable)) {
            return false;
        }
        if (!is_null($value)) {
            $this->data[$template_variable] .= $value;
        } else {
            $this->data[$template_variable] .= $default_value;
        }
        return true;
    }

    /**
     * @param array $assign_arr - associative array
     *
     * @return null
     */
    public function batchAssign($assign_arr)
    {
        if (empty($assign_arr) || !is_array($assign_arr)) {
            return false;
        }

        foreach ($assign_arr as $key => $value) {
            //when key already defined and type of old and new values are different send warning in debug-mode
            if (isset($this->data[$key]) && is_object($this->data[$key])) {
                $warning_text = 'Warning! Variable "'.$key.'" in template "'
                                .$this->template.'" overriding value and data type "object." ';
                $warning_text .= 'Possibly need to review your code! (also check that '
                                .'extensions do not load language definitions in UpdateData hook).';
                $warning = new AWarning($warning_text);
                $warning->toDebug();
                continue; // prevent overriding.
            } elseif (isset($this->data[$key]) && gettype($this->data[$key]) != gettype($value)) {
                $warning_text = 'Warning! Variable "'.$key.'" in template "'.$this->template
                               .'" overriding value and data type "'.gettype($this->data[$key]).'" ';
                $warning_text .= 'Forcing new data type '.gettype($value).'. Possibly need to review your code!';
                $warning = new AWarning($warning_text);
                $warning->toDebug();
            }
            $this->data[$key] = $value;
        }

        return true;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addHookVar($name, $value)
    {
        if (!empty($name)) {
            $this->hook_vars[$name] .= $value;
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getHookVar($name)
    {
        if (isset($this->hook_vars[$name])) {
            return $this->hook_vars[$name];
        }
        return '';
    }

    // Render html output
    public function render()
    {
        // If no template return empty. We might have controller that has no templates
        if (!empty($this->template) && $this->enableOutput) {
            $compression = '';
            if ($this->config) {
                $compression = $this->config->get('config_compression');
            }
            if (!empty($this->output)) {
                $this->response->setOutput($this->output, $compression);
            } else {
                $this->response->setOutput($this->fetch($this->template), $compression);
            }
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getOutput()
    {
        return (!empty($this->output) ? $this->output : !empty($this->template))
            ? $this->fetch($this->template)
            : '';
    }

    /**
     * @param string $output
     *
     * @void
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Process the template
     *
     * @param $filename
     *
     * @return string
     * @throws \Exception
     */
    public function fetch($filename)
    {
        ADebug::checkpoint('fetch '.$filename.' start');
        //First see if we have full path to template file. Nothing to do. Higher precedence!
        if (is_file($filename)) {
            //set full path
            $file = $filename;
        } else {
            //Build the path to the template file
            if (ABC::env('INSTALL')) {
                $file = ABC::env('DIR_INSTALL').ABC::env('DIRNAME_TEMPLATES').$filename;
            } else {
                $file = $this->getTemplateResourcePath(ABC::env('DIR_TEMPLATES'), $filename, 'full');
            }

            if ($this->has_extensions && $result = $this->extensions->isExtensionResource('T', $filename)) {
                if (is_file($file)) {
                    $warning = new AWarning(
                        "Extension <b>".$result['extension']
                        ."</b> overrides core template with <b>".$filename."</b>"
                    );
                    $warning->toDebug();
                }
                $file = $result['file'];
            }
        }

        if (empty($file)) {
            $error = new AError(
                'Error: Unable to identify file path to template '
                .$filename.'! Check blocks in the layout or enable debug mode to get more details. ',
                AC_ERR_LOAD
            );
            $error->toDebug()->toLog();
            return '';
        }

        if (is_file($file)) {
            $content = '';
            $file_pre = str_replace('.tpl', ABC::env('POSTFIX_PRE').'.tpl', $filename);
            if ($result = $this->extensions->getAllPrePostTemplates($file_pre)) {
                foreach ($result as $item) {
                    $content .= $this->_fetch($item['file']);
                }
            }

            $content .= $this->_fetch($file);

            $file_post = str_replace('.tpl', ABC::env('POSTFIX_POST').'.tpl', $filename);
            if ($result = $this->extensions->getAllPrePostTemplates($file_post)) {
                foreach ($result as $item) {
                    $content .= $this->_fetch($item['file']);
                }
            }
            ADebug::checkpoint('fetch '.$filename.' end');

            //Write HTML Cache if we need and can write
            //if ($this->config && $this->config->get('config_html_cache') && $this->html_cache_key) {
                //TODO: needs to check this!
                /*if ($this->cache->save_html_cache($this->html_cache_key, $content) === false) {
                    $error = new AError(
                        'Error: Cannot create HTML cache for file '
                        .$this->html_cache_key.'! Directory to write cache is not writable',
                        AC_ERR_LOAD);
                    $error->toDebug()->toLog();
                }*/
            //}
            return $content;
        } else {
            $error = new AError(
                'Error: Cannot load template '
                .$filename.'! File '.$file
                .' is missing or incorrect. '
                .'Check blocks in the layout or enable debug mode to get more details. ',
                AC_ERR_LOAD);
            $error->toDebug()->toLog();
        }
        return '';
    }

    /**
     * Storefront function to return path to the resource
     *
     * @param string $filename
     * @param string $mode Mode to return format: http | file
     *
     * @return string with relative path
     */
    public function templateResource($filename, $mode = 'http')
    {
        if (!$filename) {
            return null;
        }
        $http_path = '';
        $res_arr = $this->extensionsResourceMap($filename, $mode);
        //get first exact template extension resource or default template resource otherwise.
        if (isset($res_arr['original'][0])) {
            $output = $res_arr['original'][0];
        } else {
            if (isset($res_arr['default'][0])) {
                $output = $res_arr['default'][0];
            } else {
                //no extension found, use resource from core templates
                $mode2 = $mode == 'file' ? '' : 'relative';
                if( pathinfo($filename,PATHINFO_EXTENSION) == 'tpl'){
                    $src_path = ABC::env('DIR_TEMPLATES');
                }else {
                    $src_path = !$mode2
                        ? ABC::env('DIR_TEMPLATES')
                        : ABC::env('DIR_PUBLIC').ABC::env('DIRNAME_TEMPLATES');
                }

                $output = $this->getTemplateResourcePath($src_path, $filename, $mode2);
            }
        }

        if (!in_array(pathinfo($filename, PATHINFO_EXTENSION), ['tpl', 'php'])) {
            $this->extensions->hk_ProcessData($this, __FUNCTION__);
            $http_path = $this->data['http_dir'];
        }

        if (strpos($filename, ABC::env('DIRNAME_VENDOR')) > -1) {
            return  $http_path.$filename;
        }
        else if ($mode == 'http') {
            return $http_path.$output;
        } else {
            if ($mode == 'file') {
                return $output;
            } else {
                return '';
            }
        }
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function isTemplateExists($filename)
    {
        if (!$filename) {
            return false;
        }

        //check if this template file in extensions or in core
        if ($this->templateResource($filename)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if HTML Cache file present
     *
     * @param string $key
     *
     * @return bool
     */
    public function setCacheKey($key)
    {
        $this->html_cache_key = $key;
        return true;
    }

    /**
     * Check if HTML Cache file present
     *
     * @param string $key
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function checkHTMLCache($key)
    {
        if (!$key) {
            return false;
        }
        $this->html_cache_key = $key;
        $html_cache = $this->cache->get($key);
        if ($html_cache) {
            $compression = '';
            if ($this->config) {
                $compression = $this->config->get('config_compression');
            }
            $this->response->setOutput($html_cache, $compression);
            return true;
        }
        return false;
    }

    /**
     * @deprecated
     * TODO: move this functionality into publisher
     * Build or load minified CSS and return an output.
     *
     * @param string $css_file css file with relative name
     * @param string $group CSS group name for caching
     *
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function LoadMinifyCSS($css_file, $group = 'css')
    {
        if (empty($css_file)) {
            return '';
        }
        //build hash key
        $key = '';
        //get file time stamp
        $key .= $css_file."-".filemtime($this->templateResource($css_file, 'file'));
        $key = $group.".".md5($group.'-'.$key);
        //check if hash is created and load
        $css_data = $this->cache->get($key);
        if ($css_data === null) {
            require_once(ABC::env('DIR_CORE').'helper/html-css-js-minifier.php');
            //build minified css and save
            $path = dirname($this->templateResource($css_file, 'http'));
            $new_content = file_get_contents($this->templateResource($css_file, 'file'));
            //replace relative directories with full path
            $css_data = preg_replace('/\.\.\//', $path.'/../', $new_content);
            $css_data = abc_minify_css($css_data);
            $this->cache->put($key, $css_data);
        }

        return $css_data;
    }

    /**
     * Beta!
     * Preload JavaScript and return an output.
     *
     * @param string|array $js_file file(s) with relative name
     * @param string $group JS group name for caching
     *
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function PreloadJS($js_file, $group = 'js')
    {
        if (empty($js_file)) {
            return '';
        }
        //build hash key
        $key = '';
        //get file time stamp
        if (is_array($js_file)) {
            foreach ($js_file as $js) {
                //get file time stamp
                $key .= $js."-".filemtime($this->templateResource($js, 'file'));
            }
        } else {
            $key .= $js_file."-".filemtime($this->templateResource($js_file, 'file'));
        }

        $key = $group.".".md5($group.'-'.$key);
        //check if hash is created and load
        $js_data = $this->cache->get($key);
        if ($js_data === null) {
            //load js and save to cache
            //TODO: Add stable minify method. minify_js in html-css-js-minifier.php is not stable
            $js_data = '';
            if (is_array($js_file)) {
                foreach ($js_file as $file) {
                    $js_data .= file_get_contents($this->templateResource($file, 'file'))."\n";
                }
            } else {
                $js_data .= file_get_contents($this->templateResource($js_file, 'file'));
            }
            //$js_data = minify_js($js_data);
            $this->cache->put($key, $js_data);
        }

        return $js_data;
    }

    /**
     * full directory path
     *
     * @param string $extension_name
     *
     * @return string
     */
    protected function extensionTemplatesDir($extension_name)
    {
        return $this->extensionSectionDir($extension_name).ABC::env('DIRNAME_TEMPLATES');
    }

    /**
     * full directory path
     *
     * @param string $extension_name
     *
     * @return string
     */
    protected function extensionSectionDir($extension_name)
    {
        return ABC::env('DIR_APP_EXTENSIONS').$extension_name.DS;
    }

    /**
     * Build template source map for enabled extensions
     *
     * @param string $filename
     * @param string $mode
     *
     * @return array
     */
    protected function extensionsResourceMap($filename, $mode = 'file')
    {
        if (empty($filename)) {
            return [];
        }
        $output = [];
        $extensions = $this->extensions->getEnabledExtensions();
        //loop through each extension and locate resource to use
        //Note: first extension with exact resource or default resource will be used
        $test_resource_mode = $mode != 'file' ? 'relative' : $mode;
        foreach ($extensions as $ext) {
            if ($test_resource_mode == 'relative') {
                $source_dir = ABC::env('DIR_PUBLIC')
                    .ABC::env('DIRNAME_EXTENSIONS')
                    .$ext.DS
                    .ABC::env('DIRNAME_TEMPLATES');
            } else {
                $source_dir = $this->extensionTemplatesDir($ext);
            }
            $res_arr = $this->testTemplateResourcePaths($source_dir, $filename, $test_resource_mode, $ext);

            if ($res_arr) {

                $output[$res_arr['match']][] = $res_arr['path'];
            }
        }
        return $output;
    }

    /**
     * return path to the template resource
     *
     * @param string $path
     * @param string $filename
     * @param string $mode
     *
     * @return mixed
     */
    protected function getTemplateResourcePath($path, $filename, $mode)
    {
        //look into extensions first
        $res_arr = $this->extensionsResourceMap($filename);
        //get first exact template extension resource or default template resource otherwise.
        if (isset($res_arr['original'][0])) {
            return $res_arr['original'][0];
        } else {
            if (isset($res_arr['default'][0])) {
                return $res_arr['default'][0];
            }
        }

        $template_path_arr = $this->testTemplateResourcePaths($path, $filename, $mode);
        return $template_path_arr['path'];
    }

    /**
     * Function to test file paths and location of original or default file
     *
     * @param string $path
     * @param string $filename
     * @param string $mode
     * @param string $extension_txt_id
     *
     * @return array|null
     */
    protected function testTemplateResourcePaths($path, $filename, $mode = 'relative', $extension_txt_id = '')
    {
        $template = $this->default_template;
        $match = 'original';
        $dir_public = ABC::env('DIR_PUBLIC');
        $section_dirname = $this->is_admin ? ABC::env('DIRNAME_ADMIN') : ABC::env('DIRNAME_STORE');
        $dirname_templates = ABC::env('DIRNAME_TEMPLATES');
        $slash = DS;
        if ($mode == 'relative') {
            if ($extension_txt_id) {
                $public_dir_pre = ABC::env('DIRNAME_EXTENSIONS').$extension_txt_id.$slash.$dirname_templates;
            } else {
                $public_dir_pre = $dirname_templates;
            }
        } else {
            $public_dir_pre = $this->is_admin ? $dirname_templates : $dir_public.$dirname_templates;
        }

        $ret_path = $this->getPath(
            $path,
            $public_dir_pre,
            $template.$slash.$section_dirname.$filename, $mode
        );



        //try to find file in default template of extension or core
        if (!$ret_path && !$extension_txt_id) {
            $match = 'default';

            $ret_path = $this->getPath(
                $path,
                $public_dir_pre,
                'default'.$slash.$section_dirname.$filename,
                $mode
            );
            if (!$ret_path) {
                $ret_path = $this->getPath(
                    $dir_public.$dirname_templates,
                    $dirname_templates,
                    'default'.$slash.$section_dirname.$filename,
                    $mode
                );
            }
        }

        //return path. Empty path indicates, nothing found
        if ($ret_path) {
            return [
                'match' => $match,
                'path'  => $ret_path,
            ];
        } else {
            return null;
        }
    }

    /**
     * @param string $full_path_pre - full path to directory which contains template file
     * @param string $rel_path_pre  - relative path to directory which contains template file
     * @param string $template_path - relative path of template file. starts with template_txt_id
     * @param string $mode          - can be "relative" or other. Mode of returning (full path or relative)
     *
     * @return string
     */
    protected function getPath($full_path_pre, $rel_path_pre, $template_path, $mode = '')
    {
        $full_file_path = $full_path_pre.$template_path;
        if (is_file($full_file_path)) {
            $ret_path = $full_path_pre.$template_path;
            if ($mode == 'relative') {
                $ret_path = $rel_path_pre.$template_path;
            }
            return $ret_path;
        }
        return '';
    }

    /**
     * @param $file string - full path of file
     *
     * @return string
     */
    public function _fetch($file)
    {
        if (!file_exists($file)) {
            return '';
        }

        ADebug::checkpoint('_fetch '.$file.' start. View-render: '.get_class($this->render));
        $content = $this->render->fetch($file, $this->data);
        ADebug::checkpoint('_fetch '.$file.' end');
        return $content;
    }
}
