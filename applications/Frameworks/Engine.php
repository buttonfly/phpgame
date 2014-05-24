<?php
/**
 * Class Engine
 *
 * @author PhpGame
 */

require_once MS_FRAMEWORK_ROOT . 'Framework.php';
require_once MS_FRAMEWORK_ROOT . 'Controller.php';

class Engine
{
    protected $routePathFields = array();

    /**
     * @var Controller
    */
    protected $controller;

    public function getRoutePathFields()
    {
        return $this->routePathFields;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        return $this->controller;
    }

    public function setRoutePath($s1, $s2, $data)
    {
        $config = Registry::get("serverConfig");
        $this->routePathFields[0] = $config['Rewrite']['Cmd'][$s1];
        $this->routePathFields[1] = $config['Rewrite']['Scmd'][$s2];
        Registry::set('GetData', $data);
        Registry::set('Cmd', $s1);
        Registry::set('Scmd', $s2);
    }

    public function run()
    {
        $foundControllerClassName = null;
        $tempControllerClassName = 'Controller_' . join('_', $this->routePathFields);
        if (ClassAutoLoader::Load($tempControllerClassName)) {
            $foundControllerClassName = $tempControllerClassName;
        } else {
            $tmpFields = $this->routePathFields;
            array_pop($tmpFields);
            $tempControllerClassName = 'Controller_' . join('_', $tmpFields);
            if (ClassAutoLoader::Load($tempControllerClassName) )
                $foundControllerClassName = $tempControllerClassName;
        }
        if (empty($foundControllerClassName)) {
            return false;
        } else {
            $lastActionField = end($this->routePathFields);
        }
        //then create the action handler
        $this->controller = $foundControllerClassName::CreateActionHandler($this);
        $actionMethod = "action_" . $lastActionField;
        return $this->controller->$actionMethod(); // 404 must be handled in base class's __call
    } //end if function run
}

