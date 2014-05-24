<?php
/**
 * Class ClassAutoLoader
 *
 * @author PhpGame
 */

if (!defined('MS_DEBUG'))
    define('MS_DEBUG', DEBUG);
if (get_magic_quotes_gpc())
    die('Please check "magic quotes gpc"');

define('MS_LOG_EMERG',    0);
define('MS_LOG_ALERT',    1);
define('MS_LOG_CRIT',     2);
define('MS_LOG_ERR',      3);
define('MS_LOG_WARNING',  4);
define('MS_LOG_NOTICE',   5);
define('MS_LOG_INFO',     6);
define('MS_LOG_DEBUG',    7);

require_once MS_FRAMEWORK_ROOT . 'Db/DbConn.php';
require_once MS_FRAMEWORK_ROOT . 'Cache/Redis.php';
require_once MS_FRAMEWORK_ROOT . 'Queue/Amqp.php';
require_once MS_FRAMEWORK_ROOT . 'Cache/Memcache.php';
require_once MS_FRAMEWORK_ROOT . 'Cache/Registry.php';

class ClassAutoLoader
{
    static protected $PrefixRules = array();
    const PrefixRuleType_PathPrefix = 'PathPrefix';
    const PrefixRuleType_Callback = 'Callback';

    static public function Load($name)
    {
        $a = explode('_', $name);
        $classNamePrefix = $a[0];
        if (isset(self::$PrefixRules[$classNamePrefix])) {
            $rule = self::$PrefixRules[$classNamePrefix]['Rule'];
            if (self::$PrefixRules[$classNamePrefix]['Type'] == self::PrefixRuleType_PathPrefix)
                $fn = $rule . join('/', $a) . '.class.php';
            else if (self::$PrefixRules[$classNamePrefix]['Type'] == self::PrefixRuleType_Callback)
                return call_user_func($rule, $name);
            else
              $fn = MS_APP_ROOT . join('/', $a) . '.class.php';
            if (file_exists($fn)) {
                require_once $fn;
                return true;
            }
        } else {
            $getClassFile = function ($fillArray) {
                foreach ($fillArray as $file) {
                    if (file_exists($file)) {
                        return $file;
                    }
                }
                return false;
            };

            $fillArray = array(
                    MS_APP_ROOT . '../' . join('/', $a) . '.class.php',
                    MS_APP_ROOT . '../' . join('/', $a) . '.action.php',
            );
            $fn = $getClassFile($fillArray);
            
            if ($fn === false) {
                return false;
            }
            require_once $fn;
            return true;
        }
        return false;
    }

    static public function SetPrefixRule($classNamePrefix, $type, $rule)
    {
        self::$PrefixRules[$classNamePrefix] = array(
                'Type' => $type,
                'Rule' => $rule,
        );
    }
}

spl_autoload_register(array('ClassAutoLoader', 'Load'));

function SystemErrorLog($level, $class, $title, $content = null)
{
    static $depth = 0;
    static $count = 0;
    static $ErrorLogHandler;
    static $ErrorLogIgnoreExportFunctionPatterns = array();
    static $ErrorLogVarExport = array('MSSystem', 'VarExportScalarAndArray');
    
    if ($depth >= 3)
        return false;

    $depth ++;

    $datetime = date('Y-m-d H:i:s');

    if (!is_null($content) && !is_string($content))
        $content = serialize($content);

    if ($depth >= 2) {
        if ($depth == 2) {
            if ($content)
                error_log("[$datetime][$level][$class](too deep) $title\n$content\n\n");
            else
              error_log("[$datetime][$level][$class](too deep) $title\n\n");
        }
        $depth --;
        return false;
    }


    $ignoreExportFunctionPatterns = $ErrorLogIgnoreExportFunctionPatterns;
    $ignoreExportFunctionPatterns[] = '/^Smarty/';
    $ignoreExportFunctionPatterns[] = '/^MS/';
    $ignoreExportFunctionPatterns[] = '/^Mono/';
    $ignoreExportFunctionPatterns[] = '/^PDO->__construct/';

    $count++;

    $backtrace = debug_backtrace();
    $backtraceString = '';
    foreach ($backtrace as $i => $call) {
        $fullClassFunction = '';
        if (isset($call['class']))
            $fullClassFunction = $call['class'] . $call['type'];
        if (isset($call['function']))
            $fullClassFunction .= $call['function'];

        $backtrace[$i]['fullClassFunction'] = $fullClassFunction;
        $call['fullClassFunction'] = $fullClassFunction;

        $exportedArgs = '';

        $shouldIgnoreExport = ($i == 0);  // ignore the first call (this function ...)
        foreach ($ignoreExportFunctionPatterns as $pattern) {
            if (preg_match($pattern, $fullClassFunction)) {
                $shouldIgnoreExport = true;
                break;
            }
        }

        if (!$shouldIgnoreExport) {
            $exportedArgs = array();
            if (isset($call['args'])) {
                foreach ($call['args'] as $j=>$arg) {
                    $exportedArgs[] = call_user_func($ErrorLogVarExport, $arg);
                }
            }
            $exportedArgs = join(',', $exportedArgs);
        }
        $fileLine = '';
        if (isset($call['file']))
            $fileLine .= $call['file'];
        $fileLine .= ':';
        if (isset($call['line']))
            $fileLine .= $call['line'];

        $backtrace[$i]['fileLine'] = $fileLine;
        $backtraceString .= "#[$i] {$fullClassFunction}($exportedArgs) @ [$fileLine]\n";
    }

    $isLogHandled = false;
    if (!empty($ErrorLogHandler)) {
        try {
            $isLogHandled = call_user_func($ErrorLogHandler, compact('level', 'class', 'title', 'content', 'backtrace', 'backtraceString'));
        } catch (Exception $e) {
            error_log("[$datetime]" . $e->getMessage());
        }
    }

    if (!$isLogHandled) {
        if ($content)
            $isLogHandled = error_log("[$datetime][$level][$class] $title\n$content\n$backtraceString\n\n");
        else
           $isLogHandled = error_log("[$datetime][$level][$class] $title\n$backtraceString\n\n");
    }

    $depth --;
    return $isLogHandled;
}

function SystemErrorHandler($errno, $errstr, $errfile, $errline)
{
    //E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING can not be handled
    $errors = array(
            1=>'E_ERROR',
            2=>'E_WARNING',
            4=>'E_PARSE',
            8=>'E_NOTICE',
            16=>'E_CORE_ERROR',
            32=>'E_CORE_WARNING',
            64=>'E_COMPILE_ERROR',
            128=>'E_COMPILE_WARNING',
            256=>'E_USER_ERROR',
            512=>'E_USER_WARNING',
            1024=>'E_USER_NOTICE',
            2048=>'E_STRICT',
            4096=>'E_RECOVERABLE_ERROR',
            8192=>'E_DEPRECATED',
            16384=>'E_USER_DEPRECATED',
    );

    $loglevel = MS_LOG_WARNING;
    if ($errno == E_ERROR
            || $errno == E_PARSE
            || $errno == E_CORE_ERROR
            || $errno == E_COMPILE_ERROR
            || $errno == E_USER_ERROR
            || $errno == E_RECOVERABLE_ERROR
    ) {
        $loglevel = MS_LOG_ERR;
    }
    if (isset($errors[$errno]))
        $type = $errors[$errno];
    else
       $type = $errno;

    $logtitle = "$type $errstr @ [$errfile:$errline]";

    SystemErrorLog($loglevel, "php", $logtitle);

    // 'false' to continue build-in handler (show error messages), some document said "null" is wrong.
    return false;
}

set_error_handler('SystemErrorHandler', E_ALL);
