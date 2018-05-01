<?php

namespace abc\modules;

use abc\core\ABC;
use abc\core\helper\AHelperUtils;
use abc\core\lib\ABackup;

/**
 * Class ABackupModule
 *
 * @package abc\modules
 * @property array $errors
 */

class ABackupModule extends AModule implements AModuleInterface
{

    /**
     * @param array $configuration - can contains keys
     *                                  'tables' - table list for backup
     *                                  'rl' - files from public/resources directory
     *                                  'config' - sign to backup config directory
     *                                  'sql_dump_mode' - can be 'recreate' or 'data_only'
     *                                      (see ABackup class for details)
     *
     *
     * @return bool
     */
    public function backup(array $configuration)
    {

        extract($configuration);
        /**
         * @var array $tables
         * @var bool $rl
         * @var true $config
         * @var string $sql_dump_mode
         * @var ABackup $bkp
         */

        $bkp = AHelperUtils::getInstance('ABackup');

        $bkp->setBackupName('manual_backup'.'_'.date('Y-m-d-H-i-s'));
        if ($bkp->error) {
            return false;
        }

        // do sql dump
        if (!in_array($sql_dump_mode, array('data_only', 'recreate'))) {
            $sql_dump_mode = 'data_only';
        }
        $bkp->sql_dump_mode = $sql_dump_mode;
        $bkp->dumpTables($tables);

        if ($rl) {
            $bkp->backupDirectory(ABC::env('DIR_RESOURCES'), false);
        }
        if ($config) {
            $bkp->backupFile(ABC::env('DIR_ROOT').'/config/config.php', false);
        }
        $result = $bkp->archive(
            ABC::env('DIR_BACKUP').$bkp->getBackupName().'.tar.gz',
            ABC::env('DIR_BACKUP'),
            $bkp->getBackupName()
        );
        if (!$result) {
            $this->errors = array_merge($this->errors, $bkp->error);
        }

        return $result;
    }
}
