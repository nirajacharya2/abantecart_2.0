<?php

namespace abc\core\lib;

use abc\core\lib\contracts\ApiClient;

/**
 * Class CategoryApiClient
 *
 * @package abc\core\lib
 * Comment for search     -    a/catalog/category
 */
class CategoryApiClient extends BaseApiClient implements ApiClient
{
    /**
     * @param array $data
     *
     * @return bool|null
     */
    public function create(array $data)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        return $this->sendRequest('put', 'catalog/category', $data);
    }

    public function get(array $by)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        return $this->sendRequest('get', 'catalog/category', $by);
    }

    public function update(array $by, array $data)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        $data = array_merge($data, $by);
        return $this->sendRequest('post', 'catalog/category', $data);
    }

    public function delete(array $by)
    {
        if (!$this->token) {
            $this->requestToken();
        }
        return $this->sendRequest('delete', 'catalog/category', $by);
    }
}
