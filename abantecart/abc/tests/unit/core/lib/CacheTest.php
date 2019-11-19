<?php

namespace abc\tests\unit;

use abc\core\engine\Registry;
use stdClass;

/**
 * Class CacheTest
 */
class CacheTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testCache1()
    {
        $key = 'TestCacheKey';
        $data = new StdClass;
        $data->property = 'unit';
        $cache = Registry::cache();
        $cache->put($key, $data);
        $this->assertEquals('unit', $cache->get($key)->property);
        $cache->flush('unit');
    }

    public function testCacheTags()
    {
        $key = 'TestCacheKey';
        $data = new StdClass;
        $data->property = 'unit';
        $tags = 'tag-unit';

        $cache = Registry::cache();
        $cache->tags($tags)->put($key, $data);
        $this->assertEquals('unit', $cache->get($key)->property);
        $cache->flush($tags);

        $this->assertNull($cache->get($key));
    }

//    public function testMemCachedTags(){
//        $key = 'TestCacheKey';
//        $data = ['property' => 'unit'];
//        $tags = 'tag-unit';
//
//        $cache = Registry::cache();
//        $cache->setCurrentStore('memcached');
//        $cache->tags($tags)->put($key, $data);
//        //var_dump($cache->get($key));
//        $this->assertEquals('unit',$cache->get($key)['property']);
//        $cache->put('other_cache', ['test']);
//        $cache->flush($tags);
//
//        $this->assertNull($cache->get($key));
//        $this->assertIsArray( $cache->get('other_cache') );
//    }
}