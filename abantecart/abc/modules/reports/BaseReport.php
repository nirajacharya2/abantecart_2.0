<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\modules\reports;

use abc\core\ABC;
use koolreport\KoolReport;

/**
 * Class BaseReport
 *
 * @package abc\models
 */
class BaseReport extends KoolReport
{
    protected $db_config;

    public function settings()
    {
        $db_config = ABC::env('DATABASES')[ABC::env('DB_CURRENT_DRIVER')];
        $this->db_config = $db_config;

        $output = [
            "dataSources" => [
                $db_config['DB_NAME'] => [
                    "connectionString" => $db_config['DB_DRIVER'].":host=".$db_config['DB_HOST'].";dbname=".$db_config['DB_NAME'],
                    "username"         => $db_config['DB_USER'],
                    "password"         => $db_config['DB_PASSWORD'],
                    "charset"          => $db_config['DB_CHARSET'],
                ],
            ],
            "assets" => [
                           "path" => ABC::env('DIR_PUBLIC').'vendor'.DS.'koolreport'.DS ,
                           "url"  => ABC::env('HTTPS_SERVER')."vendor/koolreport"
                       ]

        ];
        return $output;
    }
}
