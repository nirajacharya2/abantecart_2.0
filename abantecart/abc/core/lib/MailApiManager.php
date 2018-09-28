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
        if (!ABC::env('IS_ADMIN')) { // forbid for non admin calls
            throw new AException (AC_ERR_LOAD, 'Error: permission denied to access class AIMManager');
        }
        $this->extensions = new AExtensionManager();
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
        $extensions = $this->extensions->getExtensionsList($filter);
        $driver_list = [];
        foreach ($extensions->rows as $ext) {
            $driver_txt_id = $ext['key'];

            if ($this->config->get($driver_txt_id.'_status') === null) {
                continue;
            }

            //NOTE! all Mail drivers MUST have class by these path
            try {
                /** @noinspection PhpIncludeInspection */
                include_once(ABC::env('DIR_APP_EXTENSIONS').$driver_txt_id.'/core/lib/'.$driver_txt_id.'.php');
            } catch (AException $e) {
            }
            $classname = preg_replace('/[^a-zA-Z]/', '', $driver_txt_id);

            if (!class_exists($classname)) {
                continue;
            }
            /**
             * @var AMailIM $driver
             */
            $driver = new $classname();
            $driver_list[$driver->getProtocol()][$driver_txt_id] = $driver->getName();
        }
        return $driver_list;
    }

    public function getCurrentMailApiDriver() {
       $driver = $this->config->get('config_mail_extension');
        //NOTE! all Mail drivers MUST have class by these path
        try {
            /** @noinspection PhpIncludeInspection */
            include_once(ABC::env('DIR_APP_EXTENSIONS').$driver.'/core/lib/'.$driver.'.php');
        } catch (AException $e) {
        }
        $className = preg_replace('/[^a-zA-Z]/', '', $driver);

        if (!class_exists($className)) {
            return false;
        }
        $driver = new $className();
        return $driver;
    }
}