<?php
/**
 * 
 * 
 * @author walkor <worker-man@qq.com>
 * 
 */

class Store
{
    protected static $instance = null;
    
    public static function connect()
    {
        if(!self::$instance)
        {
            self::$instance = new Memcache;
            self::$instance->addServer('127.0.0.1', 11211);
        }
        return self::$instance;
    }
    
    public static function set($key, $value, $ttl = 0)
    {
        if(self::connect())
        {
            return self::$instance->set($key, $value, $ttl);
        }
        return false;
    }
    
    public static function get($key)
    {
        if(self::connect())
        {
            return self::$instance->get($key);
        }
        return false;
    }
   
    public static function delete($key)
    {
        if(self::connect())
        {
            return self::$instance->delete($key);
        }
        return false;
    }
   
}
