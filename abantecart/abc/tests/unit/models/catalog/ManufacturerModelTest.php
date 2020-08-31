<?php

namespace abc\tests\unit\models\catalog;

use abc\models\catalog\Manufacturer;
use abc\tests\unit\ATestCase;
use PHPUnit\Framework\Warning;

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
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
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
     * @throws \abc\core\lib\AException
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
            $result = (new Manufacturer)->deleteManufacturer($manufacturerId);
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

        $this->assertNull(Manufacturer::find($manufacturerId));
    }


}
