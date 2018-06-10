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

namespace abc\models;

use abc\core\engine\Registry;
use Illuminate\Database\Eloquent\Model as OrmModel;
use abc\core\helper\AHelperUtils;
use Exception;

/**
 * Class AModelBase
 *
 * @package abc\models
 */
class AModelBase extends OrmModel
{
    /**
     *
     */
    const CREATED_AT = 'date_added';
    /**
     *
     */
    const UPDATED_AT = 'date_modified';
    /**
     *
     */
    const CLI = 0;
    /**
     *
     */
    const ADMIN = 1;
    /**
     *
     */
    const CUSTOMER = 2;

    /**
     * @var array
     */
    protected $actor;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \abc\core\lib\AConfig
     */
    protected $config;
    /**
     * @var \abc\core\cache\ACache
     */
    protected $cache;
    /**
     * @var array
     */
    protected $permisions = [
        self::CLI => ['update', 'delete'],
        self::ADMIN => ['update', 'delete'],
        self::CUSTOMER => []
    ];

    /**
     * AModelBase constructor.
     */
    public function __construct()
    {
        $this->actor = AHelperUtils::recognizeUser();
        $this->registry = Registry::getInstance();
        $this->config = $this->registry->get('config');
        $this->cache = $this->registry->get('cache');
    }

    /**
     * @param none
     *
     * @return array
     */
    public function getPermisions(): array
    {
        return $this->permisions[$this->actor['user_type']];
    }

    /**
     * @param $operation
     *
     * @return bool
     */
    public function hasPermision($operation): bool
    {
        return in_array($operation, $this->getPermisions());
    }

    /**
     * Extend save the model to the database.
     *
     * @param  array  $options
     * @return bool
     *
     * @throws Exception
     */
    public function save(array $options = [])
    {
        if ($this->hasPermision('save')) {
            parent::save();
        } else {
            throw new Exception('No permission for object to save the model.');
        }
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        if ($this->hasPermision('delete')) {
            parent::delete();
        } else {
            throw new Exception('No permission for object to delete the model.');
        }
    }
}