<?php

/**
 * Class ResourcesResponseModel.
 *
 * @OA\Schema (
 *     schema="ResourcesResponseModel"
 * )
 */

class ResourcesResponseModel
{
    /**
     * @OA\Property(
     *     description="Total",
     * )
     * @var integer
     */
    private $total;

    /**
     * @OA\Property(
     *     description="List of resources",
     *     @OA\Items(
     *     ref="#/components/schemas/ResourceModel"
     * )
     * )
     * @var array
     */
    private $resources;

}

/**
 * Class ResourceModel.
 *
 * @OA\Schema (
 *     schema="ResourceModel"
 * )
 */
class ResourceModel
{
    /**
     * @OA\Property(
     *     description="original",
     * )
     * @var string
     */
    private $original;

    /**
     * @OA\Property(
     *     description="thumb",
     * )
     * @var string
     */
    private $thumb;
}
