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
            case 'post':
                return $this->post();
            case 'put':
                return $this->put();
            case 'delete':
                return $this->delete();
            default:
                $this->rest->sendResponse(405);
                return null;
        }
    }

    private function isLoggedIn() {
        $headers = $this->request->getHeaders();
        $token = $this->rest->getRequestParam('token');
        if (!$headers || (!$headers['Authorization'] && !$token)) {
            return false;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization'])
            //deprecated way. do not send token via post!
            ?: $token;
        return !$token ? false : $this->customer->isLoggedWithToken($token);
    }
}
