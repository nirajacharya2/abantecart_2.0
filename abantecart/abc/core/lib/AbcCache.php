<?php
/**
 * ABC wrapper of Laravel Cache
 *
 */

namespace abc\core\lib;

use abc\core\ABC;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Cache;
use Illuminate\Filesystem\Filesystem;

/**
 * Class AbcCache
 *
 * @method static \Illuminate\Contracts\Cache\Repository  store(string|null $name = null)
 * @method static bool has(string $key)
 * @method static bool missing(string $key)
 * @method static int|bool increment(string $key, $value = 1)
 * @method static int|bool decrement(string $key, $value = 1)
 * @method static bool forever(string $key, $value)
 * @method static mixed sear(string $key, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static bool forget(string $key)
 * @method static \Illuminate\Contracts\Cache\Store getStore()
 *
 * @package abc\core\lib
 */
class AbcCache
{
    /** @var array $storeConfig */
    static $storeConfig;
    /** @var Cache $manager */
    protected $manager;
    /** @var bool */
    protected $enabled;
    static $currentStore = '';

    public function __construct(string $driver = 'file', $config = [])
    {
        static::$storeConfig = $config ?: ABC::env('CACHE')['stores'][$driver];
        if(!static::$storeConfig){
            throw new \Exception(__CLASS__.': Configuration of cache-driver '
                .$driver.' not found!. '
                .'Please check your environment and file config/'.ABC::getStageName().'/config.php');
        }
        static::$currentStore = $driver;
        $this->manager = $this->initManager();
    }

    /**
     * Enable caching is storage. Note, persistent in memory cache is always enabled
     *
     * @return  void
     *
     * @since  1.2.7
     */
    public function enableCache()
    {
        $this->enabled = true;
    }

    /**
     *Disable caching is storage. Note, persistent in memory cache is always enabled
     *
     *
     * @return  void
     *
     * @since  1.2.7
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
     * @since  1.2.7
     */
    public function isCacheEnabled()
    {
        return $this->enabled;
    }

    public function initManager(){
        $app = new Container();
        Container::setInstance($app);
        $app->singleton('files', function(){
            return new Filesystem();
        });

        $app->singleton('config', function(){
            return [
                'path.storage' => __DIR__.'/storage',
                'cache.default' => 'file',
                'cache.stores.file' => [
                    'driver' => 'file',
                    'path' => static::$storeConfig['path']
                ]
            ];
        });

        return new CacheManager($app);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  \DateTimeInterface|\DateInterval|int|null $ttl
     * @param string $store - storage name, if empty - will use default
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
        return $storage->put($key, $value, $ttl);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string $key
     * @param string $store - storage name, if empty - will use default
     *
     * @return mixed | false
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $key, string $store = '')
    {
        if( !$this->enabled ){
            return null;
        }

        if(!$this->manager){
            return false;
        }

        $storage = $this->getStorage($store);
        if(!$storage){
            return false;
        }

        $result = $storage->get($key);
        return $result === null ? false : $result;
    }


    /**
     *
     * @param  string $key
     * @param  mixed $value
     * @param  \DateTimeInterface|\DateInterval|int|null $ttl
     * @param string $store - storage name, if empty - will use default
     *
     * @return bool
     */
    public function add(string $key, $value, $ttl = null, string $store = '')
    {

        if( !$this->enabled || !$this->manager){
            return false;
        }

        $storage = $this->getStorage($store);
        $ttl = $ttl === null ? static::$storeConfig['ttl'] : $ttl;
        if(!$storage){
            return false;
        }

        return $storage->add($key, $value, $ttl);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @param  \Closure  $callback
     * @param string $store - storage name, if empty - will use default
     * @return mixed
     */
    public function remember(string $key, $ttl, \Closure $callback, string $store = '')
    {
        if( !$this->enabled || !$this->manager ){
            return false;
        }

        $storage = $this->getStorage($store);
        $ttl = $ttl === null ? static::$storeConfig['ttl'] : $ttl;
        return $storage->remember($key, $ttl, $callback);
    }


    public function flush(string $tag = '', string $store = '')
    {
        $storage = $this->getStorage($store);
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

        if($name == 'push'){
            $name = 'put';
        }
        if($name == 'pull'){
            $name = 'get';
        }
        //use current store or default - see static function
        $storage = $this->getStorage();

        return call_user_func_array([$storage, $name], $arguments);
    }

    protected function getStorage(string $store = '')
    {
        $store = !$store ? static::$currentStore : $store;
        $store = !$store ? ABC::env('CACHE')['driver'] : $store;
        return $this->manager->store($store);
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