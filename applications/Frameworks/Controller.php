<?php
/**
 * Class Controller
 *
 * @author PhpGame
 */

class Controller {

    /** @var Engine */
    protected $engine;

    protected $actionUrlPrefix;
    protected $actionPathFields;

    /** @var TemplateEngine */
    protected $templateEngine;
    /**
     *
     * @var TipMessageManager
     */
    protected $tipMessageManager = null;

    /**
     * @param $siteEngine SiteEngine
     */
    public function  __construct($engine)
    {
        $this->engine = $engine;

        $this->actionPathFields = $engine->getRoutePathFields();
    }

    /**
     * @static
     * @param $engine Engine
     * @return Controller
     */
    static public function CreateActionHandler($engine)
    {
        $className = get_called_class();
        /** @var $controller Controller */
        $controller = new $className($engine);
        $controller->initialize();
        return $controller;
    }

    public function __call($name, $arguments)
    {
        $s = join('/', $this->actionPathFields);
        throw new Exception("unknown call to MSViewController: $name ($s)");
    }

    /**
     * @return Engine
     */
    public function getEngine()
    {
        return $this->engine;
    }
}

