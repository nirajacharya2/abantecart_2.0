<?php

use abc\core\lib\ApiErrorResponse;
use abc\core\lib\ApiSuccessResponse;

/**
 * Class LoginRequestModel.
 *
 * @OA\Schema (
 *     description="Login Request",
 *     title="Login Request model",
 *     schema="loginRequestModel"
 * )
 */
class LoginRequestsModel
{
    /**
     * @OA\Property(
     *     description="Email address or loginname registered in customer account",
     * )
     * @var string
     */
    private $loginname;

    /**
     * @OA\Property(
     *     description="Email address or loginname registered in customer account",
     * )
     * @var string
     */
    private $email;

    /**
     * @OA\Property(
     *     description="Customer’s password	",
     * )
     *
     * @var string
     */
    private $password;
}

/**
 * Class LoginSuccessModel.
 *
 * @OA\Schema (
 *     description="Login Success Response",
 *     title="Login Success Response model",
 *     schema="loginSuccessModel"
 * )
 */
class LoginSuccessModel extends ApiSuccessResponse
{
    /**
     * @OA\Property(
     *     description="Access token",
     * )
     *
     * @var string
     */
    private $token;
}

/**
 * Class LoginErrorModel.
 *
 * @OA\Schema (
 *     description="Login Error Response",
 *     title="Login Error Response model",
 *     schema="loginErrorModel"
 * )
 */
class LoginErrorModel extends ApiErrorResponse
{
}
