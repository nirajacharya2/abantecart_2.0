<?php

namespace abc\core\lib\contracts;

use abc\core\lib\AMail;

interface MailApi
{
    public function getProtocol() :string;
    public function getName() :string;
    public function send(AMail $mail) :MailApiResponse;
}