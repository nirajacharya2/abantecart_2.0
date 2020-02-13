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

use abc\core\engine\Registry;
use abc\models\catalog\Product;
use abc\models\system\AuditEvent;
use abc\tests\unit\ATestCase;
use PHPUnit\Framework\Warning;

class AuditLogTest extends ATestCase
{
    /**
     * @param array $eventList
     *
     * @return int
     */
    protected function CreateUpdateRestoreDeleteProduct($eventList = [])
    {
        //use direct sql-query to prevent audit log of this operation
        Registry::db()->query("DELETE FROM ".Registry::db()->table_name('audit_events'));
        $this->reGenerateRequestId();

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
            'minimum'           => '1',
            'maximum'           => '0',
            'stock_checkout'    => '',
            'stock_status_id'   => '1',
            'sku'               => '124596788',
            'location'          => '',
            'date_available'    => '2013-08-29 14:35:30',
            'sort_order'        => '1',
            'shipping'          => '1',
            'free_shipping'     => '0',
            'ship_individually' => '0',
            'shipping_price'    => '0.1',
            'length'            => '1.00',
            'width'             => '1.00',
            'height'            => '1.00',
            'length_class_id'   => 1,
            'weight'            => '75.00',
            'weight_class_id'   => '2',
        ];
        try {
            $product = Product::createProduct($arProduct);
            $productId = $product->product_id;
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
            \H::df($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
            \H::df($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            \H::df($e->getMessage());
        }

        $this->assertIsInt($productId);

        $data = [
            'price'               => '29.55',
            'product_description' =>
                [
                    'name'             => 'Test update product',
                    'blurb'            => 'Test update blurb',
                    'description'      => 'Test update description',
                    'meta_keywords'    => 'test meta keywords',
                    'meta_description' => 'test meta description',
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
        $product = Product::onlyTrashed()
                          ->where('product_id', $productId)
                          ->first();
        $product->restore();
        //force delete
        /**
         * @var Product $mdl
         */
        $product = Product::withTrashed()
                          ->where('product_id', $productId)
                          ->first();
        if ($product) {
            $product->forceDelete();
        }

        return $productId;
    }

    public function testLoggedAllEvents()
    {
        //check all events list
        $productId = $this->CreateUpdateRestoreDeleteProduct();
        //check all events list
        $logged = $this->getLoggedEvents('Product', $productId);
        $assert = [
            //28 fields of product, 6 descriptions + 8 tags
            "created"      => 43,
            "deleting"     => 76,
            "deleted"      => 34,
            "restoring"    => 31,
            "restored"     => 30,
            "forceDeleted" => 30,

            //updated: 1 product price + 6 product descriptions + 1 date_deleted of product(restoring)
            "updated"      => 7,
            "updating"     => 7,
        ];
        $this->assertEquals($logged, $assert);
    }

    public function testLoggedPreEvents()
    {
        //check all events list
        $productId = $this->CreateUpdateRestoreDeleteProduct(
            [
                'saving',
                'creating',
                'updating',
                'deleting',
            ]
        );
        $logged = $this->getLoggedEvents('Product', $productId);
        $assert = [
            //Note: 11 - because product_id on creating is unknown!
            "creating" => 11,
            //deleting includes deletes of product, description and tags
            "deleting" => 76,
            // updated price of product. twice
            "updating" => 7,
        ];
        $this->assertEquals($logged, $assert);

    }

    public function testLoggedSaving()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['saving']);
        //check all events list
        $logged = $this->getLoggedEvents('Product', $productId);
        $assert = [
            //1 because saving before creating will skip
            "saving" => 7,
        ];

        $this->assertEquals($logged, $assert);
    }

    public function testLoggedSaved()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['saved']);
        //check all events list
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            []
        );
    }

    public function testLoggedSavingUpdating()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['saving', 'updating']);
        //check all events list
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                "updating" => 7,
            ]
        );
    }

    public function testLoggedCreatingUpdating()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['saving', 'creating']);
        //check all events list
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                "creating" => 11,
                "saving"   => 7,
            ]
        );
    }

    public function testLoggedCreatingUpdatingSaving()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['saving', 'creating', 'updating']);
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                'creating' => 11,
                'updating' => 7,
            ]
        );
    }

    public function testLoggedDeleting()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['deleting']);
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                'deleting' => 76,
            ]
        );
    }

    public function testLoggedDeleted()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['deleted']);
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                'deleted' => 34,
            ]
        );
    }

    public function testLoggedDeletingDeleted()
    {
        $productId = $this->CreateUpdateRestoreDeleteProduct(['deleting', 'deleted']);
        $logged = $this->getLoggedEvents('Product', $productId);
        $this->assertEquals(
            $logged,
            [
                'deleting' => 76,
                'deleted'  => 34,
            ]
        );
    }

    /**
     * @param string $auditableModel
     * @param int $auditableId
     *
     *
     * @return array
     */
    public function getLoggedEvents(string $auditableModel, int $auditableId): array
    {
        $db = Registry::db();

        $auditableModelId = 0;
        $auditModel = $db->table('audit_models')
                         ->where('name', '=', $auditableModel)
                         ->first();
        if ($auditModel) {
            $auditableModelId = $auditModel->id;
        } else {
            $this->fail('No model in Audit Models Table');
        }

        $requestId = Registry::request()->getUniqueId();

        $writtenEvents = [];
        //check records of all events set
        //$db->enableQueryLog();

        $audit = $db->table('audit_events');
        $result = $audit
            ->select(
                'event_type_id',
                $db->getORM()::raw('count(*) as count')
            )->leftJoin(
                'audit_event_descriptions',
                'audit_event_descriptions.audit_event_id',
                '=',
                'audit_events.id'
            )->where(
                [
                    'main_auditable_model_id' => $auditableModelId,
                    'main_auditable_id'       => $auditableId,
                    'request_id'              => $requestId,
                ]
            )->groupBy('event_type_id')
            ->get();

        if ($result) {
            foreach ($result as $row) {
                $writtenEvents[AuditEvent::getEventById($row->event_type_id)] = $row->count;
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
            Product::setCurrentLanguageID(1);
            Product::updateProduct($productId, $data);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    protected function reGenerateRequestId()
    {
        Registry::request()->setRequestId('unittest-'.time());
    }

}
