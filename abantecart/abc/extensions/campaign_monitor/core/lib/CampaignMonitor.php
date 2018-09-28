<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 26/09/2018
 * Time: 09:08
 */

namespace abc\extensions\campaign_monitor\core\lib;

require_once (__DIR__.DS."createsendphp/csrest_subscribers.php");
require_once (__DIR__.DS."createsendphp/csrest_transactional_classicemail.php");

use abc\core\lib\AMail;
use abc\core\engine\Registry;
use abc\extensions\campaign_monitor\core\lib\createsendphp\CS_REST_Subscribers;
use abc\extensions\campaign_monitor\core\lib\createsendphp\CS_REST_Transactional_ClassicEmail;


class CampaignMonitor
{

    public function __construct()
    {
        $this->registry = Registry::getInstance();
        $this->config = $this->registry->get('config');
        $this->log = $this->registry->get('log');
    }

    public function getProtocol()
    {
        return 'mail_api';
    }

    public function getName()
    {
        return 'Campaign Monitor Api';
    }

    public function send(AMail $mail)
    {
        $userId = $this->config->get('default_campaign_monitor_userid');
        $apiToken = $this->config->get('default_campaign_monitor_apikey');

        $auth = array("api_key" => $apiToken);
        $clientId = $userId;
        $wrap = new CS_REST_Transactional_ClassicEmail($auth, $clientId);

        $attachments = [];
        foreach ($mail->getAttachments() as $attachment) {
            if (file_exists($attachment['file'])) {
                $handle = fopen($attachment['file'], 'r');
                $content = fread($handle, filesize($attachment['file']));

                fclose($handle);
                $attachments[] = [
                    "Name"    => $attachment['file'],
                    //"Type" => "image/gif",
                    "Content" => chunk_split(base64_encode($content)),
                ];
            }
        }

        $siteName = $this->config->get('store_name');

        $complex_message = [
            "From"        => $siteName." ".$mail->getFrom(),
            "ReplyTo"     => $mail->getReplyTo(),
            "Subject"     => $mail->getSubject(),
            "To"          => [
                $mail->getTo(),
            ],
            "CC"          => [],
            "BCC"         => [],
            "HTML"        => $mail->getHtml(),
            "Text"        => $mail->getText(),
            "Attachments" => $attachments,
        ];
        unset($siteName);

        $group_name = $mail->getSubject();
        $add_recipients_to_subscriber_list_ID = "";
        $options = array(
            "TrackOpens"  => true,
            "TrackClicks" => true,
            "InlineCSS"   => true,
        );
        $result = $wrap->send($complex_message, $group_name, $add_recipients_to_subscriber_list_ID, $options);
        if ($result->http_status_code === 202) {
            return true;
        } else {
            $this->log->write($result->response->Message);
            return false;
        }
    }

    public static function changeSubscriber(string $listId, array $auth, array $oldCustomer, array $customer)
    {
        $wrap = new CS_REST_Subscribers($listId, $auth);

        $customerData = [
            'EmailAddress'   => $customer['email'],
            'Name'           => $customer['firstname']." ".$customer['lastname'],
            'CustomFields'   => [
                [
                    'Key'   => 'firstname',
                    'Value' => $customer['firstname'],
                ],
                [
                    'Key'   => 'lastname',
                    'Value' => $customer['lastname'],
                ],
                [
                    'Key'   => 'approved',
                    'Value' => $customer['approved'],
                ],
                [
                    'Key'   => 'status',
                    'Value' => $customer['status'],
                ],
                [
                    'Key'   => 'customer_group_id',
                    'Value' => $customer['customer_group_id'],
                ],
            ],
            'ConsentToTrack' => 'yes',
            'Resubscribe'    => true,
        ];

        if (intval($oldCustomer['newsletter']) != intval($customer['newsletter'])
            || intval($oldCustomer['status']) != intval($customer['status'])) {
            if (intval($customer['newsletter']) == 0 || intval($customer['status']) == 0) {
                $result = $wrap->unsubscribe($oldCustomer['email']);
            } else {
                $result = $wrap->add($customerData);
            }
        } elseif (intval($customer['newsletter']) == 1 && intval($customer['status']) == 1) {
            //Update only enabled newsletter
            $result = $wrap->update($oldCustomer['email'], $customerData);
        }
        if (!$result) {
            return false;
        }
        return true;
    }

}