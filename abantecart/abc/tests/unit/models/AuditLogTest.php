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
use abc\tests\unit\ATestCase;
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
                //TODO: check why restore events not fired!
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

        //call retrieve and update events
        Product::find($productId)->update(['price' => '29.55']);

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
               ->restore();

        //force delete
        Product::find($productId)->forceDelete();

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
            $this->getLoggedEvents('Product', $productId),
            [
                "created"  => 27,
                "deleted"  => 34,
                "deleting" => 66,
                "updated"  => 1,
                "updating" => 1,
            ]
        );
    }

    public function testLoggedPreEvents()
    {
        //check all events list
        $productId = $this->testCreateUpdateRestoreDeleteProduct(
            ['saving', 'creating', 'updating', 'deleting']
        );
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                "deleting" => 66,
                "updating" => 1,
            ]
        );
    }

    public function testLoggedSaving()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving']);
        //check all events list
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                //1 because saving before creating will skip
                "saving" => 1,
            ]
        );
    }

    public function testLoggedSaved()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saved']);
        //check all events list
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            []
        );
    }

    public function testLoggedSavingUpdating()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving', 'updating']);
        //check all events list
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                "updating" => 1,
            ]
        );
    }

    public function testLoggedCreatingUpdating()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving', 'creating']);
        //check all events list
        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                "saving" => 1,
            ]
        );
    }

    public function testLoggedCreatingUpdatingSaving()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['saving', 'creating', 'updating']);

        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                'updating' => 1,
            ]
        );
    }

    public function testLoggedDeleting()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['deleting']);

        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                'deleting' => 66,
            ]
        );
    }

    public function testLoggedDeleted()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['deleted']);

        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                'deleted' => 34,
            ]
        );
    }

    public function testLoggedDeletingDeleted()
    {
        $productId = $this->testCreateUpdateRestoreDeleteProduct(['deleting', 'deleted']);

        $this->assertEquals(
            $this->getLoggedEvents('Product', $productId),
            [
                'deleting' => 66,
                'deleted'  => 34,
            ]
        );
    }

    /**
     * @param string $auditableType
     * @param int $auditableId
     *
     * @return array
     */
    public function getLoggedEvents(string $auditableType, int $auditableId): array
    {
        $writtenEvents = [];
        //check records of all events set
        $audit = new Audit();
        $result = $audit
            ->select('event', $this->registry->get('db')->getORM()::raw('count(*) as count'))
            ->distinct()
            ->where('auditable_type', '=', $auditableType)
            ->where('auditable_id', '=', $auditableId)
            ->groupBy('event')
            ->orderBy('event', 'asc')
            ->get();

        if ($result->count()) {
            foreach ($result->toArray() as $row) {
                $writtenEvents[$row['event']] = $row['count'];
            }
        }
        return $writtenEvents;
    }

}