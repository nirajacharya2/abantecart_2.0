<?php

namespace abc\core\lib;

class MailApiResponse
{
    /**
     * @var bool $result
     */
    public $result;

    /**
     * @var int $status_code
     */
    public $status_code;

    /**
     * @var array $data
     */
    public $data = [];

    /**
     * MailApiResponse constructor.
     *
     * @param bool  $result
     * @param int   $status_code
     * @param array $data
     */
    public function __construct(bool $result, int $status_code, array $data)
    {
        $this->result = $result;
        $this->status_code = $status_code;
        $this->data = $data;
    }

}