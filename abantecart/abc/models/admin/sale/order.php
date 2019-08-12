<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Model;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use abc\models\order\Order;
use abc\models\order\OrderProduct;
use abc\models\user\User;
use abc\modules\events\ABaseEvent;
use H;

/**
 * Class ModelSaleOrder
 *
 * @property ModelLocalisationZone    $model_localisation_zone
 * @property ModelLocalisationCountry $model_localisation_country
 * @property ModelCatalogProduct      $model_catalog_product
 */
class ModelSaleOrder extends Model
{

    /**
     * @param int $order_id
     * @param array $data
     *
     * @throws \abc\core\lib\AException
     * @throws \ReflectionException
     */
    public function addOrderHistory($order_id, $data)
    {
        $this->db->query("UPDATE `".$this->db->table_name("orders")."`
                            SET order_status_id = '".(int)$data['order_status_id']."',
                                date_modified = NOW()
                            WHERE order_id = '".(int)$order_id."'");

        if ($data['append']) {
            $this->db->query("INSERT INTO ".$this->db->table_name("order_history")."
                                SET order_id = '".(int)$order_id."',
                                    order_status_id = '".(int)$data['order_status_id']."',
                                    notify = '".(isset($data['notify']) ? (int)$data['notify'] : 0)."',
                                    COMMENT = '".$this->db->escape(strip_tags($data['comment']))."',
                                    date_added = NOW()");
        }

        /*
         * Send Email with merchant comment.
         * Note IM-notification not needed here.
         * */
        if ($data['notify']) {
            $order_query = $this->db->query("SELECT *, os.name AS status
                                            FROM `".$this->db->table_name("orders")."` o
                                            LEFT JOIN ".$this->db->table_name("order_status_descriptions")." os 
                                                ON (o.order_status_id = os.order_status_id AND os.language_id = o.language_id)
                                            LEFT JOIN ".$this->db->table_name("languages")." l 
                                                ON (o.language_id = l.language_id)
                                            WHERE o.order_id = '".(int)$order_id."'");

            if ($order_query->num_rows) {
                //load language specific for the order in admin section
                $language = new ALanguage(Registry::getInstance(), $order_query->row['code'], 1);
                $language->load($order_query->row['filename']);
                $language->load('mail/order');

                $this->load->model('setting/store');

                $subject = sprintf($language->get('text_subject'), $order_query->row['store_name'], $order_id);

                $message = $language->get('text_order').' '.$order_id."\n";
                $message .= $language->get('text_date_added').' '.H::dateISO2Display($order_query->row['date_added'], $language->get('date_format_short'))."\n\n";
                $message .= $language->get('text_order_status')."\n\n";
                $message .= $order_query->row['status']."\n\n";
                //send link to order only for registered customers
                if ($order_query->row['customer_id']) {
                    $message .= $language->get('text_invoice')."\n";
                    $message .= html_entity_decode(
                                            $order_query->row['store_url'].'index.php?rt=account/invoice&order_id='.$order_id,
                                            ENT_QUOTES,
                                            ABC::env('APP_CHARSET')
                    )."\n\n";
                } //give link on order page for quest
                elseif ($this->config->get('config_guest_checkout') && $order_query->row['email']) {
                    $enc = new AEncryption($this->config->get('encryption_key'));
                    $order_token = $enc->encrypt($order_id.'::'.$order_query->row['email']);
                    $message .= $language->get('text_invoice')."\n";
                    $message .= html_entity_decode(
                                            $order_query->row['store_url'].'index.php?rt=account/invoice&ot='.$order_token,
                                            ENT_QUOTES,
                                            ABC::env('APP_CHARSET')
                    )."\n\n";
                }

                if ($data['comment']) {
                    $message .= $language->get('text_comment')."\n\n";
                    $message .= strip_tags(html_entity_decode($data['comment'], ENT_QUOTES, ABC::env('APP_CHARSET')))."\n\n";
                }

                $message .= $language->get('text_footer');

                if ($this->dcrypt->active) {
                    $customer_email = $this->dcrypt->decrypt_field(
                                                        $order_query->row['email'],
                                                        $order_query->row['key_id']
                    );
                } else {
                    $customer_email = $order_query->row['email'];
                }

                $mail = new AMail($this->config);
                $mail->setTo($customer_email);
                $mail->setFrom($this->config->get('store_main_email'));
                $mail->setSender($order_query->row['store_name']);
                $mail->setSubject($subject);
                $mail->setText(html_entity_decode($message, ENT_QUOTES, ABC::env('APP_CHARSET')));
                $mail->send();

                //send IMs except emails.
                //TODO: add notifications for guest checkout
                $language->load('common/im');
                $invoice_url = $order_query->row['store_url'].'index.php?rt=account/invoice&order_id='.$order_id;
                //disable email protocol to prevent duplicates emails
                $this->im->removeProtocol('email');

                if ($order_query->row['customer_id']) {
                    $message_arr = [
                        0 => [
                            'message' => sprintf($language->get('im_order_update_text_to_customer'),
                                                $invoice_url,
                                                $order_id,
                                                html_entity_decode($order_query->row['store_url'].'index.php?rt=account/account')),
                        ],
                    ];
                    $this->im->sendToCustomer($order_query->row['customer_id'], 'order_update', $message_arr);
                } else {
                    $message_arr = [
                        0 => [
                            'message' => sprintf($language->get('im_order_update_text_to_guest'),
                                                $invoice_url,
                                                $order_id,
                                                html_entity_decode($invoice_url)),
                        ],
                    ];
                    $this->im->sendToGuest($order_id, $message_arr);
                }
                //turn email-protocol back
                $this->im->addProtocol('email');

            }
        }
        H::event('abc\models\admin\order@update', [new ABaseEvent($order_id, $data)]);
    }


    /**
     * @param int $product_id
     *
     * @return int|false
     * @throws \Exception
     */
    public function getOrderTotalWithProduct($product_id)
    {
        if ( ! (int)$product_id) {
            return false;
        }
        $sql = "SELECT count(DISTINCT op.order_id, op.order_product_id) AS total
                FROM ".$this->db->table_name('order_products')." op
                WHERE  op.product_id = '".(int)$product_id."'";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * @param int $order_id
     *
     * @return string
     * @throws \Exception
     */
    public function generateInvoiceId($order_id)
    {
        $query = $this->db->query("SELECT MAX(invoice_id) AS invoice_id FROM `".$this->db->table_name("orders")."`");

        if ($query->row['invoice_id'] && $query->row['invoice_id'] >= $this->config->get('starting_invoice_id')) {
            $invoice_id = (int)$query->row['invoice_id'] + 1;
        } elseif ($this->config->get('starting_invoice_id')) {
            $invoice_id = (int)$this->config->get('starting_invoice_id');
        } else {
            $invoice_id = 1;
        }

        $this->db->query(
            "UPDATE `".$this->db->table_name("orders")."`
            SET invoice_id = '".(int)$invoice_id."',
                invoice_prefix = '".$this->db->escape($this->config->get('invoice_prefix'))."',
                date_modified = NOW()
            WHERE order_id = '".(int)$order_id."'"
        );

        return $this->config->get('invoice_prefix').$invoice_id;
    }

    /**
     * @param int $order_id
     * @param int $order_product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getOrderProducts($order_id, $order_product_id = 0)
    {
        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("order_products")."
            WHERE order_id = '".(int)$order_id."'
            ".((int)$order_product_id ? " AND order_product_id='".(int)$order_product_id."'" : '')
        );
        return $query->rows;
    }

    /**
     * @param int $order_id
     * @param int $order_product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getOrderOptions($order_id, $order_product_id)
    {
        $query = $this->db->query(
            "SELECT op.*, po.element_type, po.attribute_id, po.product_option_id, pov.subtract
            FROM ".$this->db->table_name("order_options")." op
            LEFT JOIN ".$this->db->table_name("product_option_values")." pov
                ON op.product_option_value_id = pov.product_option_value_id
            LEFT JOIN ".$this->db->table_name("product_options")." po
                ON pov.product_option_id = po.product_option_id
            WHERE op.order_id = '".(int)$order_id."'
                AND op.order_product_id = '".(int)$order_product_id."'"
        );
        return $query->rows;
    }

    /**
     * @param int $order_option_id
     *
     * @return array
     * @throws \Exception
     */
    public function getOrderOption($order_option_id)
    {
        $query = $this->db->query("SELECT op.*, po.element_type, po.attribute_id, po.product_option_id, pov.subtract
                                    FROM ".$this->db->table_name("order_options")." op
                                    LEFT JOIN ".$this->db->table_name("product_option_values")." pov
                                        ON op.product_option_value_id = pov.product_option_value_id
                                    LEFT JOIN ".$this->db->table_name("product_options")." po
                                        ON pov.product_option_id = po.product_option_id
                                    WHERE op.order_option_id = '".(int)$order_option_id."'");

        return $query->row;
    }

    /**
     * @param int $order_id
     *
     * @return array
     * @throws \Exception
     */
    public function getOrderTotals($order_id)
    {
        $query = $this->db->query("SELECT *
                                    FROM ".$this->db->table_name("order_totals")."
                                    WHERE order_id = '".(int)$order_id."'
                                    ORDER BY sort_order");
        return $query->rows;
    }

    /**
     * @param int $order_id
     *
     * @return array
     * @throws \Exception
     */
    public function getOrderHistory($order_id)
    {
        $language_id = $this->language->getContentLanguageID();
        $default_language_id = $this->language->getDefaultLanguageID();

        $query = $this->db->query("SELECT oh.date_added,
                                        COALESCE( os1.name, os1.name) AS status,
                                        oh.comment,
                                        oh.notify
                                    FROM ".$this->db->table_name("order_history")." oh
                                    LEFT JOIN ".$this->db->table_name("order_status_descriptions")." os1 ON oh.order_status_id = os1.order_status_id  
                                         AND os1.language_id = '".(int)$language_id."'
                                    LEFT JOIN ".$this->db->table_name("order_status_descriptions")." os2 ON oh.order_status_id = os2.order_status_id
                                         AND os2.language_id = '".(int)$default_language_id."'
                                    WHERE oh.order_id = '".(int)$order_id."' 
                                    ORDER BY oh.date_added");

        return $query->rows;
    }

    /**
     * @param int $order_id
     *
     * @return array
     * @throws \Exception
     */
    public function getOrderDownloads($order_id)
    {
        $query = $this->db->query("SELECT op.product_id, op.name AS product_name, od.*
                                   FROM ".$this->db->table_name("order_downloads")." od
                                   LEFT JOIN ".$this->db->table_name("order_products")." op
                                        ON op.order_product_id = od.order_product_id
                                   WHERE od.order_id = '".(int)$order_id."'
                                   ORDER BY op.order_product_id, od.sort_order, od.name");
        $output = [];
        foreach ($query->rows as $row) {
            $output[$row['product_id']]['product_name'] = $row['product_name'];
            // get download_history
            $result = $this->db->query("SELECT *
                                        FROM ".$this->db->table_name("order_downloads_history")."
                                        WHERE order_id = '".(int)$order_id."' AND order_download_id = '".$row['order_download_id']."'
                                        ORDER BY `time` DESC");
            $row['download_history'] = $result->rows;

            $output[$row['product_id']]['downloads'][] = $row;
        }

        return $output;
    }


    /**
     * @param int $order_status_id
     *
     * @return int
     * @throws \Exception
     */
    public function getOrderHistoryTotalByOrderStatusId($order_status_id)
    {
        $query = $this->db->query("SELECT oh.order_id
                                    FROM ".$this->db->table_name("order_history")." oh
                                    LEFT JOIN `".$this->db->table_name("orders")."` o 
                                        ON (oh.order_id = o.order_id)
                                    WHERE oh.order_status_id = '".(int)$order_status_id."' AND o.order_status_id > '0'
                                    GROUP BY order_id");

        return $query->num_rows;
    }



    /**
     * @param array $customers_ids
     *
     * @return array
     * @throws \Exception
     */
    public function getCountOrdersByCustomerIds($customers_ids)
    {
        $customers_ids = (array)$customers_ids;
        $ids = [];
        foreach ($customers_ids as $cid) {
            $cid = (int)$cid;
            if ($cid) {
                $ids[] = $cid;
            }
        }

        if ( ! $ids) {
            return [];
        }
        $query = $this->db->query("SELECT customer_id, COUNT(*) AS total
                                    FROM `".$this->db->table_name("orders")."`
                                    WHERE customer_id IN (".implode(",", $ids).") AND order_status_id > '0'
                                    GROUP BY customer_id");
        $output = [];
        foreach ($query->rows as $row) {
            $output[$row['customer_id']] = (int)$row['total'];
        }

        return $output;
    }
}
