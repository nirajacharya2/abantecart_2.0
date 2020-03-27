<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
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

use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\catalog\GlobalAttributesValueDescription;
use abc\models\catalog\ObjectFieldSetting;
use abc\models\catalog\ObjectType;
use H;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class FormBuilder
 *
 * @package abc\core\lib
 */
class FormBuilder
{
    /* @var $fields array */
    protected $fields;
    /* @var $model string */
    protected $model;
    /* @var $object_type int */
    protected $object_type;
    /* @var $registry Registry */
    protected $registry;
    /* @var $form Form */
    protected $form;
    /* @var $fields_preset array */
    protected $fields_preset;

    /**
     * FormBuilder constructor.
     *
     * @param BaseModel $model
     * @param int       $object_type
     * @param array     $formData
     */
    public function __construct($model, $object_type, $formData)
    {
        $this->model = $model;
        $this->object_type = $object_type;

        $this->fields_preset = $formData['fields_preset'];

        $this->form = new Form($formData);

        $this->registry = Registry::getInstance();

        $this->buildForm();
    }

    private function buildForm()
    {
        $this->loadFields();
        $this->loadRelations();
        $this->applyFieldSettings();
        $this->fields = array_merge($this->fields, self::getCsrfToken());
        $this->form->setFormFields($this->fields);
    }

    public static function getCsrfToken()
    {
        $registry = Registry::getInstance();
        $csrftoken = $registry->get('csrftoken');
        $token['csrftoken'] = [
            'name'        => 'csrftoken',
            'input_type'  => 'hidden',
            'value'       => $csrftoken->setToken()
        ];

        $instanceId = array_search($token['csrftoken']['value'], $registry->get('session')->data['csrftoken']);

        $token['csrfinstance'] = [
            'name'        => 'csrfinstance',
            'input_type'  => 'hidden',
            'value'       => $instanceId
        ];

        return $token;
    }

    private function loadFields()
    {
        $objInst = new $this->model;
        $this->fields = $objInst->getFields();

        if ($this->object_type) {
            $attributes = ObjectType::where('object_type', (new ReflectionClass($this->model))->getShortName())
                ->with([
                    'global_attribute_groups.global_attributes.values.descriptions',
                    'global_attribute_groups.global_attributes.description',
                ])
                ->find($this->object_type)
                ->toArray();

            if (!$attributes) {
                return;
            }

            $availibleFieldTypes = HtmlElementFactory::getAvailableElements();
            $elementsWithOptions = HtmlElementFactory::getElementsWithOptions();

            foreach ($attributes['global_attribute_groups'] as $attribute_group) {
                foreach ($attribute_group['global_attributes'] as $global_attribute) {

                    if (!$availibleFieldTypes[$global_attribute['element_type']]) {
                        continue;
                    }

                    $type = $availibleFieldTypes[$global_attribute['element_type']]['type'];

                    $description = $global_attribute['description'];

                    $fieldOptions = [
                        'name'        => 'attribute_'.$global_attribute['attribute_id'],
                        'title'       => $description['name'],
                        'placeholder' => $description['placeholder'],
                        'error_text'  => $description['error_text'],
                        'input_type'  => $type,
                        'value'       => '',
                        'required'    => $global_attribute['required'],
                    ];


                    if (in_array($global_attribute['element_type'], $elementsWithOptions)) {

                        $options = [];

                        foreach ($global_attribute['values'] as $attributes_value) {
                            foreach ($attributes_value['descriptions'] as $description) {
                                if ($description['language_id'] === $this->registry->get('language')->getContentLanguageID()) {
                                    $value_description = $description;
                                }
                            }

                            if (is_array($value_description)) {
                                $options[] = ['value' => $attributes_value['attribute_value_id'], 'text' => $value_description['value']];
                            }
                        }

                        $fieldOptions['props']['items'] = $options;
                        if ($global_attribute['element_type'] == 'M') {
                            $fieldOptions['props']['multiple'] = true;
                            $fieldOptions['input_type'] = 'selectbox';
                        }
                    }

                    $this->fields[$fieldOptions['name']] = $fieldOptions;
                }
            }
        }
    }

    private function loadRelations()
    {
        foreach ($this->fields as $key => &$field) {
            if (isset($field['relation']) && !empty($field['relation'])) {
                $model = new $this->model();
                $relation = $model->{$field['relation']}();
                $options = [];
                if (is_array($relation)) {
                    foreach ($relation as $item) {
                        $options[] = ['value' => $item->id, 'text' => $item->name];

                    }
                }
                $field['props']['items'] = $options;
            }
        }
    }

    private function applyFieldSettings()
    {
        $settings = ObjectFieldSetting::where('object_type', (new ReflectionClass($this->model))->getShortName())
            ->where('object_type_id', $this->object_type)
            ->get()
            ->toArray();

        foreach ($this->fields as $key => &$field) {

            $field = $this->modifyFields($field, $key);

            foreach ($settings as $setting) {
                if ($setting['object_field_name'] == $key) {

                    $reflection = new ReflectionClass(FormFieldModifier::class);
                    $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC);

                    foreach ($methods as $method) {
                        if ($method->name === $setting['field_setting']) {
                            $field = FormFieldModifier::{$setting['field_setting']}($field, $setting['field_setting_value']);
                        }
                    }

                }

            }
        }
    }

    private function modifyFields($field, $field_name)
    {
        if (!$field['name']) {
            $field['name'] = $field_name;
        }

        if (!$field['name']) {
            $field['name'] = $field_name;
        }
        if (!$field['title']) {
            $field['title'] = $this->registry->get('language')->get('title_'.$field['name']);
        }

        $field = array_merge_recursive($field, $this->fields_preset['default']);
        if (isset($this->fields_preset['fields'][$field_name])) {
            $field = array_merge($field, $this->fields_preset['fields'][$field_name]);
        }

        $field['rule'] = $this->converValidateRules($field);

        return $field;
    }

    private function converValidateRules($field)
    {
        $rules = explode('|', $field['rule']);
        $modified_rules = [];
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'string':
                    $modified_rules[] = 'required';
                    break;
                case 'nullable':
                    break;
                case 'max:1':
                    break;
                case 'number':
                    $modified_rules[] = 'decimal:3';
                    break;
                default:
                    $modified_rules[] = $rule;
            }
        }
        return implode('|', $modified_rules);
    }

    public function getFormFields()
    {
        return $this->fields;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    public function loadErrorMessages($language_id)
    {

    }

    public function getHtmlForm()
    {

    }

}
