<?php

namespace GaeUtil;

class Cached {

    protected $_key;
    protected $_data;
    protected $_ignore_cache = false;

    /**
     * Cached constructor.
     *
     * @param $cache_key
     * @param bool $ignore_cache
     */
    public function __construct($cache_key, $ignore_cache = false) {
        if (Util::isCli()) {
            $ignore_cache = true;
        }
        $this->_key = $cache_key;
        if ($ignore_cache) {
            $this->_ignore_cache = true;
        } else {
            $this->_data = self::client()->get($this->_key);
        }
    }

    /**
     * @return bool
     */
    public function exists() {
        return !empty($this->_data);
    }

    /**
     * @return mixed|\the
     */
    public function get() {
        return $this->_data;
    }

    /**
     * @param $value
     * @param int $expiration
     */
    public function set($value, $expiration = 3600) {
        $this->_data = $value;
        if (!$this->_ignore_cache) {
            self::client()->set($this->_key, $value, $expiration);
        }
    }

    /**
     *
     * @staticvar \Memcached $mem
     * @return \Memcached
     */
    static function client() {
        static $client;
        if (is_null($client)) {
            $client = new \Memcached();
            $client->addServer('localhost', 11211);
        }
        return $client;
    }

    static function keymaker() {
        return md5(json_encode(func_get_args()));
    }

    static function delete($cache_key) {
        return self::client()->delete($cache_key);
    }
}
