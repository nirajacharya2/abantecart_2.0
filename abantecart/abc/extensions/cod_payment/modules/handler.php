<?php

namespace abc\extensions\cod_payment\modules;

use abc\core\engine\Registry;
use abc\core\lib\PaymentHandlerInterface;

class PaymentHandler implements PaymentHandlerInterface
{
    /**
     * @var array
     */
    protected $errors = [];

    public function __construct()
    {
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function validatePaymentDetails(array $data = [])
    {
        return true;
    }

    public function processPayment(array $data = [])
    {
        return true;
    }

    public function callback(array $data = [])
    {

    }

}