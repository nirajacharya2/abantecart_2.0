<?php

use abc\core\lib\ApiErrorResponse;
use OpenApi\Annotations as OA;

/**
 * Class AccountRequestModel.
 *
 * @OA\Schema (
 *     description="Account Request",
 *     title="Account Request model",
 *     schema="accountRequestModel"
 * )
 */
class AccountRequestsModel
{
}

/**
 * Class AccountResponseModel.
 *
 * @OA\Schema (
 *     description="Account data",
 *     title="Response model",
 *     schema="responseModel"
 * )
 */
class AccountResponseModel
{
    /**
     * @OA\Property(
     *     description="Unique user identificator",
     * )
     * @var int
     */
    private $customer_id;

    /**
     * @OA\Property(
     *     description="Title of user info page",
     * )
     *
     * @var string
     */
    private $title;

    /**
     * @OA\Property(
     *     description="Firstname of user",
     * )
     *
     * @var string
     */
    private $firstname;

    /**
     * @OA\Property(
     *     description="Lastname of user",
     * )
     *
     * @var string
     */
    private $lastname;

    /**
     * @OA\Property(
     *     description="Email of user",
     * )
     *
     * @var string
     */
    private $email;

    /**
     * @OA\Property(
     *     description="Url to get information about user",
     * )
     *
     * @var string
     */
    private $information;

    /**
     * @OA\Property(
     *     description="Url to get history of user",
     * )
     *
     * @var string
     */
    private $history;

    /**
     * @OA\Property(
     *     description="Get newsletter information url",
     * )
     *
     * @var string
     */
    private $newsletter;

}


/**
 * Class AccountErrorModel.
 *
 * @OA\Schema (
 *     description="Account Error Response",
 *     title="Account Error Response model",
 *     schema="accountErrorModel"
 * )
 */
class AccountErrorModel extends ApiErrorResponse
{
}
