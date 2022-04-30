<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2021 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\core\lib;

/**
 * Class Form
 *
 * @package abc\core\lib
 *
 * @property string $url
 * @property string $form_name
 * @property string $title
 * @property string $method
 * @property boolean $ajax
 * @property FormField[] $form_fields
 */
class Form
{
    /* @var $url string */
    public $url;
    /* @var $back_url string */
    public $back_url;
    /* @var $form_name string */
    public $form_name;
    /* @var  $title string */
    public $title;
    /* @var  $method string */
    public $method;
    /* @var  $ajax boolean */
    public $ajax;
    /* @var  $form_fields FormField[] */
    public $form_fields;

    /**
     * Form constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['url'])) {
            $this->url = $data['url'];
        }
        if (isset($data['back_url'])) {
            $this->back_url = $data['back_url'];
        }
        if (isset($data['form_name'])) {
            $this->form_name = $data['form_name'];
        }
        if (isset($data['title'])) {
            $this->title = $data['title'];
        }
        if (isset($data['method'])) {
            $this->method = $data['method'];
        } else {
            $this->method = 'post';
        }
        if (isset($data['ajax'])) {
            $this->ajax = $data['ajax'];
        } else {
            $this->ajax = true;
        }
        if (isset($data['form_fields'])) {
            $this->form_fields = $data['form_fields'];
        }
    }

    public function setFormFields(array $data) {
        $this->form_fields = [];
        foreach ($data as $key => $datum) {
            $this->form_fields[$key] = new FormField($datum);
        }
    }
    public function getFormFields() {
        return $this->form_fields;
    }

    public function toJson() {
        return json_encode($this->toArray());
    }

    public function toArray() {
        $result = get_object_vars($this);
        $result['form_fields'] = $this->formFieldsToArray();

     /*   usort($result['form_fields'], function ($a,$b) {
            if ($a['sort_ortorder'] > (int)$b['sort_order']) {
                return 1;
            }
            if ($a['sort_ortorder'] < (int)$b['sort_order']) {
                return -1;
            }
         return 0;
        });*/

        return $result;
    }

    private function formFieldsToArray() {
        $arrayFormFields = [];
        foreach ($this->form_fields as $key => $form_field) {
            $arrayFormFields[$key] = get_object_vars($form_field);
        }
        return $arrayFormFields;
    }

}
