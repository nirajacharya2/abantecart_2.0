<?php

namespace abc\core\lib;

/**
 * Class ApiErrorResponse.
 *
 * @OA\Schema (
 *     description="Error Response",
 *     title="Error Response model",
 *     schema="ApiErrorResponse"
 * )
 */
class ApiErrorResponse
{
    /**
     * @OA\Property(
     *     description="Request result",
     * )
     * @var int
     */
    private $error_code;

    /**
     * @OA\Property(
     *     description="Title of request result",
     * )
     *
     * @var string
     */
    private $error_title;

    /**
     * @OA\Property(
     *     description="Text description of request result",
     * )
     *
     * @var string
     */
    private $error_text;

    /**
     * @OA\Property(
     *     description="Text description of request result",
     * )
     *
     * @var object
     */
    private $errors;
}
