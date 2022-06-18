<?php

namespace abc\core\lib;

/**
 * Class ApiSuccessResponse.
 *
 * @OA\Schema (
 *     description="Success Response",
 *     title="Success Response model",
 *     schema="ApiSuccessResponse"
 * )
 */
class ApiSuccessResponse
{
    /**
     * @OA\Property(
     *     description="Request result",
     * )
     * @var int
     */
    private $status;

    /**
     * @OA\Property(
     *     description="Text description of request result",
     * )
     *
     * @var string
     */
    private $success;
}
