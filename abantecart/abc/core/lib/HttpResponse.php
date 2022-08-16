<?php

namespace abc\core\lib;

class HttpResponse
{
    private $headers = [];
    private $body = [];

    /**
     * @param array $headers
     * @param array $body
     */
    public function __construct(array $headers, array $body)
    {
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }


}
