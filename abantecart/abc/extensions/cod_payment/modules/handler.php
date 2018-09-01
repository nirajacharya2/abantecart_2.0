<?php

namespace abc\extensions\cod_payment\modules;

use abc\core\engine\Registry;
use abc\core\lib\CheckoutBase;
use abc\core\lib\PaymentHandlerInterface;

class PaymentHandler implements PaymentHandlerInterface
{
    /**
     * @var array
     */
    protected $errors = [];

    public function __construct(Registry $registry, CheckoutBase $checkout)
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