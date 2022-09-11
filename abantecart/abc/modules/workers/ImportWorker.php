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

use application\components\workers\WorkerException;
use application\modules\membership\models\MembershipFile;

final class MembershipWorker extends ABaseWorker
{
    /**
     * @inheritdoc
     */
    protected function init()
    {
        $this->setInitiatorID($this->job['userId']);
        $this->setOwnerID($this->job['userId']);
        $this->inProgressMessage = 'File processing did not finish.';
    }

    /**
     * @inheritdoc
     */
    protected function afterError()
    {
        if (isset($this->inProgressModel)) {
            $this->inProgressModel->delete();
        }
    }

    /**
     * Method to process Import job.
     *
     * @return bool
     * @throws WorkerException
     */
    public function membershipDo()
    {
        self::renderStart($this->jobString, 'Import');

        if ($this->inProgressModel === null) {
            $message = 'Import model could not be found.';
            $this->inProgressMessage = $message;
            throw new WorkerException($message);
        }

        try {
            $jobQueue = $this->inProgressModel->getJobName();
            echoCLI(self::getTime(). ' --- job is redirecting --> ' . $jobQueue);
            $this->inProgressMessage = null;

            if (false != $result = addBackgroundJob($jobQueue, $this->job)) {
                $this->inProgressModel->mutex->unlock();
                echoCLI("Unlock InProgress Model");
                $this->inProgressModel->disableBehavior('InProgressBehavior');
            }

        } catch (Exception $e) {
            throw new WorkerException('', 0, $e);
        }

        self::renderFinish();

        return $result;
    }

    /**
     * @return array worker callback methods
     */
    public function getWorkerMethods()
    {
        return [
            'membershipDo'
        ];
    }
}
