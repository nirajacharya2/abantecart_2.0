<?php

namespace abc\modules;

use abc\core\ABC;
use abc\core\helper\AHelperUtils;
use abc\core\lib\ABackup;

/**
 * Class ABackupModule
 *
 * @package abc\modules
 */
class ABackupModule extends AModuleBase implements AModuleInterface
{

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



        $bkp = AHelperUtils::getInstance(ABC::getFullClassName('ABackup'));

        $bkp->setBackupName('manual_backup'.'_'.date('Y-m-d-H-i-s'));
        if ($bkp->error) {
            return false;
        }

        // do sql dump
        if (!in_array($sql_dump_mode, array('data_only', 'recreate'))) {
            $sql_dump_mode = 'data_only';
        }
        $bkp->sql_dump_mode = $sql_dump_mode;
        $bkp->dumpTables($table_list);

        if ($rl) {
            $bkp->backupDirectory(ABC::env('DIR_RESOURCES'), false);
        }
        if ($config) {
            $bkp->backupFile(ABC::env('DIR_ROOT').DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php', false);
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
    public function postProcessing(){}
}
