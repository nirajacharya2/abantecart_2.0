<?php

namespace abc\core\lib;

class BaseApiClient
{
    protected $url;
    protected $port;
    protected $apiKey;
    protected $token;
    protected $username;
    protected $password;

    /**
     * ApiClient constructor.
     *
     * @param $url
     * @param $port
     * @param $apiKey
     * @param $username
     * @param $password
     */
    public function __construct($url, $port, $apiKey, $username, $password)
    {
        $this->url = $url;
        $this->port = $port;
        $this->apiKey = $apiKey;
        $this->username = $username;
        $this->password = $password;
    }

    public function requestToken()
    {
        $response = $this->sendRequest('post', 'index/login', [
            'username' => $this->username,
            'password' => $this->password,
            'api_key'  => $this->apiKey,
        ]);
        if (is_array($response) && $response['status'] == 1 && isset($response['token'])) {
            $this->token = $response['token'];
        }
    }

    public function getToken()
    {
        return $this->token;
    }

    public function sendRequest($method, $rt, $data = [])
    {
        if (empty($rt)) {
            return false;
        }

        if (!is_int(strpos($rt, 'rt'))) {
            if (!is_int(strpos($rt, 'a/'))) {
                $rt = '&rt=a/'.$rt;
            } else {
                $rt = '&rt='.$rt;
            }
        }

        if ($this->apiKey) {
            $data = array_merge($data, ['api_key' => $this->apiKey]);
        }

        if ($this->token) {
            $data = array_merge($data, ['token' => $this->token]);
        }


        $isPost = $method === 'get' ? false : true;
        if (!$isPost || $method === 'delete') {
            $api_url = $this->url.$rt.'&'.http_build_query($data);
        } else {
            $api_url = $this->url.$rt.'&api_key='.$this->apiKey.'&token='.$this->token;
        }

        $curl = curl_init($api_url);
        curl_setopt($curl, CURLOPT_PORT, $this->port);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-App-Api-Key: ' . $data['api_key']]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
        switch ($method) {
            case 'put':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data).$rt);
                break;
            case 'post':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case 'delete':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
        }

        $response = curl_exec($curl);
        if (!$response) {
            $err = new AError('Request failed: '.curl_error($curl).'('.curl_errno($curl).'). Requested URL: '.$api_url);
            try {
                $err->toLog()->toDebug();
            } catch (\ReflectionException $exception) {

            } catch (\Exception $exception) {

            }
            curl_close($curl);
            return false;
        }

        $response_data = AJson::decode($response, true);
        curl_close($curl);
        return $response_data;
    }
}
