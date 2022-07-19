<?php

/**
 * Class GetProductsModel.
 *
 * @OA\Schema (
 *     schema="GetProductsModel"
 * )
 */
class GetProductsModel
{
    /**
     * @OA\Property(
     *     description="Page Number",
     * )
     * @var int
     */
    private $page;

    /**
     * @OA\Property(
     *     description="Total Pages",
     * )
     * @var int
     */
    private $total;

    /**
     * @OA\Property(
     *     description="Recors count",
     * )
     * @var int
     */
    private $records;

    /**
     * @OA\Property(
     *     description="Limit",
     * )
     * @var int
     */
    private $limit;

    /**
     * @OA\Property(
     *     description="Sort By",
     * )
     * @var string
     */
    private $sidx;

    /**
     * @OA\Property(
     *     description="Sort Order",
     * )
     * @var string
     */
    private $sord;

    /**
     * @OA\Property(
     *     description="Params",
     *     ref="#/components/schemas/ParamsModel"
     * )
     *
     * @var object
     */
    private $params;

    /**
     * @OA\Property(
     *     description="Rows list",
     *     @OA\Items(
     *     ref="#/components/schemas/FilterRowModel"
     *  )
     * )
     *
     * @var array
     */
    private $rows;
}

/**
 * Class ParamsModel.
 *
 * @OA\Schema (
 *     schema="ParamsModel"
 * )
 */
class ParamsModel
{
    /**
     * @OA\Property(
     *     description="Sort Order",
     * )
     * @var string
     */
    private $sort;

    /**
     * @OA\Property(
     *     description="Order",
     * )
     * @var string
     */
    private $order;

    /**
     * @OA\Property(
     *     description="Limit",
     * )
     * @var integer
     */
    private $limit;

    /**
     * @OA\Property(
     *     description="Start",
     * )
     * @var integer
     */
    private $start;

    /**
     * @OA\Property(
     *     description="Language Id",
     * )
     * @var integer
     */
    private $content_language_id;

    /**
     * @OA\Property(
     *     description="Filter",
     * )
     * @var object
     */
    private $filter;

    /**
     * @OA\Property(
     *     description="Sub sql filter",
     * )
     * @var string
     */
    private $subsql_filter;

}


/**
 * Class FilterRowModel.
 *
 * @OA\Schema (
 *     schema="FilterRowModel"
 * )
 */
class FilterRowModel
{
    /**
     * @OA\Property(
     *     description="Id",
     * )
     * @var integer
     */
    private $id;

    /**
     * @OA\Property(
     *     description="Cell",
     *     ref="#/components/schemas/FilterCellModel"
     * )
     *
     * @var object
     */
    private $cell;
}

/**
 * Class FilterCellModel.
 *
 * @OA\Schema (
 *     schema="FilterCellModel"
 * )
 */
class FilterCellModel
{
    /**
     * @OA\Property(
     *     description="Thumb",
     * )
     * @var string
     */
    private $thumb;

    /**
     * @OA\Property(
     *     description="Name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Description",
     * )
     * @var string
     */
    private $description;

    /**
     * @OA\Property(
     *     description="model",
     * )
     * @var string
     */
    private $model;

    /**
     * @OA\Property(
     *     description="Price",
     * )
     * @var double
     */
    private $price;

    /**
     * @OA\Property(
     *     description="Currency code",
     * )
     * @var string
     */
    private $currency_code;

    /**
     * @OA\Property(
     *     description="Rating",
     * )
     * @var string
     */
    private $rating;
}
