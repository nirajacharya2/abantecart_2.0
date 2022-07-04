<?php


/**
 * Class OptionModel.
 *
 * @OA\Schema (
 *     schema="OptionModel"
 * )
 */

class OptionModel {
    /**
     * @OA\Property(
     *     description="Product option id",
     * )
     * @var integer
     */
    private $product_option_id;

    /**
     * @OA\Property(
     *     description="Product attribute id",
     * )
     * @var integer
     */
    private $attribute_id;

    /**
     * @OA\Property(
     *     description="Option group id",
     * )
     * @var integer
     */
    private $group_id;

    /**
     * @OA\Property(
     *     description="Option Name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Option Placeholder",
     * )
     * @var string
     */
    private $option_placeholder;

    /**
     * @OA\Property(
     *     description="Option value",
     *     @OA\Items(
     *     ref="#/components/schemas/OptionValueModel")
     * )
     * @var array
     */
    private $option_value;

    /**
     * @OA\Property(
     *     description="Sort Order",
     * )
     * @var integer
     */
    private $sort_order;

    /**
     * @OA\Property(
     *     description="Element Type",
     * )
     * @var string
     */
    private $element_type;

    /**
     * @OA\Property(
     *     description="Html Type",
     * )
     * @var string
     */
    private $html_type;

    /**
     * @OA\Property(
     *     description="Is required",
     * )
     * @var boolean
     */
    private $required;

    /**
     * @OA\Property(
     *     description="Regexp patern",
     * )
     * @var string
     */
    private $regexp_pattern;

    /**
     * @OA\Property(
     *     description="Error text",
     * )
     * @var string
     */
    private $error_text;

    /**
     * @OA\Property(
     *     description="Settings",
     * )
     * @var string
     */
    private $settings;
}
