<?php
/**
 * Class Library_Base
 *
 * @author PhpGame
 */

class Library_Base
{
    protected static $Instances;
    protected $cache;

    public function __construct()
    {
        $this->cache = MSMemcache::GetInstance('default', '');
    }

    public function getCache()
    {
        return $this->cache;
    }

    /**
     *
     * @return AuthLib_Base
     */
    static public function Instance()
    {
        return self::InstanceInternal(__CLASS__);
    }
    
    static protected function InstanceInternal($className)
    {
        if (!isset(self::$Instances[$className]))
            self::$Instances[$className] = new $className();
        return self::$Instances[$className];
    }

    public static function enkey($key)
    {
        $klen = strlen($key);
        //反转
        $revkey = strrev($key);
        //交换
        for ($j = 1; $j < $klen; $j += 2) {
            $ts = $revkey[$j - 1];
            $revkey[$j - 1] = $revkey[$j];
            $revkey[$j] = $ts;
        }
        //替换
        $n = 1;
        $m = 1;
        while ($m < $klen) {
            $revkey[$m] = $key[$m];
            $m = pow(2, $n++);
        }

        return $revkey;
    }

    public static function getUserIdByKey($key)
    {
        $userinfo = MSMemcache::GetInstance('default', '')->get($key);
        if ($userinfo) {
            return $userinfo['u'];
        } else {
            return false;
        }
    }

    public static function getRoleIdByKey($key)
    {
        try {
            $userinfo = MSMemcache::GetInstance('default', '')->get($key);
        } catch (Exception $ex) {
            
        }
        if ($userinfo) {
            if (!isset($userinfo['r']) || empty($userinfo['r'])) {
                return false;
            }
            return $userinfo['r'];
        } else {
            return false;
        }
    }
}
