<?php


/**
 * Class ManufacturerModel.
 *
 * @OA\Schema (
 *     schema="ManufacturerModel"
 * )
 */
class ManufacturerModel
{
    /**
     * @OA\Property(
     *     description="Manufacturer unique id",
     * )
     * @var integer
     */
    private $manufacturer_id;

    /**
     * @OA\Property(
     *     description="UUID",
     * )
     * @var string
     */
    private $uuid;

    /**
     * @OA\Property(
     *     description="name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Sort Order",
     * )
     * @var integer
     */
    private $sort_order;

    /**
     * @OA\Property(
     *     description="Date Added",
     * )
     * @var string
     */
    private $date_added;

    /**
     * @OA\Property(
     *     description="Date Modified",
     * )
     * @var string
     */
    private $date_modified;

    /**
     * @OA\Property(
     *     description="Date deleted",
     * )
     * @var string
     */
    private $date_deleted;

    /**
     * @OA\Property(
     *     description="Stage Id",
     * )
     * @var integer
     */
    private $stage_id;


    /**
     * @OA\Property(
     *     description="Id",
     * )
     * @var integer
     */
    private $id;

    /**
     * @OA\Property(
     *     description="Store Id",
     * )
     * @var integer
     */
    private $store_id;
}
