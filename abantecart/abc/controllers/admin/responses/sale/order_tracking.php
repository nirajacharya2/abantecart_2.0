<?php

namespace abc\controllers\admin;
use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\engine\Registry;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\models\order\Order;
use abc\models\order\OrderProduct;
use abc\models\order\OrderStatus;
use abc\modules\events\ABaseEvent;
use Carbon\Carbon;
use H;

class ControllerResponsesSaleOrderTracking extends AController
{
    public function products()
    {

        $this->loadLanguage('sale/order');
        $this->loadModel('catalog/product');
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if (H::has_value($this->session->data['error'])) {
            $this->data['error']['warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = Order::getOrderArray($order_id, 'any');
        if(!$order_info){
            exit('order # '.$order_id.' not found!');
        }

        $post = $this->request->post;
        if ($this->request->is_POST() && is_array($post['product'])) {

            $this->db->beginTransaction();
            $order = Order::find($order_id);
            try {
                foreach($post['product'] as $orderProduct){
                    /**
                     * @var OrderProduct $op
                     */
                    $op = OrderProduct::find($orderProduct['order_product_id']);
                    if($op && $op->order_status_id != $orderProduct['order_status_id']){
                        $op->update(['order_status_id' => $orderProduct['order_status_id']]);
                        $order->update(['date_modified' => Carbon::now()]);
                        $order->touch();
                    }
                }
                $this->db->commit();
                H::event('abc\models\admin\order@update', [new ABaseEvent($order_id, $post)]);
            } catch (\Exception $e) {
                Registry::log()->write($e->getMessage());
                $this->db->rollback();
                $error = new AError('');
                return $error->toJSONResponse('APP_ERROR_406',
                    [
                        'error_text'  => 'Application error. See error log for details',
                        'reset_value' => true,
                    ]);

            }

            $this->response->addJSONHeader();
            $this->response->setOutput(AJson::encode(['result_text' => $this->language->get('text_saved')]));
            return;
        }

        if ($this->error) {
            $this->session->data['error'] = implode(' ', $this->error);
            abc_redirect($this->html->getSecureURL('sale/order/details', '&order_id='.$order_id));
        }

        $this->data['order_info'] = $order_info;

        //set content language to order language ID.
        if ($this->language->getContentLanguageID() != $order_info['language_id']) {
            //reset content language
            $this->language->setCurrentContentLanguage($order_info['language_id']);
        }

        if (empty($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_load');
            abc_redirect($this->html->getSecureURL('sale/order'));
        }


        if (isset($this->session->data['attention'])) {
            $this->data['attention'] = $this->session->data['attention'];
            unset($this->session->data['attention']);
        } else {
            $this->data['attention'] = '';
        }
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $this->data['heading_title'] = $this->language->get('heading_title').' #'.$order_id;
        $this->data['token'] = $this->session->data['token'];
        $this->data['invoice_url'] = $this->html->getSecureURL('sale/invoice', '&order_id='.$order_id);
        $this->data['button_invoice'] = $this->html->buildElement(
            [
                'type' => 'button',
                'name' => 'generate_invoice',
                'text' => $this->language->get('button_generate'),
            ]
        );

        $this->data['order_id'] = $order_id;
        $this->data['action'] = $this->html->getSecureURL('r/sale/order_tracking/products', '&order_id='.$order_id);
        $this->data['cancel'] = $this->html->getSecureURL('sale/order');

        $this->data['currency'] = $this->currency->getCurrency($order_info['currency']);

        $this->data['form_title'] = $this->language->get('edit_title_details');
        $form = new AForm('HT');
        $form_id = 'orderTrackProductFrm';
        $form->setForm([
            'form_name' => $form_id,
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = $form_id;
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => $form_id,
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->data['action'],
            ]
        );
        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_save'),
                'style' => 'button1',
            ]
        );
        $this->data['form']['cancel'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'cancel',
                'text'  => $this->language->get('button_cancel'),
                'style' => 'button2',
            ]
        );

        $this->data['title'] = $this->language->get('text_tracking_products').' #'.$order_id." - ".$order_info['firstname'].' '.$order_info['lastname'];

        $this->data['order_products'] = [];

        $order_products = OrderProduct::where('order_id', '=', $order_id)->orderBy('date_added')->get()->toArray();

        //get combined database and config info about each order status
        $orderStatuses = OrderStatus::getOrderStatusConfig();

        foreach ($order_products as $kk => $order_product) {
            $option_data = [];
            $options = OrderProduct::getOrderProductOptions($order_id, $order_product['order_product_id']);
            foreach ($options as $option) {
                $value = $option['value'];
                //generate link to download uploaded files
                if ($option['element_type'] == 'U') {
                    $file_settings = unserialize($option['settings']);
                    $filename = $value;
                    if (H::has_value($file_settings['directory'])) {
                        $file = ABC::env('DIR_APP').'system'.DS.'uploads'.DS.$file_settings['directory'].DS.$filename;
                    } else {
                        $file = ABC::env('DIR_APP').'system'.DS.'uploads'.DS.$filename;
                    }

                    if (is_file($file)) {
                        $value = '<a href="'.$this->html->getSecureURL(
                                'tool/files/download',
                                '&filename='.urlencode($filename).'&order_option_id='.(int)$option['order_option_id']
                            ).'" title=" to download file" target="_blank">'.$value.'</a>';
                    } else {
                        $value = '<span title="file '.$file.' is unavailable">'.$value.'</span>';
                    }

                } elseif ($option['element_type'] == 'C' && $value == 1) {
                    $value = '';
                }
                $title = '';
                // strip long textarea value
                if ($option['element_type'] == 'T') {
                    $title = strip_tags($value);
                    $title = str_replace('\r\n', "\n", $title);

                    $value = str_replace('\r\n', "\n", $value);
                    if (mb_strlen($value) > 64) {
                        $value = mb_substr($value, 0, 64).'...';
                    }
                }

                $option_data[] = [
                    'name'                    => $option['name'],
                    'value'                   => nl2br($value),
                    'title'                   => $title,
                    'product_option_id'       => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                ];
            }

            //check if this product product is still available, so we can use recalculation against the cart
            $product = $this->model_catalog_product->getProduct($order_product['product_id']);
            if(!$this->config->get('config_allow_order_recalc')) {
                if (empty($product) || !$product['status'] || $product['call_to_order']) {
                    $this->data['no_recalc_allowed'] = true;
                    $product['status'] = 0;
                } else {
                    if (H::dateISO2Int($product['date_available']) > time()) {
                        $this->data['no_recalc_allowed'] = true;
                        $product['status'] = 0;
                    }
                }
            }


            $this->data['cancel_statuses'] = [];
            $statuses = $disabled_statuses = [];
            foreach ($orderStatuses as $oStatus) {
                if ($oStatus['display_status'] || $oStatus['order_status_id'] == $order_product['order_status_id']) {
                    $statuses[$oStatus['order_status_id']] = $oStatus['description']['name'];
                }
                if (!$oStatus['display_status']) {
                    $disabled_statuses[] = (string)$oStatus['order_status_id'];
                }
                if (in_array('return_to_stock', (array)$oStatus['config']['actions'])) {
                    $this->data['cancel_statuses'][] = $oStatus['order_status_id'];
                }
            }

            $readonly = '';
            if (in_array(
                $this->order_status->getStatusById($order_product['order_status_id']),
                (array)ABC::env('ORDER')['not_reversal_statuses'])
            ) {
                $readonly = 'readonly';
                $disabled_statuses = $statuses;
                unset($disabled_statuses[$order_product['order_status_id']]);
                $disabled_statuses = array_keys($disabled_statuses);
            }

            $this->data['order_products'][$kk] =
                array_merge($order_product,
                            [
                                'disable_edit'     => in_array($order_product['order_status_id'], $this->data['cancel_statuses']),
                                'product_status'   => $product['status'],
                                'order_status_id'  => $form->getFieldHtml([
                                    'type'             => 'selectbox',
                                    'name'             => 'product['.$order_product['order_product_id'].'][order_status_id]',
                                    'value'            => $order_product['order_status_id'],
                                    'options'          => $statuses,
                                    'disabled_options' => $disabled_statuses,
                                    'attr'             => $readonly,
                                ]),
                                'option'           => $option_data,
                                'price'            => $this->currency->format(
                                    $order_product['price'],
                                    $order_info['currency'],
                                    $order_info['value']
                                ),
                                'total'            => $this->currency->format_total(
                                    $order_product['price'],
                                    $order_product['quantity'],
                                    $order_info['currency'], $order_info['value']
                                ),
                                'href'             => $this->html->getSecureURL(
                                    'catalog/product/update',
                                    '&product_id='.$order_product['product_id']
                                ),
                            ]
                );
        }

        $this->data['order_edit_url'] = $this->html->getSecureURL('sale/order/details', '&order_id='. $order_id);
        $this->data['redirect_confirm_text'] = $this->language->get('text_confirm_redirect_to_order_details');

        $this->view->batchAssign($this->data);
        $this->view->assign('help_url', $this->gen_help_url('order_details'));

        $tpl = 'responses/sale/orderTrackingProducts.tpl';


        $this->processTemplate($tpl);

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

}