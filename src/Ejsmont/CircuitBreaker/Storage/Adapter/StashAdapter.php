<?php
namespace Ejsmont\CircuitBreaker\Storage\Adapter;

use Ejsmont\CircuitBreaker\Storage\Adapter\BaseAdapter;
use Ejsmont\CircuitBreaker\Storage\StorageException;

class StashAdapter extends BaseAdapter
{

    /**
     * @var \Stash\Pool $client
     */
    protected $stash = null;

    public function __construct(\Stash\Pool $client, $ttl = 3600, $cachePrefix = false)
    {
        parent::__construct($ttl, $cachePrefix);
        $this->stash = $client;
    }

    protected function checkExtension()
    {
        if (!class_exists('\Stash\Pool', true)) {
            throw new Ejsmont\CircuitBreaker\Storage\StorageException("Stash not installed?");
        }
    }

    protected function load($key)
    {
        /* md5 the key, as stash strtolowers it we can't otherwise enforce case sensitivity */
        $key = md5($key);
        try {
            return $this->stash->getItem($key)->get();
        } catch (\Exception $e) {
            throw new StorageException("Failed to load stash key: $key", 1, $e);
        }
    }

    protected function save($key, $value, $ttl)
    {
        /* md5 the key, as stash strtolowers it we can't otherwise enforce case sensitivity */
        $key = md5($key);
        try {
            $item = $this->stash->getItem($key);
            $item->set($value);
            $item->expiresAfter($ttl);
            $this->stash->save($item);
        } catch (\Exception $e) {
            throw new StorageException("Failed to save stash key: $key", 1, $e);
        }
    }
}
