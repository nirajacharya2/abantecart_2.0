<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AError;
use abc\core\lib\APackageManager;

if ( ! class_exists('abc\core\ABC') || ! \abc\core\ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

if (ABC::env('IS_DEMO')) {
    header('Location: static_pages/demo_mode.php');
}

/**
 * Class ControllerPagesToolPackageInstaller
 *
 * @property \abc\models\admin\ModelToolMPApi $model_tool_mp_api
 */
class ControllerPagesToolPackageInstaller extends AController
{
    public $data;

    public function main()
    {
        $package_info = &$this->session->data['package_info'];
        $extension_key = ! $this->request->get['extension_key'] ? '' : trim($this->request->get['extension_key']);
        $extension_key = ! $this->request->post['extension_key'] ? $extension_key : trim($this->request->post['extension_key']);
        $extension_key = $package_info['extension_key'] ? $package_info['extension_key'] : $extension_key;

        $this->session->data['package_info'] = [];
        //clean temporary directory
        $this->_clean_temp_dir();

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->initBreadcrumb(array(
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ));
        $this->document->addBreadcrumb(array(
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ));

        $form = new AForm('ST');
        $form->setForm(
            array('form_name' => 'installFrm')
        );
        $this->data['form']['form_open'] = $form->getFieldHtml(
            array(
                'type'   => 'form',
                'name'   => 'installFrm',
                'action' => $this->html->getSecureURL('tool/package_installer/download'),
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            ));

        $this->data['form']['fields']['input'] = $form->getFieldHtml(
            array(
                'type'        => 'input',
                'name'        => 'extension_key',
                'value'       => $extension_key,
                'attr'        => 'autocomplete="off" ',
                'placeholder' => $this->language->get('text_key_hint'),
                'help_url'    => $this->gen_help_url('extension_key'),
            ));

        $this->data['form']['submit'] = $form->getFieldHtml(
            array(
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('text_continue'),
            ));

        if (isset($this->session->data['error'])) {
            $error_txt = $this->session->data['error'];
            $error_txt .= '<br>'.$this->language->get('error_additional_help_text');
            $this->data['error_warning'] = $error_txt;
            unset($package_info['package_dir'], $this->session->data['error'], $error_txt);
        }
        unset($this->session->data['error'], $this->session->data['success']);
        //run pre-check
        $this->_pre_check();

        $package_info['package_source'] = 'network';
        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->_initTabs('key');

        $this->view->assign('help_url', $this->gen_help_url(''));
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer.tpl');
    }

    // method for uploading package via form
    public function upload()
    {
        //clean temporary directory
        $this->_clean_temp_dir();

        $this->session->data['package_info'] = array();
        $package_info =& $this->session->data['package_info'];
        $package_info['package_source'] = 'file';

        $package_info['tmp_dir'] = $this->_get_temp_dir();

        // process post
        if ($this->request->is_POST()) {
            $tmp_filename = $this->request->files['package_file']['tmp_name'];
            $real_file_name = $this->request->files['package_file']['name'];
            if (is_uploaded_file($tmp_filename)) {
                if ( ! is_int(strpos($real_file_name, '.tar.gz')) && strtolower(pathinfo($real_file_name, PATHINFO_EXTENSION)) != 'zip') {
                    unlink($tmp_filename);
                    $this->session->data['error'] .= $this->language->get('error_archive_extension');
                } else {
                    $result = move_uploaded_file($tmp_filename,
                        $package_info['tmp_dir'].$real_file_name);
                    if ( ! $result || $this->request->files['package_file']['error']) {
                        $this->session->data['error'] .= '<br>Error: '.AHelperUtils::getTextUploadError($this->request->files['package_file']['error']);
                    } else {
                        $package_info['package_name'] = $real_file_name;
                        $package_info['package_size'] = $this->request->files['package_file']['size'];
                        abc_redirect($this->html->getSecureURL('tool/package_installer/agreement'));
                    }
                }
            } else {
                if ($this->request->post['package_url']) {
                    $package_info['package_url'] = $this->request->post['package_url'];
                    abc_redirect($this->html->getSecureURL('tool/package_installer/download'));
                } else {
                    $this->session->data['error'] .= '<br>Error: '.AHelperUtils::getTextUploadError($this->request->files['package_file']['error']);
                }
            }
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->initBreadcrumb(array(
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ));
        $this->document->addBreadcrumb(array(
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ));

        $form = new AForm('ST');
        $form->setForm(
            array('form_name' => 'uploadFrm')
        );
        $this->data['form']['form_open'] = $form->getFieldHtml(
            array(
                'type'   => 'form',
                'name'   => 'uploadFrm',
                'action' => $this->html->getSecureURL('tool/package_installer/upload'),
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            ));

        $this->data['form']['fields']['upload_file'] = $form->getFieldHtml(
            array(
                'type'  => 'file',
                'name'  => 'package_file',
                'value' => '',
                'attr'  => ' autocomplete="off" ',
            ));

        $this->data['form']['fields']['upload_url'] = $form->getFieldHtml(
            array(
                'type'  => 'input',
                'name'  => 'package_url',
                'value' => '',
                'attr'  => ' autocomplete="off" ',
            ));

        $this->data['form']['submit'] = $form->getFieldHtml(
            array(
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('text_continue'),
            ));

        if (isset($this->session->data['error'])) {
            $error_txt = $this->session->data['error'];
            $error_txt .= '<br>'.$this->language->get('error_additional_help_text');
            $this->data['error_warning'] = $error_txt;
            unset($package_info['package_dir'], $error_txt);
        }
        unset($this->session->data['error']);

        //run pre-check
        $this->_pre_check();

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->_initTabs('upload');

        $this->data['upload'] = true;
        $this->data['text_or'] = $this->language->get('text_or');
        $this->view->assign('help_url', $this->gen_help_url(''));

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer.tpl');
    }

    protected function _pre_check()
    {
        $pmanager = new APackageManager( $this->session->data['package_info'] );
        if ( ! $pmanager->validate()) {
            $this->data['error_warning'] .= $pmanager->error;
        }
    }

    protected function _initTabs($active = null)
    {

        $this->data['tabs'] = array();
        $this->data['tabs']['key'] = array(
            'href' => $this->html->getSecureURL('tool/package_installer'),
            'text' => $this->language->get('text_network_install'),
        );

        $this->data['tabs']['upload'] = array(
            'href' => $this->html->getSecureURL('tool/package_installer/upload'),
            'text' => $this->language->get('text_extension_upload'),
        );

        if (in_array($active, array_keys($this->data['tabs']))) {
            $this->data['tabs'][$active]['active'] = 1;
        } else {
            $this->data['tabs']['key']['active'] = 1;
        }
    }

    public function download()
    {
        // for short code
        $package_info =& $this->session->data['package_info'];
        $extension_key = trim($this->request->post_or_get('extension_key'));
        $disclaimer = false;
        $mp_token = '';
        $package_name = '';
        $already_downloaded = false;

        if ( ! $extension_key && ! $package_info['package_url']) {
            $this->_removeTempFiles();
            abc_redirect($this->_get_begin_href());
        }

        if ($this->request->is_GET()) {
            //reset installer array after redirects
            $this->_removeTempFiles();
            if ($extension_key) {
                //reset array only for requests by key (exclude upload url method)
                $this->session->data['package_info'] = array();
            }
        } // if does not agree  with agreement of filesize
        elseif ($this->request->is_POST()) {
            if ($this->request->post['disagree'] == 1) {
                $this->_removeTempFiles();
                unset($this->session->data['package_info']);
                abc_redirect($this->html->getSecureURL('extension/extensions/extensions'));
            } else {
                $disclaimer = (int)$this->request->get['disclaimer'];
                // prevent multiple show for disclaimer
                $this->session->data['installer_disclaimer'] = true;
            }
        }
        $pmanager = new APackageManager( $this->session->data['package_info'] );
        if ( $pmanager->isCorePackage($extension_key)) {
            $disclaimer = true;
        }

        if ( ! $disclaimer && ! $this->session->data['installer_disclaimer']) {
            $this->view->assign('heading_title', $this->language->get('text_disclaimer_heading'));

            $form = new AForm('ST');
            $form->setForm(array('form_name' => 'Frm'));
            $this->data['form']['form_open'] = $form->getFieldHtml(array(
                'type'   => 'form',
                'name'   => 'Frm',
                'action' => $this->html->getSecureURL('tool/package_installer/download'),
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            ));

            $this->data['form']['hidden'][] = $form->getFieldHtml(array(
                'id'    => 'extension_key',
                'type'  => 'hidden',
                'name'  => 'extension_key',
                'value' => $extension_key,
            ));

            $this->data['agreement_text'] = $this->language->get('text_disclaimer');

            $this->data['form']['disagree_button'] = $form->getFieldHtml(array(
                'type' => 'button',
                'href' => $this->_get_begin_href(),
                'text' => $this->language->get('text_interrupt'),
            ));

            $this->data['form']['submit'] = $form->getFieldHtml(array(
                'type' => 'button',
                'text' => $this->language->get('text_agree'),
            ));

            $this->data['form']['agree'] = $form->getFieldHtml(array(
                'type'  => 'hidden',
                'name'  => 'disclaimer',
                'value' => '0',
            ));

            $this->view->batchAssign($this->data);
            $this->processTemplate('pages/tool/package_installer_agreement.tpl');

            return null;
        }

        $form = new AForm('ST');
        $form->setForm(array('form_name' => 'retryFrm'));
        $this->data['form']['form_open'] = $form->getFieldHtml(array(
            'type'   => 'form',
            'name'   => 'retryFrm',
            'action' => $this->html->getSecureURL('tool/package_installer/download'),
        ));

        $this->data['form']['hidden'][] = $form->getFieldHtml(array(
            'id'    => 'extension_key',
            'type'  => 'hidden',
            'name'  => 'extension_key',
            'value' => $extension_key,
        ));

        $this->data['form']['hidden'][] = $form->getFieldHtml(array(
            'id'    => 'disclaimer',
            'type'  => 'hidden',
            'name'  => 'disclaimer',
            'value' => '1',
        ));

        $this->data['form']['cancel'] = $form->getFieldHtml(array(
            'type' => 'button',
            'href' => $this->_get_begin_href(),
            'text' => $this->language->get('button_cancel'),
        ));

        $this->data['form']['retry'] = $form->getFieldHtml(array(
            'type' => 'button',
            'text' => $this->language->get('text_retry'),
        ));

        $this->view->assign('text_download_error', $this->language->get('text_download_error'));

        $package_info['extension_key'] = $extension_key;

        $package_info['tmp_dir'] = $this->_get_temp_dir();

        if ( ! is_writable($package_info['tmp_dir'])) {
            $this->session->data['error'] = $this->language->get('error_dir_permission').' '.$package_info['tmp_dir'];
            unset($this->session->data['package_info']);
            abc_redirect($this->_get_begin_href());
        }
        //do condition for MP
        $this->loadModel('tool/mp_api');

        if ($extension_key) {
            // if prefix for new mp presents
            if (substr($extension_key, 0, 4) == 'acmp') {
                //need to mp token to get download based on key.
                $mp_token = $this->config->get('mp_token');
                if ( ! $mp_token) {
                    $this->session->data['error'] = sprintf($this->language->get('error_notconnected'), $this->html->getSecureURL('extension/extensions_store'));
                    abc_redirect($this->_get_begin_href());
                }
                $url = $this->model_tool_mp_api->getMPURL().'?rt=r/account/download/getdownloadbykey';

                // for upgrades of core
            } else {
                $url = "/?option=com_abantecartrepository&format=raw";
            }
            $url .= "&mp_token=".$mp_token;
            $url .= "&store_id=".ABC::env('UNIQUE_ID');
            $url .= "&store_url=".ABC::env('HTTP_SERVER');
            $url .= "&store_version=".ABC::env('VERSION');
            $url .= "&extension_key=".$extension_key;
        } else {
            $url = $package_info['package_url'];
        }

        $headers = $pmanager->getRemoteFileHeaders($url);
        if ( ! $headers) {
            $error_text = $pmanager->error;
            $error_text = empty($error_text) ? 'Unknown error happened.' : $error_text;
            $this->session->data['error'] = $this->language->get('error_mp')." ".$error_text;
            abc_redirect($this->_get_begin_href());
        }
        //if we have json returned, something went wrong.
        if (preg_match("/application\/json/", $headers['Content-Type'])) {
            $error = $pmanager->getRemoteFile($url, false);
            $error_text = $error['error'];
            $error_text = empty($error_text) ? 'Unknown error happened.' : $error_text;
            $this->session->data['error'] = $this->language->get('error_mp')." ".$error_text;
            abc_redirect($this->_get_begin_href());
        } else {
            $package_name = str_replace("attachment; filename=", "", $headers['Content-Disposition']);
            $package_name = str_replace(array('"', ';'), '', $package_name);
            if ( ! $package_name) {
                $package_name = parse_url($url);
                if (pathinfo($package_name['path'], PATHINFO_EXTENSION)) {
                    $package_name = pathinfo($package_name['path'], PATHINFO_BASENAME);
                } else {
                    $package_name = '';
                }
            }

            if ( ! $package_name) {
                $this->session->data['error'] = $this->language->get('error_repository');
                abc_redirect($this->_get_begin_href());
            }
        }

        $package_info['package_url'] = $url;
        $package_info['package_name'] = $package_name;
        $package_info['package_size'] = $headers['Content-Length'];

        // if file already downloaded - check size.
        if (file_exists($package_info['tmp_dir'].$package_name)) {
            $filesize = filesize($package_info['tmp_dir'].$package_name);
            if ($filesize != $package_info['package_size']) {
                @unlink($package_info['tmp_dir'].$package_name);
            } else {
                if ($this->request->get['agree'] == '1') {
                    abc_redirect($this->html->getSecureURL('tool/package_installer/agreement'));
                } else {
                    $already_downloaded = true;
                    abc_redirect($this->html->getSecureURL('tool/package_installer/agreement'));
                }
            }
        }

        $this->data['url'] = $this->html->getSecureURL('tool/package_download');
        $this->data['redirect'] = $this->html->getSecureURL('tool/package_installer/agreement');

        $this->document->initBreadcrumb(array(
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ));
        $this->document->addBreadcrumb(array(
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ));

        $this->data['heading_title'] = $this->language->get('heading_title_download');

        $this->data['loading'] = sprintf($this->language->get('text_loading'), (round($package_info['package_size'] / 1024, 1)).'kb');

        $package_info['install_mode'] = ! $package_info['install_mode'] ? 'install' : $package_info['install_mode'];

        if ( ! $already_downloaded) {
            $this->data['pack_info'] .= sprintf( $this->language->get('text_preloading'), $package_name.' ('.(round($package_info['package_size'] / 1024, 1)).'kb)' );
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer_download.tpl');
    }

    public function agreement()
    {

        $package_info = &$this->session->data['package_info'];

        // if we got decision
        if ($this->request->is_POST()) {
            // if does not agree  with agreement of filesize
            if ($this->request->post['disagree'] == 1) {
                $this->_removeTempFiles();
                unset($this->session->data['package_info']);
                abc_redirect($this->html->getSecureURL('extension/extensions/extensions'));
            } elseif ($this->request->post['agree_incompatibility']) {
                $package_info['confirm_version_incompatibility'] = true;
                abc_redirect($this->html->getSecureURL('tool/package_installer/agreement'));
            } // if agree
            elseif ($this->request->post['agree']) {
                abc_redirect($this->html->getSecureURL('tool/package_installer/install'));
            } elseif ( ! $this->request->post['agree'] && ! isset($this->request->post['ftp_user'])) {
                $this->_removeTempFiles('dir');
                abc_redirect($this->_get_begin_href());
            }
        }

        $this->loadLanguage('tool/package_installer');
        $package_name = $package_info['package_name'];
        if ( ! $package_name) { // if direct link - redirect to the beginning
            abc_redirect($this->_get_begin_href());
        }

        $pmanager = new APackageManager( $this->session->data['package_info'] );
        //unpack package

        // if package not unpacked - redirect to the begin and show error message
        if ( ! is_dir($package_info['tmp_dir'].$package_info['extension_key'])) {
            mkdir($package_info['tmp_dir'].$package_info['extension_key'], 0777);
        }
        if ( ! $pmanager->unpack($package_info['tmp_dir'].$package_name, $package_info['tmp_dir'].$package_info['extension_key'].'/')) {
            $this->session->data['error'] = sprintf($this->language->get('error_unpack'), $package_info['tmp_dir'].$package_name);
            $error = new AError ($pmanager->error);
            $error->toLog()->toDebug();
            abc_redirect($this->_get_begin_href());
        }

        if ( ! $package_info['package_dir'] || !is_dir($package_info['package_dir'])) {
            $error = 'Error: Cannot to find package directory after unpacking archive. ';
            $error = new AError ($error);
            $error->toLog()->toDebug();
            $this->session->data['error'] = $this->html->convertLinks(
                                                sprintf(
                                                        $this->language->get('error_pack_file_not_found'),
                                                        $package_info['package_dir']
                                                )
            );
            abc_redirect($this->_get_begin_href());
        }

        // so.. we need to know about install mode of this package
        $result = $pmanager->extractPackageInfo();
        if ( ! $result) {
            $this->session->data['error'] = $this->html->convertLinks($this->language->get('error_package_config_xml'));
            $this->_removeTempFiles();
            abc_redirect($this->_get_begin_href());
        }

        if ( ! $package_info['package_content']
            || ($package_info['package_content']['core'] && $package_info['package_content']['extensions'])
        ) {
            $this->session->data['error'] = $this->language->get('error_package_structure');
            $this->_removeTempFiles();
            abc_redirect($this->_get_begin_href());
        }

        //check cart version compatibility
        if ( ! isset($package_info['confirm_version_incompatibility'])) {
            if ( !$pmanager->checkCartVersion()) {
                if ($pmanager->isCorePackage()) {
                    $this->session->data['error'] = $this->language->get('error_package_version_compatibility');
                    abc_redirect($this->html->getSecureURL('tool/package_installer'));
                } else {
                    abc_redirect($this->html->getSecureURL('tool/package_installer/agreement'));
                }
            }
        }

        // if we were redirected
        if ($this->request->is_GET()) {
            //check  write permissions
            // find directory from app_root_dir
            $non_writables = array();
            if ($package_info['package_content']['extensions']) {
                $dst_dirs = $pmanager->getDestinationDirectories();
                $ftp_mode = false;
                // if even one destination directory is not writable - use ftp mode
                if ( ! is_writable(ABC::env('DIR_APP_EXTENSIONS'))) {
                    $non_writables[] = ABC::env('DIR_APP_EXTENSIONS');
                }

                if ($dst_dirs) {
                    foreach ($dst_dirs as $dir) {
                        if ( ! is_writable(ABC::env('DIR_ROOT').'/'.$dir) && file_exists(ABC::env('DIR_ROOT').'/'.$dir)) {
                            // enable ftp-mode
                            $ftp_mode = true;
                            $non_writables[] = ABC::env('DIR_ROOT').'/'.$dir;
                        }
                    }
                }
            } else {
                foreach ($package_info['package_content']['core'] as $corefile) {
                    $corefile_dir = pathinfo(ABC::env('DIR_ROOT').'/'.$corefile, PATHINFO_DIRNAME);
                    if (( ! is_writable(ABC::env('DIR_ROOT').'/'.$corefile) && file_exists(ABC::env('DIR_ROOT').'/'.$corefile))) {
                        // enable ftp-mode
                        $ftp_mode = true;
                        $non_writables[] = ABC::env('DIR_ROOT').'/'.$corefile;
                    } else {
                        if ( ! is_writable($corefile_dir) && is_dir($corefile_dir)) {
                            // enable ftp-mode
                            $ftp_mode = true;
                            $non_writables[] = $corefile_dir;
                        } else {
                            if ( ! is_writable($corefile_dir) && ! is_dir($corefile_dir)) {
                                //detect non-writable parent directory
                                $dir_part = explode('/', $corefile_dir);
                                $d = '';
                                while ( ! is_dir($d)) {
                                    array_pop($dir_part);
                                    $d = implode('/', $dir_part);
                                    if (is_dir($d) && ! is_writable($d)) {
                                        // enable ftp-mode
                                        $ftp_mode = true;
                                        $non_writables[] = $d;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $non_writables = array_unique($non_writables);
        }

        // if ftp mode and user give ftp parameters
        if (isset($this->request->post['ftp_user']) && $this->request->is_POST()) {
            $ftp_user = $this->request->post['ftp_user'];
            $ftp_password = $this->request->post['ftp_password'];
            $ftp_host = $this->request->post['ftp_host'];

            //let's try to connect
            if ( ! $pmanager->checkFTP($ftp_user, $ftp_password, $ftp_host)) {
                $this->session->data['error'] = $pmanager->error;
                abc_redirect($this->html->getSecureURL('tool/package_installer/agreement'));
            }
            // sign of ftp-form
            $ftp_mode = false;
            abc_redirect($this->html->getSecureURL('tool/package_installer/install'));
        } else {
            if ( ! $package_info['tmp_dir']) {
                $package_info['tmp_dir'] = $this->_get_temp_dir();
            }
        }
        // if all fine show license agreement
        if ( ! file_exists($package_info['package_dir']."/license.txt") && ! $ftp_mode) {
            abc_redirect($this->html->getSecureURL('tool/package_installer/install'));
        }

        $this->data['license_text'] = '';

        $this->document->initBreadcrumb(array(
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ));
        $this->document->addBreadcrumb(array(
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ));

        if (isset($this->session->data['error'])) {
            $this->data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        $form = new AForm('ST');
        $form->setForm(array('form_name' => 'Frm'));
        $this->data['form']['form_open'] = $form->getFieldHtml(array(
            'type'   => 'form',
            'name'   => 'Frm',
            'action' => $this->html->getSecureURL('tool/package_installer/agreement'),
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
        ));

        //version incompatibility confirmation
        if ((isset($package_info['confirm_version_incompatibility']) && ! $package_info['confirm_version_incompatibility'])) {

            $this->data['agreement_text'] = $package_info['version_incompatibility_text'];

            $this->data['form']['disagree_button'] = $form->getFieldHtml(array(
                'type' => 'button',
                'href' => $this->_get_begin_href(),
                'text' => $this->language->get('text_interrupt'),
            ));

            $this->data['form']['submit'] = $form->getFieldHtml(array(
                'type' => 'button',
                'text' => $this->language->get('text_continue'),
            ));

            $this->data['form']['agree'] = $form->getFieldHtml(array(
                'type'  => 'hidden',
                'name'  => 'agree_incompatibility',
                'value' => '0',
            ));
            $template = 'pages/tool/package_installer_agreement.tpl';

        } // confirmation for ftp access to file system
        elseif ($ftp_mode) {
            $template = 'pages/tool/package_installer_ftp_form.tpl';
            $ftp_user = $package_info['ftp_user'] ? $package_info['ftp_user'] : '';
            $ftp_host = $package_info['ftp_host'] ? $package_info['ftp_host'] : '';

            $this->data['form']['fuser'] = $form->getFieldHtml(array(
                'type'     => 'input',
                'name'     => 'ftp_user',
                'value'    => $ftp_user,
                'require'  => true,
                'help_url' => $this->gen_help_url('ftp_user'),
                'style'    => 'medium-field',
            ));

            $this->data['form']['fpass'] = $form->getFieldHtml(array(
                'type'    => 'password',
                'name'    => 'ftp_password',
                'require' => true,
                'value'   => '',
                'style'   => 'medium-field',
            ));

            $this->data['form']['fhost'] = $form->getFieldHtml(array(
                'type'     => 'input',
                'name'     => 'ftp_host',
                'value'    => $ftp_host,
                'help_url' => $this->gen_help_url('ftp_host'),
                'style'    => 'medium-field',
            ));

            $this->data['form']['submit'] = $form->getFieldHtml(array(
                'type' => 'button',
                'text' => $this->language->get('text_continue'),
            ));

            $this->data['fuser'] = $this->language->get('text_ftp_user');
            $this->data['fpass'] = $this->language->get('text_ftp_password');
            $this->data['fhost'] = $this->language->get('text_ftp_host');
            $this->data['heading_title'] = $this->language->get('heading_title_ftp');
            $this->data['warning_ftp'] = $this->language->get('warning_ftp');
            $this->data['warning_ftp_details'] = 'Need write permission for:<br><ul><li>'.implode('</li><li>', $non_writables)."</li></ul>";

        } // license agreement
        else {
            $license_filepath = $package_info['package_dir']."/license.txt";
            $agreement_text = '';
            if (file_exists($license_filepath)) {
                $agreement_text = file_get_contents($license_filepath);
                //detect encoding of file
                $is_utf8 = mb_detect_encoding($agreement_text, ABC::env('APP_CHARSET'), true);
                if ( ! $is_utf8) {
                    $agreement_text = 'Oops. Something goes wrong. Try to continue or check error log for details.';
                    $err = new AError('Incorrect character set encoding of file '.$license_filepath.' has been detected.');
                    $err->toLog();
                }
            }
            $agreement_text = htmlentities($agreement_text, ENT_QUOTES, ABC::env('APP_CHARSET'));
            $this->data['agreement_text'] = nl2br($agreement_text);

            $template = 'pages/tool/package_installer_agreement.tpl';

            $this->data['form']['agree'] = $form->getFieldHtml(array(
                'type'  => 'hidden',
                'name'  => 'agree',
                'value' => '0',
            ));

            $this->data['text_agree'] = $this->language->get('text_i_agree');
            $this->data['form']['disagree_button'] = $form->getFieldHtml(array(
                'type' => 'button',
                'href' => $this->_get_begin_href(),
                'text' => $this->language->get('text_disagree'),
            ));

            $this->data['heading_title'] = $this->language->get('heading_title_license');
            $this->data['form']['submit'] = $form->getFieldHtml(array(
                'type' => 'button',
                'text' => $this->language->get('text_agree'),
            ));
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate($template);
    }

    public function install()
    {
        $this->loadLanguage('tool/package_installer');
        $package_info = &$this->session->data['package_info'];
        $package_id = $package_info['package_id'];
        $package_dirname = $package_info['package_dir'];
        $temp_dirname = $package_info['tmp_dir'];
        $extension_id = '';
        $upgrade_confirmed = null;

        if ($this->request->is_POST() && $this->request->post['disagree'] == 1) {
            //if user disagree clean up and exit
            $this->_removeTempFiles();
            unset($this->session->data['package_info']);
            abc_redirect($this->html->getSecureURL('extension/extensions/extensions'));
        }

        if ( ! $package_id || ! file_exists($temp_dirname.$package_dirname."/code")) {
            // if error
            $this->session->data['error'] = $this->language->get('error_package_structure');
            $this->_removeTempFiles();
            abc_redirect($this->_get_begin_href());
        }

        if ($this->request->is_POST()) {
            $upgrade_confirmed = $this->request->post['agree'] == 2 ? true : false;
            $license_agree = $this->request->post['agree'] == 1 ? true : false;
            unset($this->request->post['agree']);
        }

        //check for previous version of package and create backup for it
        if ($package_info['package_content']['extensions']) {
            //process for multi-package
            foreach ($package_info['package_content']['extensions'] as $k => $ext) {
                $result = $this->_installExtension($ext, $upgrade_confirmed, $license_agree);
                unset($license_agree);
                if ($result !== true) {
                    if (isset($result['license'])) {
                        $this->data['agreement_text'] = file_get_contents($temp_dirname.$package_dirname."/code/extensions/".$ext."/license.txt");
                        $this->data['agreement_text'] = htmlentities($this->data['agreement_text'], ENT_QUOTES, ABC::env('APP_CHARSET'));
                        $this->data['agreement_text'] = nl2br($this->data['agreement_text']);
                    } else {
                        $this->data['agreement_text'] = '<h2>Extension "'.$ext.'" will be upgrade from version '.$result['upgrade'].'</h2>';
                    }
                    break;
                } else {
                    unset($package_info['package_content']['extensions'][$k]);
                }
                $extension_id = $ext;
            }
            $this->data['heading_title'] = $this->language->get('heading_title_license').'. Extension: '.$ext;
        }

        // for cart upgrade
        if ($package_info['package_content']['core']) {

            if ($upgrade_confirmed) {
                $result = $this->_upgradeCore();
                if ($result === false) {
                    $this->_removeTempFiles();
                    unset($this->session->data['package_info']);
                    abc_redirect($this->_get_begin_href());
                }
            } else {
                $this->data['heading_title'] = $this->language->get('text_core_upgrade_title');
                $this->data['attention_text'] = sprintf($this->language->get('text_core_upgrade_attention'), $package_info['package_version'])."\n\n\n\n";
                $this->data['warning_text'] = $this->language->get('text_core_upgrade_warning');

                $release_notes = $temp_dirname.$package_dirname."/release_notes.txt";
                if (file_exists($release_notes)) {
                    $this->data['agreement_text'] .= file_get_contents($release_notes);
                }
                $this->data['agreement_text'] = htmlentities($this->data['agreement_text'], ENT_QUOTES, ABC::env('APP_CHARSET'));
                $this->data['agreement_text'] = nl2br($this->data['agreement_text']);
            }
        }

        // if all  was installed
        if ($result === true) {
            // clean and redirect after install
            $this->_removeTempFiles();
            $this->cache->remove('*');
            unset($this->session->data['package_info']);
            $this->session->data['success'] = $this->language->get('text_success');
            if ($extension_id) {
                abc_redirect($this->html->getSecureURL('extension/extensions/edit', '&extension='.$extension_id));
            } else {
                abc_redirect($this->html->getSecureURL('tool/install_upgrade_history'));
            }
        }

        $form = new AForm('ST');
        $form->setForm(array('form_name' => 'Frm'));
        $this->data['form']['form_open'] = $form->getFieldHtml(array(
            'type'   => 'form',
            'name'   => 'Frm',
            'action' => $this->html->getSecureURL('tool/package_installer/install'),
        ));
        if (isset($result['license'])) {
            $this->data['form']['hidden'] = $form->getFieldHtml(array(
                'id'    => 'agree',
                'type'  => 'hidden',
                'name'  => 'agree',
                'value' => 1,
            ));
        } else {
            $this->data['form']['hidden'] = $form->getFieldHtml(array(
                'id'    => 'agree',
                'type'  => 'hidden',
                'name'  => 'agree',
                'value' => 2,
            ));
        }

        $this->data['form']['disagree_button'] = $form->getFieldHtml(array(
            'type' => 'button',
            'href' => $this->_get_begin_href(),
            'text' => $this->language->get('text_disagree'),
        ));

        $this->data['form']['submit'] = $form->getFieldHtml(array(
            'type' => 'button',
            'text' => $this->language->get('text_agree'),
        ));

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer_agreement.tpl');
    }

    /**
     * Method of extension installation from package
     *
     * @param string $extension_id
     * @param bool   $confirmed
     * @param int    $agree
     *
     * @return array|bool
     */
    protected function _installExtension($extension_id = '', $confirmed = false, $agree = 0)
    {
        $package_info = &$this->session->data['package_info'];
        $package_dirname = $package_info['package_dir'];
        $temp_dirname = $package_info['tmp_dir'];
        /**
         * @var  \DOMDocument $config
         */
        $config = simplexml_load_string(file_get_contents($temp_dirname.$package_dirname."/code/extensions/".$extension_id.'/config.xml'));

        $version = (string)$config->version;
        $type = (string)$config->type;
        $type = ! $type && $package_info['package_type'] ? $package_info['package_type'] : $type;
        $type = ! $type ? 'extension' : $type;

        // #1. check installed version
        $all_installed = $this->extensions->getInstalled('exts');
        $already_installed = false;
        if (in_array($extension_id, $all_installed)) {
            $already_installed = true;
            $installed_info = $this->extensions->getExtensionInfo($extension_id);
            $installed_version = $installed_info['version'];

            if (AHelperUtils::versionCompare($version, $installed_version, '<=')) {
                // if installed version the same or higher - do nothing
                return true;
            } else {
                if ( ! $confirmed && ! $agree) {
                    return array('upgrade' => $installed_version.' >> '.$version);
                }
            }
        }

        $pmanager = new APackageManager( $this->session->data['package_info'] );
        // #2. backup previous version
        if ($already_installed || file_exists(ABC::env('DIR_APP_EXTENSIONS').$extension_id)) {
            if ( ! is_writable(ABC::env('DIR_APP_EXTENSIONS').$extension_id)) {
                $this->session->data['error'] = $this->language->get('error_move_backup').ABC::env('DIR_APP_EXTENSIONS').$extension_id;
                abc_redirect($this->_get_begin_href());
            } else {
                if ( ! $pmanager->backupPreviousExtension($extension_id)) {
                    $this->session->data['error'] = $pmanager->error;
                    abc_redirect($this->_get_begin_href());
                }
            }

        }

        // #3. if all fine - copy extension package files
        // if ftp-access
        if ($package_info['ftp']) {
            $ftp_user = $this->session->data['package_info']['ftp_user'];
            $ftp_password = $this->session->data['package_info']['ftp_password'];
            $ftp_port = $this->session->data['package_info']['ftp_port'];
            $ftp_host = $this->session->data['package_info']['ftp_host'];

            $fconnect = ftp_connect($ftp_host, $ftp_port);
            ftp_login($fconnect, $ftp_user, $ftp_password);
            ftp_pasv($fconnect, true);
            $result = $pmanager->ftp_move($fconnect, $package_dirname."/code/abc/extensions/".$extension_id,
                $extension_id,
                $package_info['ftp_path'].'extensions/'.$extension_id);
            ftp_close($fconnect);
        } else {
            $result = rename($package_dirname."/code/abc/extensions/".$extension_id, ABC::env('DIR_APP_EXTENSIONS').$extension_id);
            //this method requires permission set to be set
            $pmanager->chmod_R(ABC::env('DIR_APP_EXTENSIONS').$extension_id, 0777, 0777);
        }

        /*
         * When extension installed by one-path process (ex.: on upload)
         * it is not present in database yet,
         * so we have to add it.
         */
        $this->extension_manager->add(array(
            'type'        => (string)$config->type,
            'key'         => (string)$config->id,
            'status'      => 0,
            'priority'    => (string)$config->priority,
            'version'     => (string)$config->version,
            'license_key' => $this->registry->get('session')->data['package_info']['extension_key'],
            'category'    => (string)$config->category,
        ));

        // #4. if copied successfully - install(upgrade)
        if ($result) {
            $install_mode = $already_installed ? 'upgrade' : 'install';
            if ( ! $pmanager->installExtension($extension_id, $type, $version, $install_mode)) {
                $this->session->data['error'] .= $this->language->get('error_install').'<br><br>'.$pmanager->error;
                $this->_removeTempFiles('dir');
                abc_redirect($this->_get_begin_href());
            }
        } else {
            if ($package_info['ftp']) {
                $this->session->data['error'] = $this->language->get('error_move_ftp').ABC::env('DIR_APP_EXTENSIONS').$extension_id.'<br><br>'.$pmanager->error;
                abc_redirect($this->html->getSecureURL('tool/package_installer/agreement'));
            } else {
                $this->session->data['error'] = $this->language->get('error_move').ABC::env('DIR_APP_EXTENSIONS').$extension_id.'<br><br>'.$pmanager->error;
                $this->_removeTempFiles('dir');
                abc_redirect($this->_get_begin_href());
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function _upgradeCore()
    {
        $package_info = &$this->session->data['package_info'];
        if (AHelperUtils::versionCompare(ABC::env('VERSION'), $package_info['package_version'], ">=")) {

            $this->session->data['error'] = sprintf( $this->language->get('error_core_version'), ABC::env('VERSION')).$package_info['package_version'].'!';
            unset($this->session->data['package_info']);
            abc_redirect($this->_get_begin_href());
        }

        //$core_files = $package_info['package_content']['core'];
        $pmanager = new APackageManager( $this->session->data['package_info'] );
        /*
         * Commented by decision do not use it in community version.
         * Pasted alerts before upgrade process start.
         * Do not remove code yet.
         *
        //backup files
        $backup = new ABackup('abantecart_' . str_replace('.','',ABC::env('VERSION')));
        //interrupt if backup directory is inaccessible
        if ($backup->error) {
            $this->session->data['error'] = implode("\n", $backup->error);
            return false;
        }
        foreach ($corefiles as $core_file) {
            if (file_exists(DIR_ROOT . '/' . $core_file)) {
                if (!$backup->backupFile(DIR_ROOT . '/' . $core_file, false)) {
                    return false;
                }
            }
        }
        // backup database
        if ($backup->dumpDatabase()) {
            $backup_dirname = $backup->getBackupName();
            if ($backup_dirname) {
                if (!$backup->dumpDatabase()) {
                    $this->session->data['error'] = implode("\n", $backup->error);
                    return false;
                }
                if (!$backup->archive(ABC::env('DIR_BACKUP') . $backup_dirname . '.tar.gz', ABC::env('DIR_BACKUP'), $backup_dirname)) {
                    $this->session->data['error'] = implode("\n", $backup->error);
                    return false;
                }
            } else {
                $this->session->data['error'] = 'Error: Unknown directory name for backup.';
                return false;
            }

            $install_upgrade_history = new ADataset('install_upgrade_history', 'admin');
            $install_upgrade_history->addRows(
                    array(
                            'date_added' => date("Y-m-d H:i:s", time()),
                            'name' => 'Backup before core upgrade. Core version: ' . ABC::env('VERSION'),
                            'version' => ABC::env('VERSION'),
                            'backup_file' => $backup_dirname . '.tar.gz',
                            'backup_date' => date("Y-m-d H:i:s", time()),
                            'type' => 'backup',
                            'user' => $this->user->getUsername() ));
        } else {
            $this->session->data['error'] = implode("\n", $backup->error);
            return false;
        }*/

        //replace files
        $pmanager->replaceCoreFiles();
        //run sql and php upgrade procedure files
        $package_dirname = $package_info['package_dir'];
        if ($pmanager->error) {
            $this->session->data['error'] = $pmanager->error;
        }
        /**
         * @var \SimpleXMLElement|\DOMDocument $config
         */
        $config = simplexml_load_string(file_get_contents($package_dirname.'/package.xml'));
        if ( ! $config) {
            $this->session->data['error'] = 'Error: package.xml from package content is not valid xml-file!';
            unset($this->session->data['package_info']);
            abc_redirect($this->_get_begin_href());
        }
        $pmanager->upgradeCore($config);
        $pmanager->updateCoreVersion((string)$config->version);
        if ($pmanager->error) {
            $this->session->data['error'] .= "\n".$pmanager->error;
        }

        return true;
    }

    protected function _find_package_dir()
    {
        $dirs = glob($this->session->data['package_info']['tmp_dir'].$this->session->data['package_info']['extension_key'].'/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            if (file_exists($dir.'/package.xml')) {
                return str_replace($this->session->data['package_info']['tmp_dir'], '', $dir);
            }
        }
        //try to find package.xml in root of package
        if (is_file($this->session->data['package_info']['tmp_dir'].$this->session->data['package_info']['extension_key'].'/package.xml')) {
            return $this->session->data['package_info']['extension_key'];
        }

        return null;
    }

    protected function _removeTempFiles($target = 'both')
    {
        $package_info = &$this->session->data['package_info'];
        if ( ! in_array($target, array('both', 'pack', 'dir'))
            || ! $package_info['package_dir']
        ) {
            return false;
        }

        //set ftp to false. it's not needed for temp files clean, because all files was created by apache user
        $this->session->data['package_info']['ftp'] = false;
        $pmanager = new APackageManager( $this->session->data['package_info'] );
        switch ($target) {
            case 'both':
                $result = $pmanager->removeDir($package_info['package_dir']);
                @unlink($package_info['tmp_dir'].$package_info['package_name']);
                break;
            case 'pack':
                $result = @unlink($package_info['tmp_dir'].$package_info['package_name']);
                break;
            case 'dir':
                $result = $pmanager->removeDir($package_info['package_dir']);
                break;
            default:
                $result = null;
                break;
        }
        if ( ! $result) {
            $this->session->data['error'] = $pmanager->error;

            return false;
        }

        return true;
    }

    protected function _get_temp_dir()
    {
        $pmanager = new APackageManager( $this->session->data['package_info'] );
        return $pmanager->getTempDir();
    }

    protected function _get_begin_href()
    {
        return $this->html->getSecureURL('tool/package_installer'.($this->session->data['package_info']['package_source'] == 'file' ? '/upload' : ''));
    }

    // this method calls before installation of package
    protected function _clean_temp_dir()
    {
            $pmanager = new APackageManager( $this->session->data['package_info'] );
            $pmanager->CleanTempDir();
    }
}
