<?php
spl_autoload_register("Mono_Logger_Autoloader");

function Mono_Logger_Autoloader($class) {
    if (substr($class, 0, 4) == 'Mono') {
        if (file_exists($file = dirname(__FILE__) . '/../' . str_replace(array('_', "\0"), array('/', ''), $class) . '.php')) {
            require $file;
        }
    }
}

class Mono_Logger
{
    const DEBUG = 100;
    const INFO = 200;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500; //Critical conditions (component unavailable, etc.)
    const ALERT = 550; //Action must be taken immediately (entire service down)
    const DATA = 999; //show always be logged

    protected static $levels = array(
            100 => 'DEBUG',
            200 => 'INFO',
            300 => 'WARNING',
            400 => 'ERROR',
            500 => 'CRITICAL',
            550 => 'ALERT',
            999 => 'DATA',
    );

    protected $name;

    /**
     * The handler stack
     *
     * @var array of Mono\Handler\Mono_Handler_Interface
     */
    protected $handlers = array();

    protected $processors = array();

    /**
     * @param string $name The logging channel
    */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Pushes an handler on the stack.
     *
     * @param Mono_Handler_Interface $handler
     */
    public function pushHandler(Mono_Handler_Interface $handler)
    {
        array_unshift($this->handlers, $handler);
    }

    /**
     * Adds a log record.
     *
     * @param integer $level The logging level
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = array(), array $extra = array() )
    {
        if (!$this->handlers) {
            $this->pushHandler(new Mono_Handler_Stream('php://stderr', self::DEBUG));
        }
        $record = array(
                'channel' => $this->name,
                'datetime' => new DateTime(),
                'level' => $level,
                'level_name' => self::getLevelName($level),
                'message' => (string) $message,
                'context' => $context,
                'extra' => $extra,
        );

        // check if any message will handle this message
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling($record)) {
                $handlerKey = $key;
                break;
            }
        }
        // none found
        if (null === $handlerKey) {
            return false;
        }
        // found at least one, process message and dispatch it
        // we don't use this feature currently.
        // foreach ($this->processors as $processor) {
        //     $record = call_user_func($processor, $record);
        // }
        while (isset($this->handlers[$handlerKey]) &&
                false === $this->handlers[$handlerKey]->handle($record)) {
            $handlerKey++;
        }

        return true;
    }

    static public function getLevelName($level)
    {
        return self::$levels[$level];
    }

    static protected $DefaultLogsRootPath;
    static public function SetDefaultLogsRootPath($path)
    {
        self::$DefaultLogsRootPath = $path;
    }
    static public function GetDefaultLogsRootPath()
    {
        return self::$DefaultLogsRootPath;
    }
}

/**
 *  log message
 * @param string $level  Mono_Loggger::DEBUG etc
 * @param string $channel
 * @param string $message
 * @param string $context
 * @return void
 * @author Jayson Xu <superjavason@gmail.com>
 */
function monolog($level, $channel, $message, array $context = array()) {
    $config = Registry::get('monoLogger');
    try{
        $log_config = null;

        //通过channel找到Logger的配置，如果全名匹配失败，则按照顶级分类匹配，如果都失败，则用default默认。
        if(isset($config['Logger'][$channel])) {
            $log_config = $config['Logger'][$channel];
        }
        if( $log_config === null) {
            $channel_fields = explode('/', $channel);
            if(isset($config['Logger'][$channel_fields[0]])) {
                $log_config = $config['Logger'][$channel_fields[0]];
            }
        }

        if( $log_config !== null) {
            if( ! isset($log_config['Level']))
                $log_config['Level'] = isset($config['Logger']['default']['Level']) ? $config['Logger']['default']['Level'] : Mono_Logger::ERROR;
            if( empty($log_config['Handlers']))
                throw new Exception ("no handlers for '$channel'");
        }
        else {
            $log_config = isset($config['Logger']['default']) ? $config['Logger']['default'] : array();

            //如果没配置 default 默认log系统，则取默认值，记录 error 到项目log目录中
            if( ! isset($log_config['Level']))
                $log_config['Level'] = Mono_Logger::ERROR;
            if( ! isset($log_config['Handlers'])) {
                $defaultLogsRootPath = Mono_Logger::GetDefaultLogsRootPath();
                if( empty($defaultLogsRootPath) )
                    throw new Exception ('no Mono_Logger DefaultLogsRootPath configurated');

                $log_config['Handlers'] = array(
                        array( 'class' => 'Mono_Handler_LineFileEveryDay', 'params'=>array('basePath' => $defaultLogsRootPath)),
                );
            }
        }

        $logger = new Mono_Logger($channel);
        foreach($log_config['Handlers'] as $handler) {
            $h = new $handler['class']();
            $h->setLevel($log_config['Level']);
            if(isset($handler['params'])) {
                foreach($handler['params'] as $name=>$value) {
                    $h->$name = $value;
                }
            }
            $logger->pushHandler($h);
        }
        $extra = array();
        $logger->addRecord($level, $message, $context, $extra);
    } catch(Exception $e) {
        //捕获logger包可能抛出的异常，用php内置出错处理日志
        $time = date("Y-m-d H:i:s e");
        error_log("[$time]" . $e->getMessage() . ":" . $e->getTraceAsString() . "\n");
    }
}

function monolog_debug($channel, $message, array $context = array()) {
    monolog(Mono_Logger::DEBUG,$channel, $message, $context);
}
function monolog_info($channel, $message, array $context = array()) {
    monolog(Mono_Logger::INFO,$channel, $message, $context);
}
function monolog_warning($channel, $message, array $context = array()) {
    monolog(Mono_Logger::WARNING,$channel, $message, $context);
}
function monolog_error($channel, $message, array $context = array()) {
    monolog(Mono_Logger::ERROR,$channel, $message, $context);
}
function monolog_data($channel, $message, array $context = array()) {
    monolog(Mono_Logger::DATA,$channel, $message, $context);
}
