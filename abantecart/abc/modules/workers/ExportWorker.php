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

use application\components\export\Exporter;
use application\components\workers\WorkerException;

/**
 * ExportWorker for larger objects to a file.
 *
 */
final class ExportWorker extends ABaseWorker
{
    /**
     * @inheritdoc
     */
    protected function init()
    {
        $this->setInitiatorID($this->job['userId']);
        $this->setOwnerID($this->job['userId']);
        $this->inProgressMessage = $this->getErrorMessage();
    }

    /**
     * @inheritdoc
     */
    protected function afterError()
    {
        /**
         * Saved failed details
         */
        \application\components\InProgress::saveBackgroundJobAfterError(
            $this->inProgressModel,
            $this->method,
            $this->jobString
        );
    }

    /**
     * Worker callback.
     *
     * @return bool
     * @throws WorkerException
     */
    public function exportDo()
    {
        self::renderStart($this->jobString, 'Export');

        if ($this->inProgressModel === null) {
            throw new WorkerException('Export objectcould not be found.');
        }

        $result = false;

        try {
            $exporter = Exporter::getInstance($this->inProgressModel);
            $exporter->setFileBaseName(pathinfo($this->inProgressModel->filename)['filename']);
            $exporter->selectAttributes($this->inProgressModel->getAttribute('attributes'));
            $exporter->selectEntities($this->inProgressModel->getAttribute('entities'));

            if ($exporter->exportToFile()) {
                self::render('Export was finished.');

                if (!$this->inProgressModel->exists('id = :id', ['id' => $this->inProgressModel->id])) {
                    $this->inProgressMessage = 'Export object was deleted from database';
                    self::render($this->inProgressMessage);
                } else {
                    //?????? example for request
                    $filePath = $exporter->getExportDir() . '/' . $this->inProgressModel->filename;
                    $isSaved = $this->makeAttempts(function () use ($filePath) {
                        return file_exists($filePath) && filesize($filePath) > 0 ? true : null;
                    }, 12, 5);
                    self::render('File exists: ' . ($isSaved ? 'true' : 'false'));

                    if ($this->inProgressModel->needGenerateViewFile(true)) {
                        self::render('Generate file for view');

                        self::render($this->inProgressModel->generateViewFile() ? 'true' : 'false');
                    }
                    $result = true;
                    $this->inProgressMessage = 'Export processing has finished. View result {here}.';
                }
            }
        } catch (Exception $e) {
            throw new WorkerException('', 0, $e);
        }

        self::renderFinish();

        return $result;
    }

    /**
     * @return string
     */
    private function getErrorMessage()
    {
        return 'Export processing did not finish. Error: error occured.';
    }

    /**
     * @return array worker callback methods
     */
    public function getWorkerMethods()
    {
        return [
            'exportDo',
        ];
    }
}
