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
namespace abc\core\extension;
use abc\core\ABC;
use abc\core\engine\AForm;
use abc\core\engine\Extension;
use abc\core\engine\Registry;
use abc\core\view\AView;
use abc\models\content\Content;
use abc\models\customer\Customer;
use H;

class ExtensionGdpr extends Extension
{

    public function onControllerCommonHeaderBottom_InitData()
    {
        $that = $this->baseObject;
        $that->view->batchAssign($that->language->getASet('gdpr/gdpr'));
        $content_info = Content::getContent((int)$that->config->get('config_account_id'))?->toArray();
        $gdpr_expiration_days = $that->config->get('gdpr_expiration_days');

        $agree_href = $content_info
                    ? $that->html->getURL('content/content', '&content_id=' . $that->config->get('config_account_id'))
                    : '';

        $that->view->assign('expiration_days', $gdpr_expiration_days ?: 30);
        $that->view->assign('gdpr_privacy_policy_url', $agree_href);
        $that->view->batchAssign($that->language->getASet('gdpr/gdpr'));
        $that->document->addStyle(
            [
                'href'  => $that->view->templateResource('assets/css/gdpr-style.css'),
                'rel'   => 'stylesheet',
                'media' => 'screen',
            ]
        );
    }

    public function onControllerCommonHead_UpdateData()
    {
        $that = $this->baseObject;
        //$that->document->addScriptBottom($that->view->templateResource('assets/js/gdpr-cookie-monster.min.js'));
        $that->document->addScriptBottom($that->view->templateResource('assets/js/gdpr-script.js'));
    }

    public function onControllerPagesAccountAccount_InitData()
    {

        $that = $this->baseObject;
        $that->loadLanguage('gdpr/gdpr');
        $view = new AView(Registry::getInstance(), 0);
        $view->assign('view_data', $that->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'GDPRViewData',
                'text'  => $that->language->get('gdpr_view_button_text'),
                'icon'  => 'fa fa-eye',
                'style' => 'mt5 btn-default',
                //'attr' => 'data-toggle="modal" data-target="#GDPRModal"'
            ]
        ));
        $customer_id = $that->customer->getId();
        $customer_info = Customer::getCustomer($customer_id);
        $data = $customer_info['data'];
        if (isset($data['gdpr'])
            && isset($data['gdpr']['erasure_requested'])
            && $data['gdpr']['erasure_requested']
            && H::dateISO2Int($data['gdpr']['request_date'])
        ) {
            $btn_text = sprintf(
                $that->language->get('gdpr_already_requested'),
                H::dateISO2Display($data['gdpr']['request_date'], $that->language->get('date_format_short')));
            $disabled = true;
        } else {
            $btn_text = $that->language->get('gdpr_request_button_text');
            $disabled = false;
        }

        $view->assign('erase_data', $that->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'GDPREraseData',
                'text'  => $btn_text,
                'icon'  => 'fa fa-remove',
                'style' => 'mt5 btn-primary '.($disabled ? 'disabled' : ''),
                'href'  => $that->html->getSecureURL('extension/gdpr/erase'),
            ]
        ));

        $view->assign('change_data', $that->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'GDPRChangeData',
                'text'  => $that->language->get('gdpr_change_data'),
                'icon'  => 'fa fa-pencil',
                'style' => 'mt5 btn-default',
                'href'  => $that->html->getSecureURL('content/contact'),
            ]
        ));

        $that->view->addHookVar('account_bottom', $view->fetch('responses/extension/gdpr_buttons.tpl'));
    }

    public function onControllerPagesAccountSubscriber_InitData()
    {
        $that = $this->baseObject;
        $that->loadLanguage('account/create');
        $that->loadLanguage('gdpr/gdpr');
        $content_info = Content::getContent((int)$that->config->get('config_account_id'))?->toArray();
        if ($content_info){
            $text_agree_href = $that->html->getURL(
                'r/content/content/loadInfo',
                '&content_id=' . $that->config->get('config_account_id')
            );
        }else{
            $text_agree_href = '#';
        }
        $text_agree = $that->language->get('text_agree', 'account_create');
        $text_agree_href_text = $that->language->get('gdpr_privacy_policy_title');

        $agree_chk = $that->html->buildElement(
            [
                'type'    => 'checkbox',
                'name'    => 'agree',
                'value'   => 1,
                'checked' => false,
            ]
        );

        $that->view->addHookVar('subscriber_hookvar',
            '<div class="form-group">
        <div class="col-md-12">
            <label class="col-md-6 mt20 mb40 pull-right">
                '.$text_agree.'&nbsp;<a href="'.$text_agree_href.
            '" onclick="openModalRemote(\'#privacyPolicyModal\',\''.$text_agree_href.'\'); return false;">
                <b>'.$text_agree_href_text.'</b></a>
                '.$agree_chk.'
            </label></div></div>');
    }

    public function onModelAccountCustomer_ValidateData()
    {
        $that = $this->baseObject;
        if(isset($that->request->post['address_1'])){
            return null;
        }

        if (!$that->request->post['agree']) {
            $that->error['warning'] .= sprintf(
                $that->language->get('error_agree', 'account_create'),
                $that->language->get('gdpr_privacy_policy_title')
            );
        }
    }

    public function onControllerPagesSaleCustomer_UpdateData()
    {
        $customer_id = (int)$this->baseObject->request->get['customer_id'];
        if ($this->baseObject_method != 'update' || !$customer_id) {
            return null;
        }

        $that = $this->baseObject;
        if (str_starts_with($that->view->getData('loginname'), 'erased_')) {
            return null;
        }

        $that->loadLanguage('gdpr/gdpr');
        $form = new AForm('HS');
        $form_data = $that->view->getData('form');
        $form_data['fields']['details'] = $this->arraySpliceAfterKey(
            $form_data['fields']['details'],
            'status',
            [
                'erase' => $form->getFieldHtml(
                        [
                            'type'  => 'button',
                            'name'  => 'gdpr_erase',
                            'text'  => $that->language->get('gdpr_text_erase_personal_data'),
                            'href'  => $that->html->getSecureURL(
                                'r/extension/gdpr/erase',
                                '&customer_id='.$customer_id
                            ),
                            'style' => 'btn btn-default alert-danger',
                            'icon'  => 'fa fa-remove',
                        ])
                    .'<script type="application/javascript">
$("#gdpr_erase")
.attr("data-confirmation", "delete")
.attr("data-confirmation-text", '.abc_js_encode($that->language->get('gdpr_text_erase_confirm')).'   );
</script>',
            ]
        );

        $customer_info = Customer::find($customer_id)->toArray();
        $entry = $that->language->get('gdpr_entry_erase');
        if ($customer_info['data']) {
            $data = $customer_info['data'];
            if (isset($data['gdpr']) && isset($data['gdpr']['request_date'])) {
                $int = H::dateISO2Int($data['gdpr']['request_date']);
                $entry .= '<br>Erasure Requested at ';
                $color = '';
                $exp_days = $that->config->get('gdpr_expiration_days');
                $exp_days = $exp_days <= 0 ? 30 : $exp_days;
                if ((time() - $int) / 86400 > $exp_days) {
                    $color = 'red';
                }
                $entry .= '<b style="color:'.$color.'">'
                    .H::dateISO2Display($data['gdpr']['request_date'], $that->language->get('date_format_short'))
                    .'</b>';
            }
        }

        $that->view->assign('entry_erase', $entry);
        $that->view->assign('form', $form_data);
    }

    protected function arraySpliceAfterKey($array, $key, $array_to_insert)
    {
        $key_pos = array_search($key, array_keys($array));
        if ($key_pos !== false) {
            $key_pos++;
            $second_array = array_splice($array, $key_pos);
            $array = array_merge($array, $array_to_insert, $second_array);
        }
        return $array;
    }

    public function onControllerPagesAccountEdit_InitData() {
        if (ABC::env('IS_ADMIN')) {
            return;
        }
        $that = $this->baseObject;
        $that->loadLanguage('account/create');
        $that->loadLanguage('account/edit');
        $that->loadLanguage('gdpr/gdpr');
        $content_info = Content::getContent((int)$that->config->get('config_account_id'))?->toArray();
        if ($content_info){
            $text_agree_href = $that->html->getURL(
                'r/content/content/loadInfo',
                '&content_id=' . $that->config->get('config_account_id')
            );
        }else{
            $text_agree_href = '#';
        }
        $text_agree = $that->language->get('text_agree', 'account_create');
        $text_agree_href_text = $that->language->get('gdpr_privacy_policy_title');

        $agree_chk = $that->html->buildElement(
            [
                'type'    => 'checkbox',
                'name'    => 'agree',
                'value'   => 1,
                'checked' => false,
            ]
        );

        $that->view->addHookVar(
            'customer_attributes',
            '<div class="gdpr-container form-group">
            <div class="col-md-12">
                ' . $agree_chk . '
                <label class="col-md-6 mt20 mb40 pull-left">
                    ' . $text_agree . '&nbsp;<a href="' . $text_agree_href .
            '" onclick="openModalRemote(\'#privacyPolicyModal\',\'' . $text_agree_href . '\'); return false;">
                    <b>' . $text_agree_href_text . '</b></a>
                </label></div></div>');
    }
}
