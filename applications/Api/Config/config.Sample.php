<?php
/**
 * config Sample
 *
 * @author PhpGame
 */

error_reporting(E_ALL);

date_default_timezone_set ( 'Asia/Shanghai' );

define ( 'MS_APP_ROOT', dirname ( __FILE__ ) . DIRECTORY_SEPARATOR );
define ( 'MS_PROJECT_ROOT', MS_APP_ROOT );
define ( 'MS_FRAMEWORK_ROOT', MS_APP_ROOT . '/../../Frameworks/' );
define ( 'DATABASE_GAMECITY', 'gamecity' );
define ( 'DATABASE_GAMECITY_READ', 'gamecity_read' );

$CONFIG['Database'][DATABASE_GAMECITY] = array(
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=dbGame',
        'user' => 'root',
        'password' => '888888',
        'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT=>120
        )
);

$CONFIG['Database'][DATABASE_GAMECITY_READ] = array(
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=dbGame',
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

