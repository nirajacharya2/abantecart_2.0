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
namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\lib\AException;
use abc\extensions\gdpr\models\storefront\extension\ModelExtensionGdpr;
use abc\models\customer\Customer;
use H;

/**
 * Class ControllerResponsesExtensionGdpr
 *
 * @property ModelExtensionGdpr $model_extension_gdpr
 */
class ControllerResponsesExtensionGdpr extends AController
{

    public $data = [];
    public $error = [];

    public function erase()
    {
        if (!$this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('index/home'));
        }
        $customer_id = $this->customer->getId();
        $this->loadLanguage('gdpr/gdpr');
        $customer_info = Customer::getCustomer($customer_id);
        $data = $customer_info['data'];
        //if already requested - do nothing
        if (isset($data['gdpr'])
            && isset($data['gdpr']['erasure_requested'])
            && $data['gdpr']['erasure_requested'] == true
        ) {

            abc_redirect($this->html->getSecureURL('account/account'));
        } else {
            $data['gdpr'] = [
                'erasure_requested' => true,
                'request_date'      => H::dateInt2ISO(time()),
            ];
            $this->messages->saveWarning(
                sprintf(
                    $this->language->get('gdpr_message_title'),
                    $customer_info['firstname'].' '.$customer_info['lastname']
                ),
                sprintf(
                    $this->language->get('gdpr_message_text'),
                    '#admin#rt=sale/customer/update&customer_id='.$customer_id
                )
            );
            $this->toHistory('r');
            /**
             * @var Customer $customer
             */
            $customer = Customer::find($customer_id);
            $customer->update( ['data' => $data ] );
            $this->session->data['success'] = $this->language->get('gdpr_success_requested');
            abc_redirect($this->html->getSecureURL('account/account'));
        }
    }

    public function viewdata()
    {
        if (!$this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('index/home'));
        }
        $customer_id = $this->customer->getId();
        $this->loadModel('extension/gdpr');

        $this->toHistory('v');

        $data = $this->model_extension_gdpr->getPersonalData($customer_id);
        $this->view->assign('data', $data);

        $this->processTemplate('responses/extension/gdpr_view_data_modal.tpl');
    }

    public function download()
    {
        if (!$this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('index/home'));
        }
        $customer_id = $this->customer->getId();
        $this->loadModel('extension/gdpr');
        $data = $this->model_extension_gdpr->getPersonalData($customer_id);
        $filename = sys_get_temp_dir().'/gdpr-'.$customer_id.'-'.time().'.csv';
        $file = fopen($filename, 'a+');
        foreach ($data as $table_name => $rows) {
            foreach ($rows as $row) {
                foreach ($row as $n => &$v) {
                    $v = $n.':-:'.$v;
                }
                $input = ['table_name' => $table_name] + $row;
                fputcsv($file, $input);
            }
        }
        fclose($file);

        $mask = basename($filename);
        $mime = 'text/csv';
        $encoding = 'binary';
        if (!headers_sent()) {
            if (file_exists($filename)) {
                $file_handler = fopen($filename, "rb");
                $filesize = filesize($filename);
                header('Pragma: public');
                header('Expires: 0');
                header('Content-Description: File Transfer');
                header('Content-Type: '.$mime);
                header('Content-Transfer-Encoding: '.$encoding);
                header('Content-Disposition: attachment; filename='.$mask);
                header('Content-Length: '.$filesize);
                ob_end_clean();

                while (!feof($file_handler)) {// if we haven't got to the End Of File
                    print(fread($file_handler, 1024 * 8));//read 8k from the file and send to the user
                    flush();//force the previous line to send its info
                    if (connection_status() != 0) {//check the connection, if it has ended...
                        @fclose($file_handler);
                        @unlink($filename);
                        $this->toHistory('d');
                        exit();
                    }
                }
                $this->toHistory('d');
                fclose($file_handler);

            } else {
                throw new AException('Error: Could not find file '.$file.'!', AC_ERR_LOAD);
            }
        } else {
            exit('Error: Headers already sent out!');
        }

    }

    /**
     * @param string $type - can be v - viewed data, d - downloaded data, r - requested erasure, e - erased
     *
     * @return bool|int
     * @throws \Exception
     */
    protected function toHistory($type)
    {
        $this->loadModel('extension/gdpr');
        return $this->model_extension_gdpr->saveHistory(
            [
                'customer_id'     => $this->customer->getId(),
                'request_type'    => $type,
                'email'           => $this->customer->getEmail(),
                'name'            => $this->customer->getFirstName().' '.$this->customer->getLastName(),
                'user_agent'      => $this->request->server['HTTP_USER_AGENT'],
                'accept_language' => $this->request->server['HTTP_ACCEPT_LANGUAGE'],
                'ip'              => $this->request->getRemoteIP(),
                'server_ip'       => $this->request->server['SERVER_ADDR'],
            ]
        );
    }
}
