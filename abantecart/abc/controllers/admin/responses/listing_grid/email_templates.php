<?php

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\models\system\EmailTemplate;
use H;
use stdClass;

class ControllerResponsesListingGridEmailTemplates extends AController
{
    public $data = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $result = EmailTemplate::getEmailTemplates($this->request->post);
        $response = new stdClass();
        $response->page = $result['page'];
        $response->total = ceil($result['total']/$result['limit']);
        $response->records = $result['total'];
        $response->userdata = new stdClass();

        $i = 0;
        foreach ($result['items'] as $item) {
            $response->rows[$i]['id'] = $item['id'];
            $response->rows[$i]['cell'] = [
                $item['text_id'],
                $item['name'],
                $this->html->buildCheckbox([
                    'name'  => 'status['.$item['id'].']',
                    'value' => $item['status'],
                    'style' => 'btn_switch',
                ]),
                $item['subject'],
            ];
            $i++;
        }

        $this->data['response'] = $response;

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    public function update_field()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->request->is_POST()) {
            $post = $this->request->post;
            if (!is_array($post['status'])) {
                return;
            }
            foreach ((array)$post['status'] as $key=>$value) {
                $emailTemplate = EmailTemplate::find($key);
                if (!$emailTemplate) {
                    continue;
                }
                $emailTemplate->update(['status' => (int)$value]);
                $emailTemplate->save();
            }
        }


        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    public function update()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->request->is_POST()) {
            $post = $this->request->post;
            if ($post['oper'] === 'save') {
                if (!is_array($post['status'])) {
                    return;
                }
                foreach ((array)$post['status'] as $key => $value) {
                    $emailTemplate = EmailTemplate::find($key);
                    if (!$emailTemplate) {
                        continue;
                    }
                    $emailTemplate->update(['status' => (int)$value]);
                }
            }

            if ($post['oper'] === 'del' && H::has_value($post['id'])) {
                $ids = array_unique(explode(',', $post['id']));
                EmailTemplate::whereIn('id', $ids)->delete();
            }
        }


        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}