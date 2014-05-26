<?php
/**
 * Class Library_Model_Base
 *
 * @author PhpGame
 */

require_once ('Config/config.php');
require_once (MS_FRAMEWORK_ROOT . 'Engine.php');

// DbConn::$SqlMonitorCallbackGlobal = array('DbConn', 'SqlMonitorCallback_MonologDebug');

Registry::set('serverConfig',   $CONFIG);
