<?php
/**
 * Class ApiLib_Model_Base
 *
 * @author PhpGame
 */

require_once ('Config/config.inc.php');
require_once (MS_FRAMEWORK_ROOT . 'Engine.php');
require_once (MS_FRAMEWORK_ROOT . 'Addon/Mono/Logger.php');

DbConn::$SqlMonitorCallbackGlobal = array('DbConn', 'SqlMonitorCallback_MonologDebug');

Registry::set('serverConfig',   $CONFIG);
if (defined('DEBUG') && DEBUG)
    Registry::set('monoLogger', $MonoLogger);
else
    Registry::set('monoLogger', array());
