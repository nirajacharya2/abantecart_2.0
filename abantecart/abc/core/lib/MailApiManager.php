<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 26/09/2018
 * Time: 09:03
 */

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\helper\AHelperUtils;
use abc\core\engine\Registry;
use Exception;


class MailApiManager
{
    //NOTE: This class is loaded in INIT for admin only
    public function __construct()
    {
        $this->registry = Registry::getInstance();
        $this->config = $this->registry->get('config');
    }


    public static function getInstance() {
        return new MailApiManager();
    }

    public function getMailDriversList()
    {
        $filter = [
            'category' => 'MailApi',
        ];
        $this->extensions = new AExtensionManager();
        $extensions = $this->extensions->getExtensionsList($filter);
        $driver_list = [];
        foreach ($extensions->rows as $ext) {
            $driver_txt_id = $ext['key'];

            if ($this->config->get($driver_txt_id.'_status') === null) {
                continue;
            }

            $classname = preg_replace('/[^a-zA-Z]/', '', $driver_txt_id);

            try {
                $driver = ABC::getObjectByAlias($classname);
                $driver_list[$driver->getProtocol()][$driver_txt_id] = $driver->getName();
            } catch (\Cake\Database\Exception $e){}

        }
        return $driver_list;
    }

    public function getCurrentMailApiDriver() {
       $driver = $this->config->get('config_mail_extension');
       $className = preg_replace('/[^a-zA-Z]/', '', $driver);
        try {
            $driver = ABC::getObjectByAlias($className);
        } catch (\Cake\Database\Exception $e){}
        return $driver;
    }
}