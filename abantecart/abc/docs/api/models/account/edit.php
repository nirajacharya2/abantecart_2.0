<?php

use abc\core\lib\ApiSuccessResponse;


/**
 * Class AccountEditRequestModel.
 *
 * @OA\Schema (
 *     description="Account Edit Request",
 *     title="Account Edit Request model",
 *     schema="accountEditRequestModel"
 * )
 */
class AccountEditRequestModel
{
    /**
     * @OA\Property(
     *     description="Customer’s first name (32 characters limit)",
     * )
     * @var string
     */
    private $firstname;

    /**
     * @OA\Property(
     *     description="Customer’s lastname (32 characters limit)",
     * )
     * @var string
     */
    private $lastname;


    /**
     * @OA\Property(
     *     description="Customer’s email address (96 characters limit)",
     * )
     * @var string
     */
    private $email;

    /**
     * @OA\Property(
     *     description="Customer’s telephone number (32 characters limit)",
     * )
     * @var string
     */
    private $telephone;

    /**
     * @OA\Property(
     *     description="Customer’s fax number (32 characters limit)",
     * )
     * @var string
     */
    private $fax;

    /**
     * @OA\Property(
     *     description="Selection to receive a newsletter (values: 1 to receive and 0 to skip)",
     * )
     * @var int
     */
    private $newsletter;
}

/**
 * Class EditStep1SuccessModel.
 *
 * @OA\Schema (
 *     description="Edit Step 1 Success Response",
 *     title="Edit Step 1 Success Response model",
 *     schema="EditStep1SuccessModel"
 * )
 */
class EditStep1SuccessModel
{

}

/**
 * Class EditStep2SuccessModel.
 *
 * @OA\Schema (
 *     description="Edit Step 2 Success Response",
 *     title="Edit Step 2 Success Response model",
 *     schema="EditStep2SuccessModel"
 * )
 */

class EditStep2SuccessModel extends ApiSuccessResponse
{
    /**
     * @OA\Property(
     *     description="Text message for customer after registration",
     * )
     *
     * @var string
     */
    private $text_message;

}

/**
 * Class EditFieldsModel.
 *
 * @OA\Schema (
 *     description="Edit Fields Model",
 *     title="Edit Fields Model",
 *     schema="EditFieldsModel"
 * )
 */
class EditFieldsModel
{
    /**
     *  @OA\Property(
     *   ref="#/components/schemas/EditAccountField"
     *   ),
     * @var object
     */
    private $firstname;
    /**
     *  @OA\Property(
     *   ref="#/components/schemas/EditAccountField"
     *   ),
     * @var object
     */
    private $lastname;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/EditAccountField"
     *   ),
     * @var object
     */
    private $email;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/EditAccountField"
     *   ),
     * @var object
     */
    private $telephone;

  /**
     *  @OA\Property(
     *   ref="#/components/schemas/EditAccountField"
     *   ),
     * @var object
     */
    private $fax;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/EditAccountField"
     *   ),
     * @var object
     */
    private $newsletter;


}

/**
 * Class EditAccountField.
 *
 * @OA\Schema (
 *     description="Edit Account Field",
 *     title="Crea teAccount Field model",
 *     schema="EditAccountField"
 * )
 */
class EditAccountField
{
    /**
     * @OA\Property(
     *     description="Field type",
     * )
     * @var string
     */
    private $type;

    /**
     * @OA\Property(
     *     description="Field name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Field value",
     * )
     * @var string
     */
    private $value;

    /**
     * @OA\Property(
     *     description="Field reuiqured",
     * )
     * @var boolean
     */
    private $required;

    /**
     * @OA\Property(
     *     description="Field error",
     * )
     * @var string
     */
    private $error;

    /**
     * @OA\Property(
     *     description="Field options",
     *
     * )
     * @var object
     */
    private $options;

    /**
     * @OA\Property(
     *     description="Field checked for checkbox",
     * )
     * @var boolean
     */
    private $checked;

}
