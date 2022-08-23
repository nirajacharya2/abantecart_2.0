<?php

use abc\core\lib\ApiSuccessResponse;
use abc\docs\api\models\SecureRequestModel;


/**
 * Class AccountCreateRequestModel.
 *
 * @OA\Schema (
 *     description="Account Create Request",
 *     title="Account Create Request model",
 *     schema="accountCreateRequestModel"
 * )
 */
class AccountCreateRequestModel
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
     *     description="Customer’s loginname (Unique login name between 5 and 64 characters. Required If 'Require Login Name' is enabled (default))",
     * )
     * @var string
     */
    private $loginname;

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
     *     description="Company name (optional) (32 characters limit)",
     * )
     * @var string
     */
    private $company;

    /**
     * @OA\Property(
     *     description="Street Address (128 characters limit)",
     * )
     * @var string
     */
    private $address_1;

    /**
     * @OA\Property(
     *     description="Apartment #, Suite #, etc part of address (128 characters limit)",
     * )
     * @var string
     */
    private $address_2;

    /**
     * @OA\Property(
     *     description="Zip code or Postal code (10 characters limit)",
     * )
     * @var string
     */
    private $postcode;

    /**
     * @OA\Property(
     *     description="City or town name (128 characters limit)",
     * )
     * @var string
     */
    private $city;

    /**
     * @OA\Property(
     *     description="ID of the country based on provided list of countries",
     * )
     * @var int
     */
    private $country_id;

    /**
     * @OA\Property(
     *     description="ID for the local zone within a country. This is usually a state or region (This ID can be received with separate request based on selected country ID)",
     * )
     * @var int
     */
    private $zone_id;

    /**
     * @OA\Property(
     *     description="Password to access login to the account",
     * )
     *
     * @var string
     */
    private $password;

    /**
     * @OA\Property(
     *     description="Confirmation with the same password as in password field",
     * )
     *
     * @var string
     */
    private $confirm;

    /**
     * @OA\Property(
     *     description="This is a confirmation that user agrees to the site user agreement (This is configured in the admin and can be possibly enabled or disabled. Values: 1 agree or 0 decline)",
     * )
     * @var int
     */
    private $agree;

    /**
     * @OA\Property(
     *     description="Selection to receive a newsletter (values: 1 to receive and 0 to skip)",
     * )
     * @var int
     */
    private $newsletter;
}

/**
 * Class CreateStep1SuccessModel.
 *
 * @OA\Schema (
 *     description="Create Step 1 Success Response",
 *     title="Create Step 1 Success Response model",
 *     schema="CreateStep1SuccessModel"
 * )
 */
class CreateStep1SuccessModel
{

}

/**
 * Class CreateStep2SuccessModel.
 *
 * @OA\Schema (
 *     description="Create Step 2 Success Response",
 *     title="Create Step 2 Success Response model",
 *     schema="CreateStep2SuccessModel"
 * )
 */

class CreateStep2SuccessModel extends ApiSuccessResponse
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
 * Class CreateFieldsModel.
 *
 * @OA\Schema (
 *     description="Create Fields Model",
 *     title="Create Fields Model",
 *     schema="CreateFieldsModel"
 * )
 */
class CreateFieldsModel
{

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $loginname;
    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $firstname;
    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $lastname;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $email;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $telephone;

  /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $fax;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $company;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $address_1;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $address_2;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $city;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $postcode;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $country_id;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $zone_id;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $password;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $confirm;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $newsletter;

    /**
     *  @OA\Property(
     *   ref="#/components/schemas/CreateAccountField"
     *   ),
     * @var object
     */
    private $agree;

}

/**
 * Class CreateAccountField.
 *
 * @OA\Schema (
 *     description="Create Account Field",
 *     title="Crea teAccount Field model",
 *     schema="CreateAccountField"
 * )
 */
class CreateAccountField
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
