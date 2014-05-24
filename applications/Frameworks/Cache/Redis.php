<?php
/**
 * Class MSRedis
 *
 * @author PhpGame
 */

class MSRedis {
    static protected $connections = array();

    static public function getConnectionByName($name)
    {

        if (!isset(self::$connections[$name])) {
            $config = Registry::get('serverConfig');
            if (empty($config['RedisServer'][$name])) {
                throw new Exception("can not find redis server '$name' in global config");
            } else {
                if (isset($config['RedisServer'][$name]['db'])) {
                    self::$connections[$name] = new RedisConnection($config['RedisServer'][$name]['host'], $config['RedisServer'][$name]['port'], $config['RedisServer'][$name]['db']);
                } else {
                    self::$connections[$name] = new RedisConnection($config['RedisServer'][$name]['host'], $config['RedisServer'][$name]['port']);
                }
            }
        }
        return self::$connections[$name];
    }
}


class RedisConnection {
    /**
     * The redis client
     * @var Redis
     */
    protected $_client;

    /**
     * The redis server name
     * @var string
     */
    public $hostname = "localhost";

    /**
     * The redis server port
     * @var integer
     */
    public $port=6379;

    private $db = 0;

    private $smartForceRefresh;

    public function __construct($hostname, $port, $db=0) {
        $this->hostname = $hostname;
        $this->port     = $port;
        $this->db       = $db;
    }

    /**
     * getForceRefreshKey 获取强制刷新的值
     * @return mixed
     */
    public function getForceRefreshKey() {
        if (defined('MS_FORCE_REFRESH_VAR_NAME'))
            return MSSystem::GetRequest(MS_FORCE_REFRESH_VAR_NAME);
        return null;
    }

    /**
     * shouldSmartForceRefresh 获取是否需要强制刷新缓存数据
     * @param  mixed $key 可以指定特定的值来强制刷新
     * @return bool
     */
    public function shouldSmartForceRefresh($key = null) {
        $doForceRefresh = false;
        $forceRefreshKey = $this->getForceRefreshKey();
        if ($this->smartForceRefresh) {
            $doForceRefresh = true;
        } else if ($forceRefreshKey) {
            if ($forceRefreshKey == 1 || ($key && $forceRefreshKey == $key))
                $doForceRefresh = true;
        }
        return $doForceRefresh;
    }

    /**
     * Sets the redis client to use with this connection
     * @param Redis $client the redis client instance
     */
    public function setClient(MSRedis $client)
    {
        $this->_client = $client;
    }

    /**
     * Gets the redis client
     * @return Redis the redis client
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new MSRedis;
            $this->_client->connect($this->hostname, $this->port);
            if ($this->db) {
                $this->_client->select($this->db);
            }
        }
        return $this->_client;
    }

    /**
     * Returns a property value based on its name.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to read a property
     * <pre>
     * $value=$component->propertyName;
     * </pre>
     * @param string $name the property name
     * @return mixed the property value
     * @see __set
     */
    public function __get($name) {
        $getter='get'.$name;
        if (property_exists($this->getClient(),$name)) {
            return $this->getClient()->{$name};
        } elseif (method_exists($this->getClient(),$getter)) {
            return $this->$getter();
        }
        return null;
    }

    /**
     * Sets value of a component property.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to set a property
     * <pre>
     * $this->propertyName=$value;
     * </pre>
     * @param string $name the property name
     * @param mixed $value the property value
     * @return mixed
     * @see __get
     */
    public function __set($name,$value)
    {
        $setter='set'.$name;
        if (property_exists($this->getClient(),$name)) {
            return $this->getClient()->{$name} = $value;
        } elseif(method_exists($this->getClient(),$setter)) {
            return $this->getClient()->{$setter}($value);
        }
        return null;
    }

    /**
     * Checks if a property value is null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using isset() to detect if a component property is set or not.
     * @param string $name the property name
     * @return boolean
     */
    public function __isset($name)
    {
        $getter='get'.$name;
        if (property_exists($this->getClient(),$name)) {
            return true;
        } elseif (method_exists($this->getClient(),$getter)) {
            return true;
        }
        return false;
    }

    /**
     * Sets a component property to be null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using unset() to set a component property to be null.
     * @param string $name the property name or the event name
     * @return mixed
     */
    public function __unset($name)
    {
        $setter='set'.$name;
        if (property_exists($this->getClient(),$name)) {
            $this->getClient()->{$name} = null;
        } elseif(method_exists($this,$setter)) {
            $this->$setter(null);
        }
    }
    /**
     * Calls a method on the redis client with the given name.
     * Do not call this method. This is a PHP magic method that we override to
     * allow a facade in front of the redis object.
     * @param string $name the name of the method to call
     * @param array $parameters the parameters to pass to the method
     * @return mixed the response from the redis client
     */
    public function __call($name, $parameters) {
        return call_user_func_array(array($this->getClient(),$name),$parameters);
    }
}

/**
 *   $CONFIG['RedisServers'] = array(
 *      array(
 *          'host' => '127.0.0.1',
 *          'port' => '6380'
 *      ),
 *      array(
 *          'host' => '127.0.0.1',
 *          'port' => '6381'
 *      ),
 *   );
 */
class RedisDistributed {
    public $instance = null;
    private static $redis = null;
    private $config = array();


    private function __construct() {
        $config = Registry::get('serverConfig');
        if ((!isset($config['RedisServers']) || !$config['RedisServers']) && is_array($config['RedisServers'])) {
            throw new Exception("can not find redis distributed server configuration in global config");
        }
        try {
            foreach ($config['RedisServers'] as $server) {
                $this->config[] = $server['host'].':'.$server['port'];
            }
            $this->instance = new RedisArray($this->config);
        }  catch (Exception $e) {
            throw $e;
        }
    }

    private function __clone() {

    }

    public static function instance(){
        if (!self::$redis) {
            self::$redis = new self();
        }
        return self::$redis->instance;
    }
}