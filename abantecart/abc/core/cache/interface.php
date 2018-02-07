<?php
namespace abc\core\cache;
interface ACacheDriverInterface
{
    public function isSupported();
    /**
     * Get cached data from a file by key and group
     *
     * @param   string  $key          The cache data key
     * @param   string  $group        The cache data group
     * @param   boolean $check_expire True to verify cache time expiration
     *
     * @return  mixed  Boolean false on failure or a cached data string
     */
    public function get($key, $group, $check_expire = true);

    /**
     * Save data to a file by key and group
     *
     * @param   string $key   The cache data key
     * @param   string $group The cache data group
     * @param   string $data  The data to store in cache
     *
     * @return  boolean
     */
    public function put($key, $group, $data);

    /**
     * Remove a cached data file by key and group
     *
     * @param   string $key   The cache data key
     * @param   string $group The cache data group
     *
     * @return  boolean
     */
    public function remove($key, $group);

    /**
     * Clean cache for a group provided.
     *
     * @param   string $group The cache data group, passed '*' indicate all cache removal
     *
     * @return  boolean
     */
    public function clean($group);

    /**
     * Delete expired cache data
     *
     * @return  boolean  True on success, false otherwise.
     */
    public function gc();

    /**
     * Lock cached item
     *
     * @param   string  $key      The cache data key
     * @param   string  $group    The cache data group
     * @param   integer $locktime Cached item max lock time
     *
     * @return  array
     */
    public function lock($key, $group, $locktime);

    /**
     * Unlock cached item
     *
     * @param   string $key   The cache data key
     * @param   string $group The cache data group
     *
     * @return  boolean
     */
    public function unlock($key, $group = null);

}
