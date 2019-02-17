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

/**
 * Class FormField
 *
 * @package abc\core\lib
 */
class FormField
{
    /**
     * @var $name string
     */
    public $name;
    /**
     * @var $title string
     */
    public $title;
    /**
     * @var $type string
     */
    public $type;
    /**
     * @var $value string
     */
    public $value;
    /**
     * @var $validate string
     */
    public $validate;
    /**
     * @var $sort_order int
     */
    public $sort_order;
    /**
     * @var $options array
     */
    public $options;
    /**
     * @var $props array
     */
    public $props;
    /**
     * @var $v_flex_props array
     */
    public $v_flex_props;
    /**
     * @var $ajax_params array
     */
    public $ajax_params;

    /**
     * FormField constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        if (isset($data['title'])) {
            $this->title = $data['title'];
        }
        if (isset($data['input_type'])) {
            $this->type = $data['input_type'];
        } elseif (isset($data['type'])) {
            $this->type = $data['type'];
        }
        if (isset($data['rule'])) {
            $this->validate = $data['rule'];
        }
        if (isset($data['value'])) {
            $this->value = $data['value'];
        }
        if (isset($data['sort_order'])) {
            $this->sort_order = $data['sort_order'];
        }
        if (isset($data['props'])) {
            $this->props = $data['props'];
        }
        if (isset($data['v_flex_props'])) {
            $this->v_flex_props = $data['v_flex_props'];
        }
        if (isset($data['options'])) {
            $this->options = $data['options'];
        }
        if (isset($data['ajax_params'])) {
            $this->ajax_params = $data['ajax_params'];
        }
    }

}
