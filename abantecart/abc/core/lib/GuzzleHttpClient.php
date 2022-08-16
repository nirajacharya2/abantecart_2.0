<?php

namespace abc\core\lib;

use abc\core\lib\contracts\HttpClient;
use GuzzleHttp\Client;

class GuzzleHttpClient implements HttpClient
{
    private $client;

    /**
     * Options should be associative array with options for Guzzle https://docs.guzzlephp.org/en/stable/quickstart.html
     *
     * @param array $options
     */
    public function __construct(?array $options = [])
    {
        if (!$options) {
            $options = [];
        }
        $this->client = new Client($options);
    }

    public function get(string $url, array $options = []): HttpResponse
    {
        $response = $this->client->get($url, $options);
        return new HttpResponse($response->getHeaders(), json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function post(string $url, array $options = []): HttpResponse
    {
        $response = $this->client->post($url, $options)->getBody();
        return new HttpResponse($response->getHeaders(), json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function put(string $url, array $options = []): HttpResponse
    {
        $response = $this->client->put($url, $options)->getBody();
        return new HttpResponse($response->getHeaders(), json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function delete(string $url, array $options = []): HttpResponse
    {
        $response = $this->client->delete($url, $options)->getBody();
        return new HttpResponse($response->getHeaders(), json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

}
