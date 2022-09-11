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

/**
 * Class ElasticWorker
 */
class ElasticWorker extends ABaseWorker
{
    protected function init()
    {
        $this->setInitiatorID($this->job['userId']);
        $this->setOwnerID($this->job['userId']);
    }

    /**
     * background processing product indexation
     *
     * @return bool
     * @throws WorkerException
     */
    public function elasticProductIndexDo()
    {
        self::renderStart($this->jobString, 'Product indexing process');

        $bulkData = $ids = $idsIgnoreList =[];

        try {
            // ????? select products
            foreach ($products as $k => $product) {
                $index = $product->preparedData($assessmentRow, false, 0);

                if ($index) {
                    $bulkData['body'][] = [
                        'index' => [
                            '_index' => $product->index,
                            '_type' => $product->type,
                            '_id' => $index['body']['id'],
                        ],
                    ];

                    $bulkData['body'][] = $index['body'];

                    $ids[] = $product['id'];
                } else {
                    $idsIgnoreList[] = $product['id'];
                }
            }

            if ($bulkData) {
                $client->bulk($bulkData);
            }

            if ($ids) {
                //update products that they are now in elastic
            }

            if ($idsIgnoreList) {
                //mark all products that we cannot prepared data for indexing
            }
        } catch (\Exception $e) {
            throw new WorkerException('', 0, $e);
        }

        self::renderFinish();

        return true;
    }

    /**
     * @return array worker callback methods
     */
    public function getWorkerMethods()
    {
        return [
            'elasticProductIndexDo',
        ];
    }
}
