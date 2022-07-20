<?php

namespace Tests\unit\models\catalog;

use abc\models\catalog\Manufacturer;
use Exception;
use PDOException;
use PHPUnit\Framework\Warning;
use Tests\unit\ATestCase;

class ManufacturerModelTest extends ATestCase
{
    /**
     * @return bool|mixed
     */
    public function testCreateManufacturer()
    {
        $arManufacturer = [
            'name' => 'Manufacturer create test',
            'sort_order'=> 100,
            'manufacturer_store' => [0],
            'keyword' => 'test-create-manufacturer'
        ];
        try {
        $manufacturerId = Manufacturer::addManufacturer($arManufacturer);
        } catch (PDOException|Warning|Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsInt($manufacturerId);
        return $manufacturerId;
    }

    /**
     * @depends testCreateManufacturer
     *
     * @param int $manufacturerId
     */
    public function testReadManufacturer(int $manufacturerId)
    {
        $manufacturer = Manufacturer::find($manufacturerId);
        $this->assertEquals('Manufacturer create test', $manufacturer->name);
    }


    /**
     * @depends testCreateManufacturer
     *
     * @param int $manufacturerId
     *
     */
    public function testUpdateManufacturer(int $manufacturerId)
    {
        $arManufacturer = [
            'name' => 'Manufacturer update test',
            'sort_order'=> 300,
            ];

        (new Manufacturer())->editManufacturer($manufacturerId, $arManufacturer);

        $manufacturer = Manufacturer::find($manufacturerId);
        $this->assertEquals(300, $manufacturer->sort_order);
    }

    /**
     * @depends testCreateManufacturer
     *
     * @param int $manufacturerId
     *
     */
    public function testDeleteManufacturer(int $manufacturerId)
    {
        try {
            (new Manufacturer)->deleteManufacturer($manufacturerId);
        } catch (PDOException|Warning|Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertNull(Manufacturer::find($manufacturerId));
    }
}
