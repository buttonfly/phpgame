<?php
/**
 * Class ClassAutoLoader
 *
 * @author PhpGame
 */

if (get_magic_quotes_gpc())
    die('Please check "magic quotes gpc"');

define('MS_LOG_EMERG',   0);
define('MS_LOG_ALERT',   1);
define('MS_LOG_CRIT',    2);
define('MS_LOG_ERR',     3);
define('MS_LOG_WARNING', 4);
define('MS_LOG_NOTICE',  5);
define('MS_LOG_INFO',    6);
define('MS_LOG_DEBUG',   7);

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

}

spl_autoload_register(array('ClassAutoLoader', 'Load'));

