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

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\AError;
use abc\core\lib\AExtensionManager;
use abc\core\lib\APackageManager;
use H;


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
        $package_info = $this->session->data['package_info'];
        $extension_key = !$this->request->get['extension_key']
            ? ''
            : trim($this->request->get['extension_key']);

        $extension_key = !$this->request->post['extension_key']
            ? $extension_key
            : trim($this->request->post['extension_key']);

        $extension_key = $package_info['extension_key'] ? $package_info['extension_key'] : $extension_key;

        $this->session->data['package_info'] = [];
        //clean temporary directory
        $this->cleanTempDir();

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $form = new AForm('ST');
        $form->setForm(
            ['form_name' => 'installFrm']
        );
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'installFrm',
                'action' => $this->html->getSecureURL('tool/package_installer/download'),
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            ]);

        $this->data['form']['fields']['input'] = $form->getFieldHtml(
            [
                'type'        => 'input',
                'name'        => 'extension_key',
                'value'       => $extension_key,
                'attr'        => 'autocomplete="off" ',
                'placeholder' => $this->language->get('text_key_hint'),
                'help_url'    => $this->gen_help_url('extension_key'),
            ]);

        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('text_continue'),
            ]);

        if (isset($this->session->data['error'])) {
            $error_txt = $this->session->data['error'];
            $error_txt .= '<br>'.$this->language->get('error_additional_help_text');
            $this->data['error_warning'] = $error_txt;
            unset($package_info['package_dir'], $this->session->data['error'], $error_txt);
        }
        unset($this->session->data['error'], $this->session->data['success']);

        $package_info['package_source'] = 'network';
        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->initTabs('key');

        $this->view->assign('help_url', $this->gen_help_url(''));
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer.tpl');
    }

    // method for uploading package via form
    public function upload()
    {
        //clean temporary directory
        $this->cleanTempDir();

        $this->session->data['package_info'] = [];
        $package_info = $this->session->data['package_info'];
        $package_info['package_source'] = 'file';
        $package_info['tmp_dir'] = $this->getTempDir();

        // process post
        if ($this->request->is_POST()) {
            $tmp_filename = $this->request->files['package_file']['tmp_name'];
            $real_file_name = $this->request->files['package_file']['name'];
            if (is_uploaded_file($tmp_filename)) {
                if (!is_int(strpos($real_file_name, '.tar.gz'))
                    && strtolower(pathinfo($real_file_name, PATHINFO_EXTENSION)) != 'zip') {
                    unlink($tmp_filename);
                    $this->session->data['error'] .= $this->language->get('error_archive_extension');
                } else {
                    $result = move_uploaded_file($tmp_filename, $package_info['tmp_dir'].$real_file_name);
                    if (!$result || $this->request->files['package_file']['error']) {
                        $this->session->data['error'] .= '<br>Error: '
                            .H::getTextUploadError($this->request->files['package_file']['error']);
                    } else {
                        $package_info['package_name'] = $real_file_name;
                        $package_info['package_size'] = $this->request->files['package_file']['size'];
                        $this->session->data['package_info'] = $package_info;
                        abc_redirect($this->html->getSecureURL('tool/package_installer/confirm'));
                    }
                }
            } else {
                if ($this->request->post['package_url']) {
                    $this->session->data['package_info']['package_url'] = $this->request->post['package_url'];
                    abc_redirect($this->html->getSecureURL('tool/package_installer/download'));
                } else {
                    $this->session->data['error'] .= '<br>Error: '
                        .H::getTextUploadError($this->request->files['package_file']['error']);
                }
            }
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $form = new AForm('ST');
        $form->setForm(
            ['form_name' => 'uploadFrm']
        );
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'uploadFrm',
                'action' => $this->html->getSecureURL('tool/package_installer/upload'),
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            ]);

        $this->data['form']['fields']['upload_file'] = $form->getFieldHtml(
            [
                'type'  => 'file',
                'name'  => 'package_file',
                'value' => '',
                'attr'  => ' autocomplete="off" ',
            ]);

        $this->data['form']['fields']['upload_url'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'package_url',
                'value' => '',
                'attr'  => ' autocomplete="off" ',
            ]);

        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('text_continue'),
            ]);

        if (isset($this->session->data['error'])) {
            $error_txt = $this->session->data['error'];
            $error_txt .= '<br>'.$this->language->get('error_additional_help_text');
            $this->data['error_warning'] = $error_txt;
            unset($package_info['package_dir'], $error_txt);
        }
        unset($this->session->data['error']);

        $this->data['heading_title'] = $this->language->get('heading_title');
        $this->data['text_license_agreement'] = $this->language->get('text_license_agreement');

        $this->initTabs('upload');

        $this->data['upload'] = true;
        $this->data['text_or'] = $this->language->get('text_or');
        $this->view->assign('help_url', $this->gen_help_url(''));

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer.tpl');
    }

    protected function preCheck()
    {
        $check_results = [];
        $package_info = $this->session->data['package_info'];
        //if package not extracted yet - skip
        if (!$package_info['package_dir']) {
            return false;
        }

        $pm = new APackageManager($package_info);
        $pm->extractPackageInfo();
        $is_core_package = $pm->isCorePackage();
        // 1. check warnings for package such as version incompatibility etc
        if (!$pm->checkCartVersion()) {
            if ($is_core_package) {
                $error_text = "Error: Can't install package. Your cart version is ".ABC::env('VERSION').". ";
                $error_text .= "Version(s) ".implode(', ', $pm->package_info['supported_cart_versions'])."  required.";
                $check_results['critical']['common'][] = $error_text;
            } else {
                $check_results['warnings']['common'][] = "Current copy of this package is not verified for your version"
                    ." of AbanteCart (v".ABC::env('VERSION').").<br>"
                    ."Package build is specified for AbanteCart version(s) "
                    .implode(', ', $pm->package_info['supported_cart_versions'])."<br>"
                    ."This is not a problem, but if you notice issues or incompatibility,"
                    ." please contact extension developer.<br>";
            }
        }
        //2. check destinations and ask about backgroundJob creation for process
        $result = $pm->validateDestination();

        if (!$result) {
            $check_results['warnings']['common'][] = "Permission denied for files(directories):<br>"
                .implode("<br>", $pm->errors);
        }
        //check extensions pack (install/upgrades) and show info what will do
        $all_installed = $this->extensions->getDbExtensions();
        //process for multi-package
        $em = new AExtensionManager();
        foreach ($pm->package_info['package_content']['extensions'] as $ext_txt_id) {
            $config_file = $pm->package_info['package_dir']
                ."code".DS
                ."abc".DS
                ."extensions"
                .DS
                .$ext_txt_id
                .DS
                ."config.xml";
            if (($config = @simplexml_load_file($config_file)) === false) {
                $check_results['warnings']['extensions'][] = "Extension "
                    .$ext_txt_id." does not contain config.xml file and will be skipped.";
                continue;
            }

            $version = (string)$config->version;
            //if already installed
            if (in_array($ext_txt_id, $all_installed)) {
                $installed_info = $this->extensions->getExtensionInfo($ext_txt_id);
                $installed_version = $installed_info['version'];
                if (H::versionCompare($version, $installed_version, '<=')) {
                    // if installed version the same or higher - do nothing
                    $check_results['warnings']['extensions'][] = "Extension ".$ext_txt_id
                        ." will be skipped. Same or higher version(".$installed_version.") already installed.";
                    continue;
                }
                $check_results['messages']['extensions'][] = "Extension ".$ext_txt_id
                    ." will be upgraded from v".$installed_version." up to v".$version;
            } else {
                if (!$em->validateCoreVersion($ext_txt_id, $config)) {
                    // if installed version the same or higher - do nothing
                    $check_results['warnings']['extensions'][] = implode("<br>", $em->errors);
                    continue;
                }
                $check_results['messages']['extensions'][] = "Extension ".$ext_txt_id." will be installed.";
            }
        }

        //in case when all of extensions cannot be installed - mark as warnings as critical
        if (!$is_core_package && !sizeof($check_results['critical'])
            && !sizeof($check_results['messages']) && $check_results['warnings']
        ) {
            $check_results['critical']['extensions'] = $check_results['warnings']['extensions'];
            unset($check_results['warnings']['extensions']);
        }

        if ($is_core_package && !sizeof($check_results['critical'])) {
            $check_results['messages']['core'][] = 'Upgrade Package will be processed.';
        }

        //add message about background Job
        if (!$check_results['critical'] && $check_results['warnings']) {
            if (ABC::getFullClassName('APackageInstallerJob')) {
                $check_results['need_background_job'] = true;
                $check_results['messages'][][] = "Do you want to create background job for this process?";
            } else {
                $check_results['critical'] = $check_results['warnings'];
                $check_results['critical'][][] = 'This install process cannot be run in UI-mode."
                    ." Try to run it in cli-mode with "abcexec" script.'
                    .' See more details <a href="???">here</a>';
                unset($check_results['warnings']);
            }
        } else {
            $check_results['need_background_job'] = false;
        }

        return $check_results;
    }

    protected function initTabs($active = null)
    {
        $this->data['tabs'] = [];
        $this->data['tabs']['key'] = [
            'href' => $this->html->getSecureURL('tool/package_installer'),
            'text' => $this->language->get('text_network_install'),
        ];

        $this->data['tabs']['upload'] = [
            'href' => $this->html->getSecureURL('tool/package_installer/upload'),
            'text' => $this->language->get('text_extension_upload'),
        ];

        if (in_array($active, array_keys($this->data['tabs']))) {
            $this->data['tabs'][$active]['active'] = 1;
        } else {
            $this->data['tabs']['key']['active'] = 1;
        }
    }

    public function download()
    {
        // for short code
        $package_info = $this->session->data['package_info'];
        $extension_key = trim($this->request->post_or_get('extension_key'));
        $disclaimer = false;
        $mp_token = '';
        $package_name = '';
        $already_downloaded = false;

        if (!$extension_key && !$package_info['package_url']) {
            $this->removeTempFiles();
            abc_redirect($this->getBeginHref());
        }

        if ($this->request->is_GET()) {
            //reset installer array after redirects
            $this->removeTempFiles();
            if ($extension_key) {
                //reset array only for requests by key (exclude upload url method)
                $this->session->data['package_info'] = [];
            }
        } // if does not agree  with agreement of filesize
        elseif ($this->request->is_POST()) {
            if ($this->request->post['disagree'] == 1) {
                $this->removeTempFiles();
                unset($this->session->data['package_info']);
                abc_redirect($this->html->getSecureURL('extension/extensions/extensions'));
            } else {
                $disclaimer = (int)$this->request->get['disclaimer'];
                // prevent multiple show for disclaimer
                $this->session->data['installer_disclaimer'] = true;
            }
        }
        $package_info = $this->session->data['package_info'];
        $pm = new APackageManager($package_info);
        if ($pm->isCorePackage($extension_key)) {
            $disclaimer = true;
        }

        if (!$disclaimer && !$this->session->data['installer_disclaimer']) {
            $this->view->assign('heading_title', $this->language->get('text_disclaimer_heading'));

            $form = new AForm('ST');
            $form->setForm(['form_name' => 'Frm']);
            $this->data['form']['form_open'] = $form->getFieldHtml([
                'type'   => 'form',
                'name'   => 'Frm',
                'action' => $this->html->getSecureURL('tool/package_installer/download'),
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            ]);

            $this->data['form']['hidden'][] = $form->getFieldHtml([
                'id'    => 'extension_key',
                'type'  => 'hidden',
                'name'  => 'extension_key',
                'value' => $extension_key,
            ]);

            $this->data['agreement_text'] = $this->language->get('text_disclaimer');

            $this->data['form']['disagree_button'] = $form->getFieldHtml([
                'type' => 'button',
                'href' => $this->getBeginHref(),
                'text' => $this->language->get('text_interrupt'),
            ]);

            $this->data['form']['submit'] = $form->getFieldHtml([
                'type' => 'button',
                'text' => $this->language->get('text_agree'),
            ]);

            $this->data['form']['agree'] = $form->getFieldHtml([
                'type'  => 'hidden',
                'name'  => 'disclaimer',
                'value' => '0',
            ]);

            $this->view->batchAssign($this->data);
            $this->processTemplate('pages/tool/package_installer_confirm.tpl');

            return null;
        }

        $form = new AForm('ST');
        $form->setForm(['form_name' => 'retryFrm']);
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'retryFrm',
            'action' => $this->html->getSecureURL('tool/package_installer/download'),
        ]);

        $this->data['form']['hidden'][] = $form->getFieldHtml([
            'id'    => 'extension_key',
            'type'  => 'hidden',
            'name'  => 'extension_key',
            'value' => $extension_key,
        ]);

        $this->data['form']['hidden'][] = $form->getFieldHtml([
            'id'    => 'disclaimer',
            'type'  => 'hidden',
            'name'  => 'disclaimer',
            'value' => '1',
        ]);

        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type' => 'button',
            'href' => $this->getBeginHref(),
            'text' => $this->language->get('button_cancel'),
        ]);

        $this->data['form']['retry'] = $form->getFieldHtml([
            'type' => 'button',
            'text' => $this->language->get('text_retry'),
        ]);

        $this->view->assign('text_download_error', $this->language->get('text_download_error'));

        $package_info['extension_key'] = $extension_key;

        $package_info['tmp_dir'] = $this->getTempDir();

        if (!is_writable($package_info['tmp_dir'])) {
            $this->session->data['error'] = $this->language->get('error_dir_permission').' '.$package_info['tmp_dir'];
            unset($this->session->data['package_info']);
            abc_redirect($this->getBeginHref());
        }
        //do condition for MP
        $this->loadModel('tool/mp_api');

        if ($extension_key) {
            // if prefix for new mp presents
            if (substr($extension_key, 0, 4) == 'acmp') {
                //need to mp token to get download based on key.
                $mp_token = $this->config->get('mp_token');
                if (!$mp_token) {
                    $this->session->data['error'] = sprintf(
                        $this->language->get('error_notconnected'),
                        $this->html->getSecureURL('extension/extensions_store')
                    );
                    abc_redirect($this->getBeginHref());
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

        $headers = $pm->getRemoteFileHeaders($url);
        if (!$headers) {
            $error_text = implode("<br>", $pm->errors);
            $error_text = empty($error_text) ? 'Unknown error happened.' : $error_text;
            $this->session->data['error'] = $this->language->get('error_mp')." ".$error_text;
            abc_redirect($this->getBeginHref());
        }
        //if we have json returned, something went wrong.
        if (preg_match("/application\/json/", $headers['Content-Type'])) {
            $error = $pm->getRemoteFile($url, false);
            $error_text = $error['error'];
            $error_text = empty($error_text) ? 'Unknown error happened.' : $error_text;
            $this->session->data['error'] = $this->language->get('error_mp')." ".$error_text;
            abc_redirect($this->getBeginHref());
        } else {
            $package_name = str_replace("attachment; filename=", "", $headers['Content-Disposition']);
            $package_name = str_replace(['"', ';'], '', $package_name);
            if (!$package_name) {
                $package_name = parse_url($url);
                if (pathinfo($package_name['path'], PATHINFO_EXTENSION)) {
                    $package_name = pathinfo($package_name['path'], PATHINFO_BASENAME);
                } else {
                    $package_name = '';
                }
            }

            if (!$package_name) {
                $this->session->data['error'] = $this->language->get('error_repository');
                abc_redirect($this->getBeginHref());
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
                    abc_redirect($this->html->getSecureURL('tool/package_installer/confirm'));
                } else {
                    $already_downloaded = true;
                    abc_redirect($this->html->getSecureURL('tool/package_installer/confirm'));
                }
            }
        }

        $this->data['url'] = $this->html->getSecureURL('tool/package_download');
        $this->data['redirect'] = $this->html->getSecureURL('tool/package_installer/confirm');

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $this->data['heading_title'] = $this->language->get('heading_title_download');

        $this->data['loading'] = sprintf(
            $this->language->get('text_loading'),
            (round($package_info['package_size'] / 1024, 1)).'kb'
        );

        $package_info['install_mode'] = !$package_info['install_mode'] ? 'install' : $package_info['install_mode'];

        if (!$already_downloaded) {
            $this->data['pack_info'] .= sprintf(
                $this->language->get('text_preloading'),
                $package_name.' ('.(round($package_info['package_size'] / 1024, 1)).'kb)'
            );
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer_download.tpl');
    }

    public function confirm()
    {
        $package_info = $this->session->data['package_info'];
        $pm = new APackageManager($package_info);
        $this->loadLanguage('tool/package_installer');
        $package_name = $package_info['package_name'];
        if (!$package_name) { // if direct link - redirect to the beginning
            abc_redirect($this->getBeginHref());
        }

        //unpack package
        if (!$pm->unpack($package_info['tmp_dir'].$package_name, $package_info['tmp_dir'])) {
            $this->session->data['error'] = sprintf(
                $this->language->get('error_unpack'),
                $package_info['tmp_dir'].$package_name
            );
            abc_redirect($this->getBeginHref());
        }
        $package_info = $pm->package_info;
        if (!$package_info['package_dir'] || !is_dir($package_info['package_dir'])) {
            $error = 'Error: Cannot to find package directory after unpacking archive. ';
            $error = new AError ($error);
            $error->toLog()->toDebug();
            $this->session->data['error'] = $this->html->convertLinks(
                sprintf(
                    $this->language->get('error_pack_file_not_found'),
                    $package_info['package_dir']
                )
            );
            abc_redirect($this->getBeginHref());
        }

        // so.. we need to know about install mode of this package
        $result = $pm->extractPackageInfo();
        $package_info = $pm->package_info;
        if (!$result) {
            $this->session->data['error'] = $this->html->convertLinks($this->language->get('error_package_config_xml'));
            $this->log->write(implode("\n", $pm->errors));
            $this->removeTempFiles();
            abc_redirect($this->getBeginHref());
        }

        if (!$package_info['package_content']
            || ($package_info['package_content']['core'] && $package_info['package_content']['extensions'])
        ) {
            $this->session->data['error'] = $this->language->get('error_package_structure');
            $this->log->write(implode("\n", $pm->errors));
            $this->removeTempFiles();
            abc_redirect($this->getBeginHref());
        }

        $this->session->data['package_info'] = $pm->getPackageInfo();
        //check package before install
        $this->data['check_results'] = $this->preCheck();

        //remove temporary files if some critical errors presents
        if ($this->data['check_results']['critical']) {
            $this->removeTempFiles();
        }

        // if all fine show license confirm or release notes
        if (!isset($this->data['check_results']['critical'])) {
            if (is_file($package_info['package_dir']."/license.txt")) {
                $this->data['license_text'] = file_get_contents($package_info['package_dir']."/license.txt");

                //detect encoding of file
                $is_utf8 = mb_detect_encoding($this->data['license_text'], ABC::env('APP_CHARSET'), true);
                if (!$is_utf8) {
                    $this->data['license_text'] = '';
                    $err = new AError(
                        'Incorrect character set encoding of file '
                        .$package_info['package_dir']."/license.txt".' has been detected.'
                    );
                    $err->toLog();
                }

                $this->data['license_text'] = htmlentities(
                                                        $this->data['license_text'],
                                                        ENT_QUOTES,
                                                        ABC::env('APP_CHARSET')
                );
                $this->data['license_text'] = nl2br($this->data['license_text']);

            }
            if (is_file($package_info['package_dir']."/release_notes.txt")) {
                $this->data['release_notes'] = file_get_contents($package_info['package_dir']."/release_notes.txt");
                $this->data['release_notes'] = htmlentities(
                                                        $this->data['release_notes'],
                                                        ENT_QUOTES,
                                                        ABC::env('APP_CHARSET')
                );
                $this->data['release_notes'] = nl2br($this->data['release_notes']);
            }
        }

        $this->data['heading_title'] = $this->language->get('heading_title_confirmation');
        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('tool/package_installer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->session->data['error'])) {
            $this->data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        $form = new AForm('ST');
        $form->setForm(['form_name' => 'Frm']);
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'Frm',
            'action' => $this->html->getSecureURL('tool/package_installer/install'),
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
        ]);

        if ($this->data['check_results']['need_background_job']) {
            $this->data['form']['nbg'] = $form->getFieldHtml([
                'type'  => 'hidden',
                'name'  => 'need_background_job',
                'value' => '1',
            ]);
        }

        $this->data['text_agree'] = $this->language->get('text_i_agree');
        $this->data['form']['disagree_button'] = $form->getFieldHtml([
            'type' => 'button',
            'href' => $this->getBeginHref(),
            'text' => $this->language->get('text_disagree'),
        ]);

        $this->data['form']['submit'] = $form->getFieldHtml([
            'type' => 'button',
            'text' => $this->language->get('text_agree'),
        ]);

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/tool/package_installer_confirm.tpl');
    }

    public function install()
    {
        $this->loadLanguage('tool/package_installer');
        $package_info = $this->session->data['package_info'];

        $package_dirname = $package_info['package_dir'];
        $upgrade_confirmed = null;

        if (!$package_info || !$package_dirname) {
            abc_redirect($this->html->getSecureURL('extension/extensions/extensions'));
        }

        if (!file_exists($package_dirname."/code")) {
            $this->session->data['error'] = $this->language->get('error_package_structure');
            $this->removeTempFiles();
            abc_redirect($this->getBeginHref());
        }

        $check_results = $this->preCheck();
        if ($check_results['critical']) {
            abc_redirect($this->html->getSecureURL('tool/package_installer/confirm'));
        }
        //if need to create background job
        if ($this->request->post_or_get('nbg')) {
            if (!ABC::getFullClassName('APackageInstallerJob')) {
                $this->session->data['error'] =
                    'Error occurred during creating of background Job. Job handler Not Set.';
            } else {
                //TODO: add job creating here
                $result = H::createJob([]);
                if ($result) {
                    $this->session->data['success'] =
                        'Background Job has been created successfully and will be run soon';
                } else {
                    $this->session->data['error'] =
                        'Error occurred during creating of background Job. See error log for details.';
                }
            }
            abc_redirect($this->html->getSecureURL('tool/package_installer'));
        }

        //if run install process directly
        $pm = new APackageManager($this->session->data['package_info']);
        // for cart upgrade
        if ($pm->isCorePackage()) {
            $result = $pm->upgradeCore();
        } else {
            //process for multi-package
            $result = $pm->installPackageExtensions();
        }

        // if all  was installed
        if ($result === true) {
            // clean and redirect after install
            $this->removeTempFiles();
            $this->cache->flush('*');
            unset($this->session->data['package_info']);
            $this->session->data['success'] = $this->language->get('text_success');

            if ($pm->package_info['installed']) {
                if (sizeof($pm->package_info['installed']) == 1) {
                    $redirect_url = $this->html->getSecureURL(
                        'extension/extensions',
                        '&extension='.$pm->package_info['installed'][0]
                    );
                } else {
                    $redirect_url = $this->html->getSecureURL('extension/extensions');
                }
            } else {
                $redirect_url = $this->html->getSecureURL('tool/install_upgrade_history');
            }
        } else {
            $this->session->data['error'] = implode("<br>", $pm->errors);
            $redirect_url = $this->html->getSecureURL('tool/install_upgrade_history');
        }
        $this->removeTempFiles();
        abc_redirect($redirect_url);
    }

    /**
     * Method of extension installation from package
     *
     * @param string $extension_id
     * @param bool $confirmed
     * @param int $agree
     *
     * @return array|bool
     * @throws \DebugBar\DebugBarException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    protected function installExtension($extension_id = '', $confirmed = false, $agree = 0)
    {
        $package_info = $this->session->data['package_info'];
        $package_dirname = $package_info['package_dir'];
        /**
         * @var  \SimpleXMLElement|\DOMDocument $config
         */
        $config = simplexml_load_file($package_dirname."/code/extensions/".$extension_id.'/config.xml');
        if ($config === false) {
            $this->session->data['error'] =
                'Extension '.$extension_id.' cannot be installed. config.xml file not found.';
            return false;
        }

        $version = (string)$config->version;
        $type = (string)$config->type;
        $type = !$type && $package_info['package_type'] ? $package_info['package_type'] : $type;
        $type = !$type ? 'extension' : $type;

        // #1. check installed version
        $all_installed = $this->extensions->getInstalled('exts');
        $already_installed = false;
        if (in_array($extension_id, $all_installed)) {
            $already_installed = true;
            $installed_info = $this->extensions->getExtensionInfo($extension_id);
            $installed_version = $installed_info['version'];
            if (H::versionCompare($version, $installed_version, '<=')) {
                // if installed version the same or higher - do nothing
                return true;
            } else {
                if (!$confirmed && !$agree) {
                    return ['upgrade' => $installed_version.' >> '.$version];
                }
            }
        }
        $package_info = $this->session->data['package_info'];
        $pm = new APackageManager($package_info);
        // #2. backup previous version
        if ($already_installed || file_exists(ABC::env('DIR_APP_EXTENSIONS').$extension_id)) {
            if (!is_writable(ABC::env('DIR_APP_EXTENSIONS').$extension_id)) {
                $this->session->data['error'] = $this->language->get('error_move_backup').ABC::env('DIR_APP_EXTENSIONS')
                                                .$extension_id;
                abc_redirect($this->getBeginHref());
            } else {
                if (!$pm->backupPreviousExtension($extension_id)) {
                    $this->session->data['error'] = implode("<br>", $pm->errors);
                    abc_redirect($this->getBeginHref());
                }
            }
        }

        // #3. if all fine - copy extension package files
        $result = rename(
            $package_dirname."/code/abc/extensions/".$extension_id,
            ABC::env('DIR_APP_EXTENSIONS').$extension_id
        );
        //this method requires permission set to be set
        $pm->chmod_R(ABC::env('DIR_APP_EXTENSIONS').$extension_id, 0777, 0777);

        /*
         * When extension installed by one-path process (ex.: on upload)
         * it is not present in database yet,
         * so we have to add it.
         */
        $this->extension_manager->add([
            'type'        => (string)$config->type,
            'key'         => (string)$config->id,
            'status'      => 0,
            'priority'    => (string)$config->priority,
            'version'     => (string)$config->version,
            'license_key' => $this->registry->get('session')->data['package_info']['extension_key'],
            'category'    => (string)$config->category,
        ]);

        // #4. if copied successfully - install(upgrade)
        if ($result) {
            $install_mode = $already_installed ? 'upgrade' : 'install';
            if (!$pm->installExtension($extension_id, $type, $version, $install_mode)) {
                $this->session->data['error'] .= $this->language->get('error_install')
                                                .'<br><br>'.implode("<br>", $pm->errors);
                $this->removeTempFiles();
                abc_redirect($this->getBeginHref());
            }
        } else {
            if ($package_info['ftp']) {
                $this->session->data['error'] = $this->language->get('error_move_ftp')
                    .ABC::env('DIR_APP_EXTENSIONS')
                    .$extension_id.'<br><br>'
                    .implode("<br>", $pm->errors);
                abc_redirect($this->html->getSecureURL('tool/package_installer/confirm'));
            } else {
                $this->session->data['error'] = $this->language->get('error_move')
                                                .ABC::env('DIR_APP_EXTENSIONS')
                                                .$extension_id
                                                .'<br><br>'
                                                .implode("<br>", $pm->errors);
                $this->removeTempFiles();
                abc_redirect($this->getBeginHref());
            }
        }

        return true;
    }

    /**
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    protected function upgradeCore()
    {
        $package_info = $this->session->data['package_info'];
        if (H::versionCompare(ABC::env('VERSION'), $package_info['package_version'], ">=")) {

            $this->session->data['error'] = sprintf(
                                                    $this->language->get('error_core_version'),
                                                    ABC::env('VERSION')
                ).$package_info['package_version'].'!';
            unset($this->session->data['package_info']);
            abc_redirect($this->getBeginHref());
        }

        $pm = new APackageManager($this->session->data['package_info']);

        //replace files
        $pm->replaceCoreFiles();
        //run sql and php upgrade procedure files
        $package_dirname = $package_info['package_dir'];
        if ($pm->errors) {
            $this->session->data['error'] = implode("<br>", $pm->errors);
        }
        /**
         * @var \SimpleXMLElement|\DOMDocument $config
         */
        $config = simplexml_load_string(file_get_contents($package_dirname.'/package.xml'));
        if (!$config) {
            $this->session->data['error'] = 'Error: package.xml from package content is not valid xml-file!';
            unset($this->session->data['package_info']);
            abc_redirect($this->getBeginHref());
        }
        $pm->upgradeCore();
        $pm->updateCoreVersion((string)$config->version);
        if ($pm->errors) {
            $this->session->data['error'] .= implode('<br>', $pm->errors);
        }

        return true;
    }

    protected function findPackageDir()
    {
        $dirs = glob(
                $this->session->data['package_info']['tmp_dir']
                                .$this->session->data['package_info']['extension_key'].'/*',
                GLOB_ONLYDIR
        );
        foreach ($dirs as $dir) {
            if (file_exists($dir.'/package.xml')) {
                return str_replace($this->session->data['package_info']['tmp_dir'], '', $dir);
            }
        }
        //try to find package.xml in root of package
        if (is_file(
            $this->session->data['package_info']['tmp_dir']
            .$this->session->data['package_info']['extension_key'].'/package.xml')
        ) {
            return $this->session->data['package_info']['extension_key'];
        }

        return null;
    }

    protected function removeTempFiles()
    {

        $pm = new APackageManager($this->session->data['package_info']);
        $dirs = glob($pm->getTempDir().'*');
        $result = true;
        foreach ($dirs as $dir) {
            $result = !$pm->removeDir($dir) ? false : $result;
        }

        if (!$result) {
            $this->session->data['error'] = implode("<br>", $pm->errors);
            return false;
        }

        return true;
    }

    protected function getTempDir()
    {
        $package_info = (array)$this->session->data['package_info'];
        $pm = new APackageManager($package_info);
        return $pm->getTempDir();
    }

    protected function getBeginHref()
    {
        return $this->html->getSecureURL(
            'tool/package_installer'
            .($this->session->data['package_info']['package_source'] == 'file' ? '/upload' : '')
        );
    }

    // this method calls before installation of package
    protected function cleanTempDir()
    {
        $package_info = (array)$this->session->data['package_info'];
        $pm = new APackageManager($package_info);
        $pm->cleanTempDir();
    }

    protected function saveSessionPackageInfo($package_info)
    {
        foreach ($package_info as $k => $v) {
            $this->session->data['package_info'][$k] = $v;
        }
    }
}
