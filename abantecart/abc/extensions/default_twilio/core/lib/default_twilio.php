<?php
//namespace abc\core\extension;

use abc\core\engine\AForm;
use abc\core\engine\Registry;

final class DefaultTwilio
{
    public $errors = [];
    private $registry;
    private $config;
    private $sender;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
        $this->registry->get('language')->load('default_twilio/default_twilio');
        $this->config = $this->registry->get('config');
        try {
            require_once('Services/Twilio.php');
            require_once('Twilio/autoload.php');
            $AccountSid = $this->config->get('default_twilio_username');
            $AuthToken = $this->config->get('default_twilio_token');

            $this->sender = new \Twilio\Rest\Client($AccountSid, $AuthToken);

        } catch (Exception $e) {
            if ($this->config->get('default_twilio_logging')) {
                $this->registry->get('log')->write('Twilio error: '.$e->getMessage().'. Error Code:'.$e->getCode());
            }
        }
    }

    public function getProtocol()
    {
        return 'sms';
    }

    public function getProtocolTitle()
    {
        return $this->registry->get('language')->get('default_twilio_protocol_title');
    }

    public function getName()
    {
        return 'Twilio';
    }

    public function send($to, $text)
    {
        if (!$to || !$text) {
            return null;
        }
        $to = '+'.ltrim($to, '+');
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        try {
            $from = $this->config->get('default_twilio_sender_phone');
            $from = $from ? '+'.ltrim($from, '+') : '';

            $this->sender->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $text,
                ]
            );
            $result = true;
        } catch (Exception $e) {
            if ($this->config->get('default_twilio_logging')) {
                $this->registry->get('log')->write('Twilio error: '.$e->getMessage().'. Error Code:'.$e->getCode());
            }
            $result = false;
        }

        return $result;
    }

    public function sendFew($to, $text)
    {
        foreach ($to as $uri) {
            $this->send($uri, $text);
        }
    }

    public function validateURI($uri)
    {
        $this->errors = [];
        $uri = trim($uri);
        $uri = trim($uri, ',');

        $uris = explode(',', $uri);
        foreach ($uris as $u) {
            $u = trim($u);
            if (!$u) {
                continue;
            }
            $u = preg_replace('/[^0-9\+]/', '', $u);
            if ($u[0] != '+') {
                $u = '+'.$u;
            }
            if (!preg_match('/^\+[1-9]{1}[0-9]{3,14}$/', $u)) {
                $this->errors[] = 'Mobile number '.$u.' is not valid!';
            }
        }

        if ($this->errors) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Function builds form element for storefront side (customer account page)
     *
     * @param AForm $form
     * @param string $value
     *
     * @return object
     * @throws \abc\core\lib\AException
     */
    public function getURIField($form, $value = '')
    {
        $this->registry->get('language')->load('default_twilio/default_twilio');

        return $form->getFieldHtml(
            [
                'type'       => 'phone',
                'name'       => 'sms',
                'value'      => $value,
                'label_text' => $this->registry->get('language')->get('entry_sms'),
            ]);
    }
}