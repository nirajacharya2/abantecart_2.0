<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\engine;

use abc\core\ABC;
use abc\core\lib\ARest;

/**
 * @OA\Info(
 *     title="Abantecart 2.0 REST API",
 *     version="0.1",
 *     description="REST API for Abantecart version 2.0"
 * )
 * @OA\SecurityScheme(
 *      securityScheme="tokenAuth",
 *      in="header",
 *      name="Token Auth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="token",
 * ),
 * @OA\SecurityScheme(
 *      securityScheme="apiKey",
 *      in="header",
 *      name="X-App-Api-key",
 *      type="apiKey",
 * ),
 * @OA\Tag(
 *     name="Account",
 *     description="Manage account: create, update, delete"
 * )
 * @OA\Tag(
 *     name="Checkout",
 *     description="Manage checkout: create, update, delete"
 * )
 * @OA\Tag(
 *     name="Common",
 *     description="Manage checkout: create, update, delete"
 * )
 * @OA\Tag(
 *     name="Product",
 *     description="Product API: create, update, delete"
 * )
 */
class AControllerAPI extends AController
{
    public $rest;
    public $error = [];
    public $data = [];

    public function __construct( $registry, $instance_id, $controller, $parent_controller = '' )
    {
        parent::__construct( $registry, $instance_id, $controller, $parent_controller );
        $this->rest = new ARest;
    }

    public function main()
    {
        //disable api only SF when maintenance mode is ON
        if ($this->config->get('config_maintenance') && !ABC::env('IS_ADMIN')) {
            $this->rest->setResponseData([
                'error_code' => 503,
                'error_text' => 'Maintenance mode'
            ]);
            $this->rest->sendResponse(503);
            return null;
        }
        //call methods based on REST re	quest type
        switch ($this->rest->getRequestMethod()) {
            case 'get':
                return $this->get();
                break;
            case 'post':
                return $this->post();
                break;
            case 'put':
                return $this->put();
                break;
            case 'delete':
                return $this->delete();
                break;
            default:
                $this->rest->sendResponse( 405 );

                return null;
                break;
        }
    }

    //Abstract Methods
    public function get()
    {
        $this->rest->sendResponse( 405 );
        return null;
    }

    public function post()
    {
        $this->rest->sendResponse( 405 );
        return null;
    }

    public function put()
    {
        $this->rest->sendResponse( 405 );
        return null;
    }

    public function delete()
    {
        $this->rest->sendResponse( 405 );
        return null;
    }

    public function mapErrorsAsArray($errors): array {
        $result = [];
        foreach ($errors as $key=> $value) {
            $result[] = [
                'id'          => $key,
                'description' => $value
            ];
        }
        return $result;
    }
}
