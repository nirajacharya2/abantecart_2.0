<?php

/**
 * Class OptionValueModel.
 *
 * @OA\Schema (
 *     schema="OptionValueModel"
 * )
 */

class OptionValueModel {
    /**
     * @OA\Property(
     *     description="Product option value id",
     * )
     * @var integer
     */
    private $product_option_value_id;

    /**
     * @OA\Property(
     *     description="Product attribute value id",
     * )
     * @var integer
     */
    private $attribute_value_id;

    /**
     * @OA\Property(
     *     description="Product attribute data",
     * )
     * @var string
     */
    private $grouped_attribute_data;

    /**
     * @OA\Property(
     *     description="Option group id",
     * )
     * @var integer
     */
    private $group_id;

    /**
     * @OA\Property(
     *     description="Option name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Option placeholder",
     * )
     * @var string
     */
    private $option_placeholder;

    /**
     * @OA\Property(
     *     description="Option pattern",
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
     *     description="Option settings",
     * )
     * @var string
     */
    private $settings;

    /**
     * @OA\Property(
     *     description="Child Option names",
     *     @OA\Items()
     * )
     * @var array
     */
    private $children_options_names;

    /**
     * @OA\Property(
     *     description="SKU",
     * )
     * @var string
     */
    private $sku;

    /**
     * @OA\Property(
     *     description="Price",
     * )
     * @var double
     */
    private $price;

    /**
     * @OA\Property(
     *     description="Prefix",
     * )
     * @var string
     */
    private $prefix;

    /**
     * @OA\Property(
     *     description="Weight",
     * )
     * @var double
     */
    private $weight;

    /**
     * @OA\Property(
     *     description="Weight type",
     * )
     * @var string
     */
    private $weight_type;

    /**
     * @OA\Property(
     *     description="Quantity",
     * )
     * @var integer
     */
    private $quantity;

    /**
     * @OA\Property(
     *     description="Subtract",
     * )
     * @var integer
     */
    private $subtract;

    /**
     * @OA\Property(
     *     description="Default",
     * )
     * @var integer
     */
    private $default;
}
