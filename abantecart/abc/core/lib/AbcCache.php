<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use Closure;
use DateInterval;
use DateTimeInterface;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Cache\TaggedCache;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Redis\RedisManager;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class AbcCache
 *
 * @method static Repository  store(string|null $name = null)
 * @method static bool has(string $key)
 * @method static bool missing(string $key)
 * @method static int|bool increment(string $key, $value = 1)
 * @method static int|bool decrement(string $key, $value = 1)
 * @method static bool forever(string $key, $value)
 * @method static mixed sear(string $key, Closure $callback)
 * @method static mixed rememberForever(string $key, Closure $callback)
 * @method static bool forget(string $key)
 *
 * @package abc\core\lib
 */
class AbcCache
{
    /** @var array $storeConfig */
    static $storeConfig;
    /** @var CacheManager $manager */
    protected $manager;
    /** @var bool */
    protected $enabled;
    static $currentStore = '';

    public function __construct(string $defaultDriver = 'file', $config = [])
    {
        static::$storeConfig = $config ?: ABC::env('CACHE')['stores'][$defaultDriver];
        if(!static::$storeConfig){
            throw new Exception(__CLASS__.': Configuration of cache-driver '
                .$defaultDriver.' not found!. '
                .'Please check your environment and file config/'.ABC::getStageName().'/config.php');
        }
        static::$currentStore = $defaultDriver;
        $this->initManager();
        if (!$this->manager) {
            Registry::log()->write(__CLASS__.':  Cannot to initiate cache manager.');
        }

        $this->manager->setDefaultDriver(static::$currentStore);

        $config = Registry::config();
        if (!$config) {
            $this->disableCache();
        } elseif ($config->get('config_cache_enable')) {
            $this->enableCache();
        } else {
            $this->disableCache();
        }
    }

    /**
     * Enable caching is storage. Note, persistent in memory cache is always enabled
     *
     * @return  void
     *
     */
    public function enableCache()
    {
        $this->enabled = true;
    }

    /**
     *Disable caching is storage. Note, persistent in memory cache is always enabled
     *
     * @return  void
     *
     */
    public function disableCache()
    {
        $this->enabled = false;
    }

    /**
     * Check if cache storage is enabled
     *
     * @return  boolean  Caching state
     *
     */
    public function isCacheEnabled()
    {
        return $this->enabled;
    }

    protected function initManager()
    {
        /** @var Application $app */
        $app = new Container();
        Container::setInstance($app);
        $this->initFileApp($app);
        $this->initMemcachedApp($app);
        $this->initRedisApp($app);

        $config = $this->getConfig();
        $config['cache.default'] = static::$currentStore;
        $config['cache.prefix'] = ABC::env('APP_NAME');

        $app['config'] = function () use ($config) {
            return $config;
        };
        $this->manager = new CacheManager($app);
    }

    /**
     * @param Application $app
     *
     */
    protected function initFileApp(&$app)
    {

        $app->singleton('files', function(){
            return new Filesystem();
        });

    }

    /**
     * @param Application $app
     *
     */
    protected function initMemcachedApp(&$app)
    {
        $app['memcached.connector'] = new MemcachedConnector();
    }
    /**
     * @param Application $app
     *
     */
    protected function initRedisApp(&$app)
    {
        $app->singleton('redis', function($app){
            $config = $this->getConfig('redis');
            return new RedisManager($app,$config['client'],$config );
        });
    }

    /**
     * @param string $name
     * @return array|array[]|mixed
     */
    public function getConfig($name = '')
    {
        $output = [];
        if(!$name) {
            if (isset(ABC::env('CACHE')['stores']['file'])) {
                $output = [
                    'cache.stores.file' => [
                        'driver' => 'file',
                        'path' => AbcCache::$storeConfig['path'],
                    ],
                ];
            }

            if (isset(ABC::env('CACHE')['stores']['redis'])) {
                $output['cache.stores.redis'] =
                    array_merge(
                        ['driver' => 'redis'],
                        ABC::env('CACHE')['stores']['redis']
                    );
            }

            if (isset(ABC::env('CACHE')['stores']['memcached'])) {
                $output['cache.stores.memcached'] =
                    array_merge(
                        ['driver' => 'memcached'],
                        ABC::env('CACHE')['stores']['memcached']
                    );
            }
        }else{
            $output = ABC::env('CACHE')['stores'][$name];
        }

        return $output;
    }

    /**
     * @return CacheManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return string
     */
    public function getCurrentStore()
    {
        return static::$currentStore;
    }

    /**
     * @return Repository
     */
    public function getCurrentStorage()
    {
        return $this->getStorage();
    }

    /**
     * @param string $store
     *
     * @throws Exception
     */
    public function setCurrentStore(string $store)
    {
        if ($store && in_array($store, $this->getAvailableStores())) {
            static::$currentStore = $store;
            $this->manager->setDefaultDriver($store);
        } else {
            throw new Exception('Storage '.$store
                .' not found in the configuration. See abc/config/*/config.php file for details');
        }
    }

    /**
     * @return array
     */
    public function getAvailableStores()
    {
        return array_keys(ABC::env('CACHE')['stores']);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  DateTimeInterface|DateInterval|int|null $ttl
     * @param string $store - storage name, if empty - will use default
     *
     *
     * @return bool
     */
    public function put(string $key, $value, $ttl = null, string $store = '')
    {
        if( !$this->enabled || !$this->manager){
            return false;
        }

        $storage = $this->getStorage($store);
        $ttl = $ttl === null ? static::$storeConfig['ttl'] : $ttl;
        if(!$storage){
            return false;
        }

        //use first word in the key as tag. ABC only case.
        $parts = explode(".", $key);
        if (count($parts) > 1 && method_exists($storage->getStore(), 'tags')) {
            $storage = $storage->tags($parts[0]);
        }
        return $storage->put($key, $value, $ttl);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string $key
     * @param string $store - storage name, if empty - will use default
     *
     * @return mixed | false
     * @throws InvalidArgumentException
     */
    public function get(string $key, string $store = '')
    {
        if( !$this->enabled ){
            return null;
        }

        if(!$this->manager){
            return null;
        }

        $storage = $this->getStorage($store);
        if(!$storage){
            return null;
        }

        //use first word in the key as tag. ABC only case.
        $parts = explode(".", $key);
        if (count($parts) > 1 && method_exists($storage->getStore(), 'tags')) {
            $storage = $storage->tags($parts[0]);
        }

        return $storage->get($key);
    }


    /**
     *
     * @param  string $key
     * @param  mixed $value
     * @param  DateTimeInterface|DateInterval|int|null $ttl
     * @param string $store - storage name, if empty - will use default
     *
     * @return bool
     */
    public function add(string $key, $value, $ttl = null, string $store = '')
    {

        if( !$this->enabled || !$this->manager){
            return null;
        }

        $storage = $this->getStorage($store);
        $ttl = $ttl === null ? static::$storeConfig['ttl'] : $ttl;
        return $storage?->add($key, $value, $ttl);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  string  $key
     * @param  DateTimeInterface|DateInterval|int|null  $ttl
     * @param  Closure  $callback
     * @param string $store - storage name, if empty - will use default
     * @return mixed
     */
    public function remember(string $key, $ttl, Closure $callback, string $store = '')
    {
        if( !$this->enabled || !$this->manager ){
            return null;
        }

        /** @var \Illuminate\Cache\Repository $storage */
        $storage = $this->getStorage($store);
        $ttl = $ttl ?? static::$storeConfig['ttl'];
        return $storage->remember($key, $ttl, $callback);
    }

    public function flush($tags = '', string $store = '')
    {
        $tags = is_array($tags) ? $tags : func_get_args();
        $storage = $this->getStorage($store);
        if (method_exists($storage->getStore(), 'tags') && $tags) {
            /** @var TaggedCache $storage */
            return $storage->tags($tags)->flush();
        }
        /** @var FileStore $storage */
        return $storage->flush();
    }

    /**
     * Forward calls to current(default) storage
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if($name == 'store'){
            return call_user_func_array([$this->manager, $name], $arguments);
        }

        if( !$this->enabled ){
            return null;
        }
        //backward compatibility
        if($name == 'push'){
            $name = 'put';
        }
        if($name == 'pull'){
            $name = 'get';
        }
        if($name == 'remove'){
            $name = 'flush';
        }
        //use current store or default - see static function
        $storage = $this->getStorage();

        return call_user_func_array([$storage, $name], $arguments);
    }

    /**
     * @param string $store
     *
     * @return Repository
     */
    protected function getStorage(string $store = '')
    {
        $store = $store ?: static::$currentStore;
        $store = $store ?: ABC::env('CACHE')['driver'];
        return $this->manager->store($store);
    }

    public function tags($tags, $store = '')
    {
        $tags = is_array($tags) ? $tags : func_get_args();
        $storage = $this->getStorage($store);
        if (method_exists($storage->getStore(), 'tags') && $tags) {
            $storage = $storage->tags($tags);
        }
        return $storage;
    }

    public function paramsToString($data = [])
    {
        $output = '';
        if (empty($data)) {
            return '';
        }
        asort($data);
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $output .= $this->paramsToString($val);
            } else {
                $output .= '.'.$key."=".$val;
            }
        }
        return $output;
    }
}