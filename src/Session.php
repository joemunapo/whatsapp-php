<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Session
{
    protected $data;

    protected $storageKey;

    protected $ttl = 900; // 15 minutes

    public function __construct($key)
    {
        $this->storageKey = "whatsapp_session_{$key}";
        $this->data = Cache::get($this->storageKey, []);
    }

    public function get($key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    public function set($key, $value)
    {
        Arr::set($this->data, $key, $value);
        $this->save();
    }

    public function forget($key)
    {
        Arr::forget($this->data, $key);
        $this->save();
    }

    public function clear()
    {
        $this->data = [];
        $this->save();
    }

    protected function save()
    {
        Cache::put($this->storageKey, $this->data, $this->ttl);
    }

    public function __destruct()
    {
        $this->save();
    }
}
