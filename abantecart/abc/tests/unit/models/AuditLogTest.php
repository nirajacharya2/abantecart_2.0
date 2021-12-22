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

namespace abc\tests\unit\models\admin;

use abc\models\catalog\Product;
use abc\models\system\Audit;
use abc\models\system\AuditEvent;
use abc\models\system\AuditModel;
use abc\tests\unit\ATestCase;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\Warning;

class AuditLogTest extends ATestCase
{
    /**
     * @param array $eventList
     *
     * @return int
     */
    public function testCreateUpdateRestoreDeleteProduct($eventList = [])
    {
        $productId = null;

        if (!$eventList) {
            Product::$auditEvents = [
                //Note: do not use creating with saving! Both will be skipped
                // Also On "creating" model don't know Key ID.
                'creating',
                'created',

                'updating',
                'updated',

                'deleting',
                'deleted',
                'forceDeleted',

                'restoring',
                'restored',
                //NOTE: only saving supported!
                'saving',

                //Cannot recognize saved after created and updated
                'saved',

            ];
        } else {
            Product::$auditEvents = $eventList;
        }

        $arProduct = [
            'status'              => '1',
            'featured'            => '1',
            'product_description' =>
                [
                    'name'             => 'Test product',
                    'blurb'            => 'Test blurb',
                    'description'      => 'Test description',
                    'meta_keywords'    => '',
                    'meta_description' => '',
                    'language_id'      => 1,
                ],
            'product_tags'        => 'cheeks,makeup',
            'product_category'    =>
                [
                    0 => '40',
                ],
            'product_store'       =>
                [
                    0 => '0',
                ],
            'manufacturer_id'     => '11',
            'model'               => 'Test Model',
            'call_to_order'       => '0',
            'price'               => '29.5000',
            'cost'                => '22',
            'tax_class_id'        => '1',
            'subtract'            => '0',
            'quantity'            => '99',
            'minimum'             => '1',
            'maximum'             => '0',
            'stock_checkout'      => '',
            'stock_status_id'     => '1',
            'sku'                 => '124596788',
            'location'            => '',
            'keyword'             => '',
            'date_available'      => '2013-08-29 14:35:30',
            'sort_order'          => '1',
            'shipping'            => '1',
            'free_shipping'       => '0',
            'ship_individually'   => '0',
            'shipping_price'      => '0',
            'length'              => '0.00',
            'width'               => '0.00',
            'height'              => '0.00',
            'length_class_id'     => '0',
            'weight'              => '75.00',
            'weight_class_id'     => '2',
        ];
        try {
            $productId = Product::createProduct($arProduct);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsInt($productId);

        $data = [
            'price' => '29.55',
            'product_description' =>
                    [
                        'name'             => 'Test update product',
                        'blurb'            => 'Test update blurb',
                        'description'      => 'Test update description',
                        'meta_keywords'    => '',
                        'meta_description' => '',
                        'language_id'      => 1,
                    ]
        ];
        $this->UpdateProduct($productId, $data);


        // now delete, restore and force delete
        try {
            Product::destroy($productId);
            $result = true;
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
            $result = false;
        } catch (Warning $e) {
            $this->fail($e->getMessage());
            $result = false;
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            $result = false;
        }

        $this->assertEquals(true, $result);
        //call restore events
        Product::onlyTrashed()
               ->where('product_id', $productId)
               ->first()
               ->restore();

        //force delete
        /**
         * @var Product $mdl
         */
        $mdl = Product::find($productId);
        $mdl->forceDelete();

        return $productId;
    }

    /**
     * @depends testCreateUpdateRestoreDeleteProduct
     *
     * @param $productId
     */
    public function testLoggedAllEvents(int $productId)
    {

        //check all events list
        $this->assertEquals(
            [
                "created"      => 28,
                "deleted"      => 37,
                "deleting"     => 80,
                "forceDeleted" => 35,
                "restored"     => 35,
                "restoring"    => 35,
                "updated"      => 5,
                "updating"     => 13,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedPreEvents()
    {
        //check all events list
        $productId = $this->testCreateUpdateRestoreDeleteProduct(
            ['saving', 'creating', 'updating', 'deleting']
        );
        $this->assertEquals(
            [
                "creating" => 7,
                "deleting" => 80,
                "updating" => 5,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedSaving()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving']);
        //check all events list
        $this->assertEquals(
            [
                //1 because saving before creating will skip
                "saving" => 5,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedSaved()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saved']);
        //check all events list
        $this->assertEquals(
            [],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedSavingUpdating()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving', 'updating']);
        //check all events list
        $this->assertEquals(
            [
                "updating" => 5,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedCreatingUpdating()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving', 'creating']);
        //check all events list
        $this->assertEquals(
            [
                "creating" => 7,
                "saving" => 5,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedCreatingUpdatingSaving()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving', 'creating', 'updating']);

        $this->assertEquals(
            [
                'creating' => 7,
                'updating' => 5,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedDeleting()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['deleting']);

        $this->assertEquals(
            [
                'deleting' => 80,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedDeleted()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['deleted']);

        $this->assertEquals(
            [
                'deleted' => 37,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    public function testLoggedDeletingDeleted()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['deleting', 'deleted']);

        $this->assertEquals(
            [
                'deleting' => 80,
                'deleted'  => 37,
            ],
            $this->getLoggedEvents('Product', $productId)
        );
    }

    /**
     * @param string $auditableModel
     * @param int $auditableId
     *
     * @return array
     */
    public function getLoggedEvents(string $auditableModel, int $auditableId): array
    {
        $db = $this->registry->get('db');

        $auditableModelId = 0;
        /** @var AuditModel $auditModel */
        $auditModel = $db->table('audit_models')
            ->where('name', '=', $auditableModel)
            ->first();
        if ($auditModel) {
            $auditableModelId = $auditModel->id;
        } else {
            $this->fail('No model in Audit Models Table');
        }

        $requestId = $this->registry->get('request')->getUniqueId();

        $writtenEvents = [];
        //check records of all events set
        //$db->enableQueryLog();

        $audit = new AuditEvent();
        $result = $audit
            ->select('event_type_id', $db->getORM()::raw('count(*) as count'))
            ->leftJoin('audit_event_descriptions', 'audit_event_descriptions.audit_event_id', '=', 'audit_events.id')
            ->where('main_auditable_model_id', '=', $auditableModelId)
            ->where('main_auditable_id', '=', $auditableId)
            ->where('request_id', '=', $requestId)
            ->groupBy('event_type_id')
            ->get();


        if ($result->count()) {
            foreach ($result->toArray() as $row) {
                $writtenEvents[AuditEvent::getEventById($row['event_type_id'])] = $row['count'];
            }
        }

        return $writtenEvents;
    }

    /**
     * @depends testCreateProduct
     *
     * @param int $productId
     */
    public function UpdateProduct(int $productId, $data)
    {
        try {
            Product::updateProduct($productId, $data, 1);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

    }

}
