<?php
/**
 * Class ApiLib_Model_Base
 *
 * @author PhpGame
 */

date_default_timezone_set ( 'Asia/Singapore' );
define('VISIT_LOG',true);
$MonoLogger = array();
if (defined ( 'DEBUG' ) && DEBUG) {
    $MonoLogger ['Logger'] = array (
            'SQL' => array (
                    'Level' => 100,
                    'Handlers' => array (
                            array (
                                    'class' => 'Mono_Handler_RawMessageFileEveryDay'
                            )
                    )
            ),
    );
}

if (defined('VISIT_LOG') && VISIT_LOG) {
    if(!isset($MonoLogger['Logger'])) {
        $MonoLogger['Logger'] = array();
    }
    $MonoLogger['Logger']['log'] = array(
            'Level' => 200,
            'Handlers' => array(
                    array(
                            'class' => 'Mono_Handler_RawMessageFileEveryDay'
                    )
            )
    );
}

define ( 'MS_APP_ROOT', dirname ( __FILE__ ) . DIRECTORY_SEPARATOR );
define ( 'MS_PROJECT_ROOT', MS_APP_ROOT );
define ( 'MS_PROJECT_LOGS_ROOT', MS_APP_ROOT . '/../../logs/' );
define ( 'MS_FRAMEWORK_ROOT', MS_APP_ROOT . '/../../Frameworks/' );

define ( 'DATABASE_GAMECITY', 'gamecity' );
define ( 'DATABASE_GAMECITY_READ', 'gamecity_read' );
