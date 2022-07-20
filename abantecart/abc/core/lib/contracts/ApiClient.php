<?php

namespace abc\core\lib\contracts;

interface ApiClient
{
    public function create(array $data);

    public function get(array $by);

    public function update(array $by, array $data);

    public function delete(array $by);
}
