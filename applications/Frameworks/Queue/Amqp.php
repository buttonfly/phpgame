<?php
/**
 * Class Amqp
 *
 * @author PhpGame
 */

class Amqp
{
    static protected $connections = array();

    static public function getConnectionByName($name)
    {
        if (!isset(self::$connections[$name]) || !self::$connections[$name]->getClient()) {
            $config = Registry::get('serverConfig');
            if (empty($config['RabbitMQ'][$name])) {
                throw new Exception("can not find RabbitMQ server '$name' in global config");
            } else {
                self::$connections[$name] = new AmqpClient(
                    $config['RabbitMQ'][$name]['host'],
                    $config['RabbitMQ'][$name]['port'],
                    $config['RabbitMQ'][$name]['vhost'],
                    $config['RabbitMQ'][$name]['username'],
                    $config['RabbitMQ'][$name]['password']
                );
            }
        }
        return self::$connections[$name];
    }
}

/**
 * AMQP extension wrapper to communicate with RabbitMQ server
* Send and recieve messages. Implements Wrapper template.
* For More documentation please see:
* http://php.net/manual/en/book.amqp.php
*/
class AmqpClient
{
    protected $client = null;

    public function __construct($host, $port, $vhost, $username, $password) {

        $this->client = new AMQPConnection(array(
                'host' => $host,
                'vhost' => $vhost,
                'port' => $port,
                'login' => $username,
                'password' => $password,
        ));
        //Autoconnect for pecl extension
        if (method_exists($this->client, 'connect') && $this->client->isConnected() == false) {
            $this->client->connect();
        }
    }

    /**
     * Declares a new Exchange on the broker
     * @param $name
     * @param $flags
     */
    public function declareExchange($name, $type = AMQP_EX_TYPE_DIRECT, $flags = NULL)
    {
        $channel = new AMQPChannel($this->client);
        $ex = new AMQPExchange($channel);
        $ex->setName($name);
        $ex->setType($type);
        $ex->setFlags($flags);
        $ex->declare();
        return $ex;
    }

    /**
     * Declares a new Queue on the broker
     * @param $name
     * @param $flags
     */
    public function declareQueue($name, $flags = NULL)
    {
        $channel = new AMQPChannel($this->client);
        $queue = new AMQPQueue($channel);
        $queue->setName($name);
        $queue->setFlags($flags);
        $queue->declare();
        return $queue;
    }

    /**
     * Returns an instance of AMQPExchange for exchange a queue is bind
     * @param $exchange
     * @param $queue
     * @param $routingKey
     */
    public function bindExchangeToQueue($exchange, $queue, $routingKey = "")
    {
        $exchange = $this->exchange($exchange);
        $exchange->bind($queue, $routingKey);
        return $exchange;
    }

    /**
     * Get exchange by name
     * @param $name  name of exchange
     * @return  object AMQPExchange
     */
    public function exchange($name)
    {
        $channel = new AMQPChannel($this->client);
        $ex = new AMQPExchange($channel);
        $ex->setName($name);
        return $ex;
    }

    /**
     * Binds a queue to specified exchange
     * Returns an instance of AMQPQueue for queue an exchange is bind
     * @param $queue
     * @param $exchange
     * @param $routingKey
     */
    public function bindQueueToExchange($queue, $exchange, $routingKey = "")
    {
        $queue = $this->queue($queue);
        $queue->bind($exchange, $routingKey);
        return $queue;
    }

    /**
     * Get queue by name
     * @param $name  name of exchange
     * @return  object AMQPQueue
     */
    public function queue($name)
    {
        $channel = new AMQPChannel($this->client);
        $queue   = new AMQPQueue($channel);
        $queue->setName($name);
        return $queue;
    }

    /**
     * Returns AMQPConnection instance
     *
     * @return AMQPConnection
     */
    public function getClient()
    {
        return $this->client;
    }

    public function disconnect(){
        if (method_exists($this->client, 'disconnect') && $this->client->isConnected()) {
            $r = $this->client->disconnect();
            $this->client = null;
            return $r;
        }
        return false;
    }
}