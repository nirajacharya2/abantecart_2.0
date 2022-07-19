<?php

/**
 * Class ProductModel.
 *
 * @OA\Schema (
 *     schema="ProductModel"
 * )
 */
class ProductModel
{
    /**
     * @OA\Property(
     *     description="Product unique id",
     * )
     * @var integer
     */
    private $product_id;

    /**
     * @OA\Property(
     *     description="UUID",
     * )
     * @var string
     */
    private $uuid;

    /**
     * @OA\Property(
     *     description="model",
     * )
     * @var string
     */
    private $model;

    /**
     * @OA\Property(
     *     description="sku",
     * )
     * @var string
     */
    private $sku;

    /**
     * @OA\Property(
     *     description="location",
     * )
     * @var string
     */
    private $location;

    /**
     * @OA\Property(
     *     description="stock_checkout",
     * )
     * @var string
     */
    private $stock_checkout;

    /**
     * @OA\Property(
     *     description="stock_status_id",
     * )
     * @var integer
     */
    private $stock_status_id;

    /**
     * @OA\Property(
     *     description="Manufacturer Id",
     * )
     * @var integer
     */
    private $manufacturer_id;

    /**
     * @OA\Property(
     *     description="shipping",
     * )
     * @var integer
     */
    private $shipping;

    /**
     * @OA\Property(
     *     description="ship_individually",
     * )
     * @var integer
     */
    private $ship_individually;

    /**
     * @OA\Property(
     *     description="free_shipping",
     * )
     * @var integer
     */
    private $free_shipping;

    /**
     * @OA\Property(
     *     description="shipping_price",
     * )
     * @var double
     */
    private $shipping_price;

    /**
     * @OA\Property(
     *     description="price",
     * )
     * @var string
     */
    private $price;

    /**
     * @OA\Property(
     *     description="tax_class_id",
     * )
     * @var integer
     */
    private $tax_class_id;

    /**
     * @OA\Property(
     *     description="date_available",
     * )
     * @var string
     */
    private $date_available;

    /**
     * @OA\Property(
     *     description="weight",
     * )
     * @var double
     */
    private $weight;

    /**
     * @OA\Property(
     *     description="weight_class_id",
     * )
     * @var integer
     */
    private $weight_class_id;

    /**
     * @OA\Property(
     *     description="length",
     * )
     * @var double
     */
    private $length;

    /**
     * @OA\Property(
     *     description="width",
     * )
     * @var double
     */
    private $width;

    /**
     * @OA\Property(
     *     description="height",
     * )
     * @var double
     */
    private $height;

    /**
     * @OA\Property(
     *     description="length_class_id",
     * )
     * @var integer
     */
    private  $length_class_id;

    /**
     * @OA\Property(
     *     description="status",
     * )
     * @var integer
     */
    private $status;

    /**
     * @OA\Property(
     *     description="viewed",
     * )
     * @var integer
     */
    private $viewed;

    /**
     * @OA\Property(
     *     description="sort order",
     * )
     * @var integer
     */
    private $sort_order;

    /**
     * @OA\Property(
     *     description="Subtract",
     * )
     * @var integer
     */
    private $subtract;

    /**
     * @OA\Property(
     *     description="Minimum",
     * )
     * @var integer
     */
    private $minimum;

    /**
     * @OA\Property(
     *     description="Maximum",
     * )
     * @var integer
     */
    private $maximum;

    /**
     * @OA\Property(
     *     description="Cost",
     * )
     * @var double
     */
    private $cost;

    /**
     * @OA\Property(
     *     description="Call to Order",
     * )
     * @var integer
     */
    private $call_to_order;

    /**
     * @OA\Property(
     *     description="product_type_id",
     * )
     * @var integer
     */
    private  $product_type_id;

    /**
     * @OA\Property(
     *     description="settings",
     * )
     * @var string
     */
    private  $settings;

    /**
     * @OA\Property(
     *     description="date_added",
     * )
     * @var string
     */
    private  $date_added;

    /**
     * @OA\Property(
     *     description="date_modified",
     * )
     * @var string
     */
    private  $date_modified;

    /**
     * @OA\Property(
     *     description="date_deleted",
     * )
     * @var string
     */
    private  $date_deleted;

    /**
     * @OA\Property(
     *     description="Stage Id",
     * )
     * @var string
     */
    private  $stage_id;

    /**
     * @OA\Property(
     *     description="ID",
     * )
     * @var integer
     */
    private  $id;

    /**
     * @OA\Property(
     *     description="Language ID",
     * )
     * @var integer
     */
    private  $language_id;

    /**
     * @OA\Property(
     *     description="name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Meta Keywords",
     * )
     * @var string
     */
    private $meta_keywords;

    /**
     * @OA\Property(
     *     description="Meta Description",
     * )
     * @var string
     */
    private $meta_description;

    /**
     * @OA\Property(
     *     description="description",
     * )
     * @var string
     */
    private $description;

    /**
     * @OA\Property(
     *     description="blurb",
     * )
     * @var string
     */
    private $blurb;

    /**
     * @OA\Property(
     *     description="Store Id",
     * )
     * @var integer
     */
    private $store_id;

    /**
     * @OA\Property(
     *     description="title",
     * )
     * @var string
     */
    private  $title;

    /**
     * @OA\Property(
     *     description="Unit",
     * )
     * @var string
     */
    private  $unit;

    /**
     * @OA\Property(
     *     description="Manufacturer Name",
     * )
     * @var string
     */
    private $manufacturer;

    /**
     * @OA\Property(
     *     description="Stock status",
     * )
     * @var string
     */
    private $stock_status;

    /**
     * @OA\Property(
     *     description="Length class Name",
     * )
     * @var string
     */
    private  $length_class_name;

    /**
     * @OA\Property(
     *     description="Rating",
     * )
     * @var double
     */
    private  $rating;

    /**
     * @OA\Property(
     *     description="Final price",
     * )
     * @var double
     */
    private $final_price;

    /**
     * @OA\Property(
     *     description="Thumb url",
     * )
     * @var string
     */
    private $thumbnail;

    /**
     * @OA\Property(
     *     description="Is Special product",
     * )
     * @var boolean
     */
    private $special;

    /**
     * @OA\Property(
     *     description="Discounts",
     *     @OA\Items()
     * )
     * @var array
     */
    //TODO: Add items descriptions
    private $discounts;

    /**
     * @OA\Property(
     *     description="Product Price",
     * )
     * @var double
     */
    private $product_price;

    /**
     * @OA\Property(
     *     description="Stock",
     * )
     * @var integer
     */
    private $stock;

    /**
     * @OA\Property(
     *     description="Options",
     *     @OA\Items(
     *     ref="#/components/schemas/OptionModel"
     * )
     * )
     * @var array
     */
    //TODO: Add items descriptions
    private $options;

    /**
     * @OA\Property(
     *     description="text_starts",
     * )
     * @var string
     */
    private $text_stars;
    //TODO: What different between text_starts and stars???

    /**
     * @OA\Property(
     *     description="Starts",
     * )
     * @var string
     */
    private $stars;

    /**
     * @OA\Property(
     *     description="Average",
     * )
     * @var integer
     */
    private $average;

    /**
     * @OA\Property(
     *     description="List of tags",
     *     @OA\Items()
     * )
     * @var array
     */
    //TODO: Add items descriptions
    private $tags;

}
