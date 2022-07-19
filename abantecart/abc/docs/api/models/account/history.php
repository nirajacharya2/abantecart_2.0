<?php

/**
 * Class AccountHistoryRequestModel.
 *
 * @OA\Schema (
 *     description="Account History Request",
 *     title="Account History Request Model",
 *     schema="AccountHistoryRequestModel"
 * )
 */
class AccountHistoryRequestModel
{
    /**
     * @OA\Property(
     *     description="Page Number, default 1",
     * )
     *
     * @var int
     */
    private $page;

    /**
     * @OA\Property(
     *     description="Limit of order for display, default value of setting config_catalog_limit",
     * )
     *
     * @var int
     */
    private $limit;
}

/**
 * Class HistorySuccessModel.
 *
 * @OA\Schema (
 *     description="History Success Model",
 *     title="History Success Model",
 *     schema="HistorySuccessModel"
 * )
 */
class HistorySuccessModel
{
    /**
     * @OA\Property(
     *     description="Orders list",
     *     @OA\Items(
     *     ref="#/components/schemas/HistoryOrder"
     *  )
     * )
     *
     * @var array
     */
    private $orders;

    /**
     * @OA\Property(
     *     description="Total orders",
     * )
     *
     * @var int
     */
    private $total_orders;

    /**
     * @OA\Property(
     *     description="Page Number",
     * )
     *
     * @var int
     */
    private $page;

}


/**
 * Class HistoryOrder.
 *
 * @OA\Schema (
 *     schema="HistoryOrder"
 * )
 */
class HistoryOrder {
    /**
     * @OA\Property(
     *     description="Order Id",
     * )
     *
     * @var int
     */
    private $order_id;

    /**
     * @OA\Property(
     *     description="Order Name",
     * )
     *
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Order Status",
     * )
     *
     * @var string
     */
    private $status;

    /**
     * @OA\Property(
     *     description="Date create",
     * )
     *
     * @var string
     */
    private $date_added;

    /**
     * @OA\Property(
     *     description="Products count in order",
     * )
     *
     * @var int
     */
    private $products;

    /**
     * @OA\Property(
     *     description="Order total",
     * )
     *
     * @var double
     */
    private $total;
}
