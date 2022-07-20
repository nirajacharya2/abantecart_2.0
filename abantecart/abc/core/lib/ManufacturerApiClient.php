<?php

namespace abc\core\lib;

use abc\core\lib\contracts\ApiClient;

class ManufacturerApiClient extends BaseApiClient implements ApiClient
{
    public function create(array $data)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        return $this->sendRequest('put', 'catalog/manufacturer', $data);
    }

    public function get(array $by)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        return $this->sendRequest('get', 'catalog/manufacturer', $by);
    }

    public function update(array $by, array $data)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        $data = array_merge($data, $by);
        return $this->sendRequest('post', 'catalog/manufacturer', $data);
    }

    public function delete(array $by)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        return $this->sendRequest('delete', 'catalog/manufacturer', $by);
    }
}
