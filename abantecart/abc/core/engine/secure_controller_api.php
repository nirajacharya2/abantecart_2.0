<?php
namespace abc\core\engine;

use abc\core\lib\ARest;

class ASecureControllerAPI extends AControllerAPI
{
    public function main()
    {
        if (!$this->isLoggedIn()) {
            $this->rest->setResponseData([
                'error_code' => 401,
                'error_title' => 'Unauthorized',
                'error_text' => 'Not logged in or Login attempt failed!'
            ]);
            $this->rest->sendResponse(401);
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
                $this->rest->sendResponse(405);

                return null;
                break;
        }
    }

    private function isLoggedIn() {
        $headers = $this->request->getHeaders();
        if (!$headers || !$headers['Authorization']) {
            return false;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$token) {
            return false;
        }
        return $this->customer->isLoggedWithToken($token);
    }
}
