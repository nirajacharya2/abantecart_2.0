<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\engine\Registry;
use H;

/**
 * Class AOrderStatus
 *
 * @property ADB                    $db
 * @property \abc\core\lib\AbcCache $cache
 */
class AOrderStatus
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var array
     */
    protected $base_statuses = [
        0  => 'incomplete',
        1  => 'pending',
        2  => 'processing',
        3  => 'shipped',
        7  => 'canceled',
        5  => 'completed',
        8  => 'denied',
        9  => 'canceled_reversal',
        10 => 'failed',
        11 => 'refunded',
        12 => 'reversed',
        13 => 'chargeback',
        14 => 'canceled_by_customer',
    ];
    /**
     * @var array
     */
    protected $statuses = [];

    /**
     * AOrderStatus constructor.
     *
     * @param Registry $registry
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct($registry = null)
    {
        $this->registry = $registry ? $registry : Registry::getInstance();
        $this->statuses = $this->base_statuses;

        //todo add cache
        $cache_key = 'localization.order_status.list';
        $order_statuses = $this->cache->get($cache_key);
        if ($order_statuses === false) {
            $order_statuses = $this->db->query("SELECT * FROM ".$this->db->table_name('order_statuses'));
            foreach ($order_statuses->rows as $s) {
                if (!isset($this->statuses[$s['order_status_id']])) {
                    $this->statuses[$s['order_status_id']] = $s['status_text_id'];
                }
            }
            $this->cache->put($cache_key, $this->statuses);
        }
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    /**
     * @param int $order_status_id
     * @param string $status_text_id
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function addStatus($order_status_id, $status_text_id)
    {
        $order_status_id = (int)$order_status_id;
        //preformat text_id at first
        $status_text_id = H::preformatTextID($status_text_id);

        if (in_array($order_status_id, array_keys($this->statuses)) || in_array($status_text_id, $this->statuses)) {
            $error_text = 'Error: Cannot add new order status with id '.$order_status_id.' and text id '.$status_text_id
                .' into AOrderStatus class.';
            $e = new AError($error_text);
            $e->toLog()->toDebug();
            return false;
        }

        if (!$status_text_id) {
            $error_text = 'Error: Cannot add new order status with id '.$order_status_id.' and empty text id';
            $e = new AError($error_text);
            $e->toLog()->toDebug();
            return false;
        }

        $this->statuses[$order_status_id] = $status_text_id;
        return true;
    }

    /**
     * @param int $order_status_text_id
     *
     * @return int
     */
    public function getStatusByTextId($order_status_text_id)
    {
        $flipped = array_flip($this->statuses);
        return $flipped[$order_status_text_id];
    }

    /**
     * @param int $order_status_id
     *
     * @return string
     */
    public function getStatusById($order_status_id)
    {
        return $this->statuses[$order_status_id];
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @return array
     */
    public function getBaseStatuses()
    {
        return $this->base_statuses;
    }

}
