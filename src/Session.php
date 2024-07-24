<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Facades\Cache;

class Session
{
    // Initializing private properties
    private $data;
    private $storage_key;
    private $initialized_key;

    /**
     * Constructor to initialize the class properties
     * 
     * @param $key string
     * 
     * @return void
     */
    public function __construct($key)
    {
        // Setting the storage key for this session
        $this->storage_key = $key;

        // Setting the initialized key for this session
        $this->initialized_key = "{$this->storage_key}_initialized";

        // Adding the initialized key with a TTL of one minute
        if (!Cache::add($this->initialized_key, true, 8)) {
            throw new \InvalidArgumentException("Session with key '$key' already exists.");
        }

        // Retrieving session data from cache or setting default empty object
        $this->data = cache($this->storage_key) ?? (object) [];
    }

    /**
     * Destructor to save session data in cache on object destruction
     * 
     * @return void
     */
    public function __destruct()
    {
        // Saving the session data to cache
        cache([$this->storage_key => $this->data], now()->addDays(15));

        Cache::forget($this->initialized_key);
    }

    /**
     * Method to add or update a key-value pair to session data
     * 
     * @param $key string
     * @param $value mixed
     * 
     * @return void
     */
    public function remember(string|object|array $key, $value = null)
    {
        // Adding or updating the key-value pair to session data
        if (is_array($key) || is_object($key)) {
            foreach ($key as $k => $v) {
                if (is_numeric($k)) {
                    throw new \Exception('Invalid key: numeric indexes when array passed are not allowed');
                } else {
                    $this->data->{$k} = $v;
                }
            }
        } else {
            $this->data->{$key} = $value;
        }
    }

    /**
     * 
     */
    public function setNext(string $method, string $controller = null)
    {
        $this->setMethod($method);
        if (!is_null($controller)) {
            $this->setController($controller);
        }
    }

    /**
     * Checks if session has next method
     */
    public function hasNext()
    {
        return (bool) $this->get('method') && $this->get('controller');
    }

    //TODO Check if controller exists
    private function setController(string $controller)
    {
        $this->remember(
            'controller',
            $controller
        );
    }

    // Add another method



    //TODO Check if method exists
    private function setMethod(string $method)
    {
        $this->remember('method', $method);
    }

    /**
     * Method to remove a key from session data
     * If the parameter is 'all', then removes all data from session
     * 
     * @param $key string
     * 
     * @return void
     */
    public function forget(string $key = 'all', bool $silentFail = false)
    {
        if ($key === 'all') {
            $this->data = (object) [];
        } else if (isset($this->data?->{$key})) {
            unset($this->data->{$key});
        }
        return false;
    }

    /**
     * Method to retrieve a value for the given key from session data
     * 
     * @param $key string
     * 
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        try {
            return $this->data->{$key} ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
