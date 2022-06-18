<?php
namespace abc\docs\api\models;

/**
 * Class SecureRequestModel.
 *
 * @OA\Schema (
 *     description="Secure Request",
 *     title="Secure Request Model",
 *     schema="SecureRequestModel"
 * )
 */
class SecureRequestModel
{
    /**
     * @OA\Property(
     *     description="Access token ID. This token is provided by the system after successful initial authentication",
     * )
     *
     * @var string
     */
    private $token;

    /**
     * @OA\Property(
     *     description="Unique API key that is set in the control panel",
     * )
     *
     * @var string
     */
    private $api_key;
}
