<?php

namespace abc\core\lib\contracts;

use abc\core\lib\HttpResponse;

interface HttpClient
{
    public function __construct(array $options = []);

    public function get(string $url, array $options = []): HttpResponse;

    public function post(string $url, array $options = []): HttpResponse;

    public function put(string $url, array $options = []): HttpResponse;

    public function delete(string $url, array $options = []): HttpResponse;
}
