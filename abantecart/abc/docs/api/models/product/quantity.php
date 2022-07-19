<?php

/**
 * Class QuantityResponseModel.
 *
 * @OA\Schema (
 *     schema="QuantityResponseModel"
 * )
 */

class QuantityResponseModel
{
    /**
     * @OA\Property(
     *     description="Quantity",
     * )
     * @var integer
     */
    private $quantity;

    /**
     * @OA\Property(
     *     description="Stock Status",
     * )
     * @var string
     */
    private $stock_status;

    /**
     * @OA\Property(
     *     description="Option value quantities",
     *     @OA\Items(
     *     ref="#/components/schemas/OptionValusQuantityModel"
     *  )
     * )
     *
     * @var array
     */
    private $option_value_quantities;
}


/**
 * Class OptionValusQuantityModel.
 *
 * @OA\Schema (
 *     schema="OptionValusQuantityModel"
 * )
 */

class OptionValusQuantityModel
{
    /**
     * @OA\Property(
     *     description="Product option value id",
     * )
     * @var integer
     */
    private $product_option_value_id;

    /**
     * @OA\Property(
     *     description="Quantity",
     * )
     * @var integer
     */
    private $quantity;
}
