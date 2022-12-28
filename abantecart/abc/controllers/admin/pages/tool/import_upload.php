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
use abc\core\lib\AData;

class ControllerPagesToolImportUpload extends AController
{
    /**
     * @var array()
     */
    public $data = [];
    public $errors = [];
    /**
     * @var array()
     */
    public $file_types = [
        'text/csv',
        'application/vnd.ms-excel',
        'text/plain',
        'application/octet-stream',
    ];
    /**
     * @var \abc\core\lib\AData
     */
    private $handler;


    public function main()
    {
        $this->data['import_format'] = 'other';
        $this->data['redirect'] = $this->html->getSecureURL('tool/import_export', '&active=import');

        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('tool/import_export');
        $redirect = $this->data['redirect'];

        if (!$this->request->is_POST() || !$this->user->canModify('tool/import_export')) {
            abc_redirect($redirect);
            $this->dispatch('error/permission');
            return;
        }

        if (empty($this->request->files)) {
            $this->session->data['error'] = 'File data for export is empty!';
            abc_redirect($redirect);
        }

        if (!$this->validateRequest()) {
            abc_redirect($redirect);
        }

        //All good so far, prepare import
        $this->handler = new AData();
        $file_data = $this->prepareImport();
        if ($file_data['error']) {
            $this->session->data['error'] = $file_data['error'];
            abc_redirect($redirect);
        }

        $this->session->data['import'] = $file_data;
        unset($this->session->data['import_map']);

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        //internal import format
        if ($file_data['format'] == 'internal') {
            abc_redirect($this->html->getSecureURL('tool/import_export/internal_import'));
        } else {
            abc_redirect($this->html->getSecureURL('tool/import_export/import_wizard'));
        }
    }

    protected function prepareImport()
    {
        $file = $this->request->files['imported_file'];
        $post = $this->request->post;

        $res = [];
        $res['run_mode'] = isset($post['test_mode']) ? $post['test_mode'] : 'commit';
        $res['delimiter_id'] = $post['options']['delimiter'];
        $res['delimiter'] = $this->handler->csvDelimiters[$res['delimiter_id']];

        if (in_array($file['type'],
            ['text/csv', 'application/vnd.ms-excel', 'text/plain', 'application/octet-stream'])) {
            #NOTE: 'application/octet-stream' is a solution for Windows OS sending unknown file type
            $res['file_type'] = 'csv';
        } else {
            return ['error' => $this->language->get('error_file_format')];
        }

        //move uploaded file to tmp processing location
        $res['file'] = ABC::env('DIR_DATA').'import_'.basename($file['tmp_name']).".txt";
        $result = move_uploaded_file($file['tmp_name'], $res['file']);
        if ($result === false) {
            //remove trunk
            unlink($file['tmp_name']);
            $error_text = 'Error! Unable to move uploaded file to '.$res['file'];
            return ['error' => $error_text];
        }

        $this->extensions->hk_ProcessData($this, 'file_uploaded', $res);

        //detect file format
        if ($res['file_type'] == 'csv') {
            ini_set('auto_detect_line_endings', true);
            if ($fh = fopen($res['file'], 'r')) {
                $cols = fgetcsv($fh, 0, $res['delimiter']);
                if (count($cols) < 2) {
                    return ['error' => $this->language->get('error_csv_import')];
                }
                //do we have internal format or some other
                $res['format'] = $this->data['import_format'];
                $count_dots = 0;
                foreach ($cols as $key) {
                    if (strpos($key, ".") !== false) {
                        $count_dots++;
                    }
                }

                //try to detect file format basing on column names
                $cols_count = count($cols);
                $exclude_col_names = ['action'];
                foreach ($exclude_col_names as $exclude_col_name) {
                    if (in_array($exclude_col_name, $cols)) {
                        $cols_count--;
                    }
                }
                if ($count_dots == $cols_count) {
                    $res['format'] = 'internal';
                    list($res['table'],) = explode('.', $cols[0]);
                }
            } else {
                return ['error' => $this->language->get('error_data_corrupted')];
            }
            $res['request_count'] = 0;
            while (fgetcsv($fh, 0, $res['delimiter']) !== false) {
                $res['request_count']++;
            }
            fclose($fh);
        }
        return $res;
    }

    protected function validateRequest()
    {
        $file = $this->request->files['imported_file'];
        $this->errors = [];
        if (!is_dir(ABC::env('DIR_DATA'))) {
            mkdir(ABC::env('DIR_DATA'), 0755, true);
        }
        if (!is_writable(ABC::env('DIR_DATA'))) {
            $this->errors['error'] = sprintf($this->language->get('error_tmp_dir_non_writable'), ABC::env('DIR_DATA'));
        } elseif (!in_array($file['type'], $this->file_types)) {
            $this->errors['error'] = $this->language->get('error_file_format');
        } elseif (file_exists($file['tmp_name']) && $file['size'] > 0) {

        } elseif (file_exists($file['tmp_name'])) {
            $this->errors['error'] = $this->language->get('error_file_empty');
        } elseif ($file['error'] != 0) {
            $this->errors['error'] = $this->language->get('error_upload_'.$file['error']);
        } else {
            $this->errors['error'] = $this->language->get('error_empty_request');
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__);
        if ($this->errors) {
            $this->session->data['error'] = $this->errors['error'];
            return false;
        } else {
            return true;
        }
    }

}
