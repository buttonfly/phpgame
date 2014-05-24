<?php
/**
 * Class ApiLib_Model_Base
 *
 * @author PhpGame
 */

define('DEBUG', true);
error_reporting(E_ALL);
//define('VISIT_LOG',true);

require_once 'config_common.inc.php';

$CONFIG['Database'][DATABASE_GAMECITY] = array(
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=birds',
        'user' => 'root',
        'password' => '888888',
        'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT=>120
        )
);

$CONFIG['Database'][DATABASE_GAMECITY_READ] = array(
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=birds',
        'user' => 'root',
        'password' => '888888',
        'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT=>120
        )
);

$CONFIG['MemcachedServer']['default'] = array(
        'host' => '127.0.0.1',
        'port' => 11211
);

//跟nginx重写规则相关
$CONFIG['Rewrite']['Cmd'] = array(
    '1' => 'User', '2' => 'Map', '3' => 'Props'
);

$CONFIG['Rewrite']['Scmd'] = array(
    '1' => 'Login', '2' => 'Logout'
);

$CONFIG['Rewrite']['User'] = array(
        '1' => array('User', 'Login'),
        '2' => array('User', 'Logout'),
);

