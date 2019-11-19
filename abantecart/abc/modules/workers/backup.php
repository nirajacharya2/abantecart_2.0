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

namespace abc\modules\workers;

use abc\core\ABC;
use abc\core\lib\ABackup;
use H;

/**
 * Class ABackupModule
 *
 * @package abc\modules
 */
class ABackupWorker extends ABaseWorker
{

    public $workerName = 'Backup Worker';

    public function __construct()
    {
        $this->reRunIfFailed = true;
    }

    /**
     * @param array $configuration - can contains keys
     *                              'tables' - table list for backup
     *                              'rl' - files from public/resources directory
     *                              'config' - sign to backup config directory
     *                              'sql_dump_mode' - can be 'recreate' or 'data_only'
     *                                   (see ABackup class for details)
     *
     *
     * @return bool
     * @throws \DebugBar\DebugBarException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function backup(array $configuration)
    {

        extract($configuration);
        /**
         * @var array $table_list
         * @var bool $rl
         * @var true $config
         * @var string $sql_dump_mode
         * @var ABackup $bkp
         */

        $bkp = H::getInstance(ABC::getFullClassName('ABackup'));

        $bkp->setBackupName('manual_backup'.'_'.date('Y-m-d-H-i-s'));
        if ($bkp->error) {
            return false;
        }

        // do sql dump
        if (!in_array($sql_dump_mode, ['data_only', 'recreate'])) {
            $sql_dump_mode = 'data_only';
        }
        $bkp->sql_dump_mode = $sql_dump_mode;
        $bkp->dumpTables($table_list);

        if ($rl) {
            $bkp->backupDirectory(ABC::env('DIR_RESOURCES'), false);
        }
        if ($config) {
            $bkp->backupFile(ABC::env('DIR_ROOT').DS.'config'.DS.'config.php', false);
        }
        $result = $bkp->archive(
            ABC::env('DIR_BACKUP').$bkp->getBackupName().'.tar.gz',
            ABC::env('DIR_BACKUP'),
            $bkp->getBackupName()
        );
        if (!$result) {
            $this->errors = array_merge($this->errors, $bkp->error);
        }

        return $this->errors ? false : true;
    }

    /**
     * @return array
     */
    public function getModuleMethods()
    {
        return ['backup'];
    }
    public function postProcessing()
    {
    }
}
