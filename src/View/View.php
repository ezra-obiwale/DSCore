<?php

namespace dScribe\View;

use dScribe\Core\AController,
    dScribe\Core\Flash,
    Util;

class View {

    /**
     * Variables to pass into view files
     * @var array
     */
    protected $variables;

    /**
     * The current module
     * @var string
     */
    protected $module;

    /**
     * The current controller
     * @var AController
     */
    protected $controller;

    /**
     * The current action
     * @var string
     */
    protected $action;

    /**
     * Parameters passed into the action
     * @var array
     */
    protected $params;

    /**
     * Indicates whether to run a partial view or not
     * @var boolean
     */
    protected $partial;

    /**
     * File to view
     * @var string
     */
    protected $viewFile;

    /**
     * Instance of the flash messenger
     * @var Flash
     */
    protected $flash;

    /**
     *
     * @var Renderer
     */
    private $renderer;

    /**
     * Class constructor
     */
    final public function __construct($initialize = true) {
        if ($initialize) {
            $this->variables = $this->viewFile = array();
        }

        $this->module = ucfirst(engineGet('module'));
        $this->params = engineGet('params');
    }

    /**
     * Sets the variables to pass into the view file
     * @param array $variables
     * @return View
     */
    final public function variables(array $variables) {
        $this->variables = array_merge($this->variables, $variables);
        return $this;
    }

    /**
     * Fetches the variables to pass into the view file
     * @param string $varName Name of variable to fetch. If this is null, all 
     * variables will be returned in an array
     * @return mixed
     */
    final public function getVariables($varName = null) {
        return ($varName) ? $this->variables[$varName] : $this->variables;
    }

    /**
     * Fetches the current module
     * @return string
     */
    final public function getModule() {
        return $this->module;
    }

    /**
     * Override the current module
     * @param string $module
     * @return View
     */
    final public function setModule($module) {
        $this->module = $module;
        return $this;
    }

    /**
     * Sets the current controller
     * @param AController $controller
     * @return View
     */
    final public function setController(AController $controller) {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Fetches the current controller
     * @param boolean $live Indicates whether to return a model or string
     * @return AController
     */
    final public function getController($live = true) {
        return ($live) ? $this->controller : $this->controller->getClass();
    }

    /**
     * Sets the current action
     * @param string $action
     * @return View
     */
    final public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * Fetches the current action
     * @return string
     */
    final public function getAction() {
        return $this->action;
    }

    /**
     * Fetches the parameters sent into the current action
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Override the parameters sent to the current action
     * @param array $params
     * @return View
     */
    public function setParams(array $params) {
        $this->params = $params;
        return $this;
    }

    /**
     * Indicates that the view file should not load any layout
     * @return View
     */
    final public function partial() {
        $this->partial = true;
        return $this;
    }

    /**
     * Checks if to render just the view file without the layouts
     * @return boolean
     */
    final public function isPartial() {
        return $this->partial;
    }

    /**
     * Determines which action's view to use instead of current's
     * @param string $_ You may pass 1 to 3 parameters as [module],[controller],action
     * @param bool $fromVendor Set to true if to load the first parameter from the
     * vendor directory
     * @return View
     */
    final public function file($_, $fromVendor = false) {
        $args = func_get_args();

        if (count($args) == 2 && is_bool($fromVendor) && $fromVendor) {
            $this->viewFile = VENDOR . $_ . '.phtml';
        }
        else {
            if (count($args) > 3) {
                for ($i = 3; $i < count($args); $i++) {
                    unset($args[$i]);
                }
            }

            switch (count($args)) {
                case 1:
                    $this->viewFile[2] = Util::camelToHyphen($args[0]);
                    break;
                case 2:
                    $this->viewFile[1] = Util::camelToHyphen($args[0]);
                    $this->viewFile[2] = Util::camelToHyphen($args[1]);
                    break;
                case 3:
                    $this->viewFile[0] = ucfirst(Util::hyphenToCamel($args[0]));
                    $this->viewFile[1] = Util::camelToHyphen($args[1]);
                    $this->viewFile[2] = Util::camelToHyphen($args[2]);
                    break;
            }
        }

        return $this;
    }

    /**
     * Fetches the view file array
     * @param boolean $addOthers Indicates whether to add missing parameters i.e.
     * module, controller, or action
     * @return array
     */
    final public function getViewFile($addOthers = true) {
        if ($addOthers && is_array($this->viewFile)) {
            if (!isset($this->viewFile[0]))
                $this->viewFile[0] = ucfirst(Util::hyphenToCamel(engineGet('module')));
            if (!isset($this->viewFile[1]))
                $this->viewFile[1] = Util::camelToHyphen(engineGet('controller'));
            if (!isset($this->viewFile[2]))
                $this->viewFile[2] = Util::camelToHyphen(engineGet('action'));

            ksort($this->viewFile);
        }
        return $this->viewFile;
    }

    /**
     * Fetches the relative path to a resource
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $params
     * @param string $hash 
     * @return string
     */
    final public function url($module, $controller = null, $action = null,
            array $params = array(), $hash = null) {
        $module = ucfirst(Util::hyphenToCamel($module));
        $moduleOptions = engineGet('config', 'modules', $module, false);
        $module = (isset($moduleOptions['alias'])) ? $moduleOptions['alias'] : $module;

        $return = Util::camelToHyphen($module);
        if ($controller) $return .= '/' . Util::camelToHyphen($controller);
        if ($action) $return .= '/' . Util::camelToHyphen($action);
        if (!empty($params))
                $return .= str_replace('//', '/' . urlencode(' ') . '/',
                    '/' . join('/', $this->encodeParams($params)));
        if ($hash) $return .= '#' . $hash;

        return engineGet('serverPath') . $return;
    }

    private function encodeParams(array $params) {
        foreach ($params as &$param) {
            $param = urlencode($param);
        }
        return $params;
    }

    /**
     * 
     * @return Renderer
     */
    final public function getRenderer() {
        if (!$this->renderer) {
            $this->renderer = new Renderer(false);
        }
        return $this->renderer->setView($this);
    }

    /**
     * Renders the view
     */
    final public function render($errorMessage = null) {
        $this->getRenderer()->render($errorMessage);
    }

    /**
     * Fetches the output of an action
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $params
     * @param boolean $partial Indicates whether to return partial or full view
     * @return string
     */
    final public function getOutput($module, $controller, $action,
            array $params = array(), $partial = true) {
        $view = new View();
        $controllerClass = ucfirst(\Util::hyphenToCamel($module)) . '\Controllers\\' . ucfirst(\Util::hyphenToCamel($controller)) . 'Controller';
        $controllerClass = new $controllerClass;
        $controllerClass->setView($view);
        $action = lcfirst(\Util::hyphenToCamel($action));
        $view->setController($controllerClass)
                ->setAction(lcfirst(\Util::hyphenToCamel($action)))
                ->setModule(ucfirst(\Util::hyphenToCamel($module)));
        $actionRet = call_user_func_array(array($controllerClass, $action . 'Action'),
                $params);
        if (is_object($actionRet) && is_a($actionRet, 'dScribe\View\View')) {
            $view = $actionRet;
        }
        else {
            $view->variables(($actionRet) ? $actionRet : array());
        }
        if ($viewFile = $view->getViewFile(false)) {
            if (!isset($viewFile[0]) && !isset($viewFile[1])) {
                $view->file($module, $controller, $viewFile[2]);
            }
            else if (!isset($viewFile[0])) {
                $view->file($module, $viewFile[1], $viewFile[2]);
            }
        }
        else {
            $view->file($module, $controller, $action);
        }

        if ($partial) {
            $view->partial();
        }

        ob_start();
        $view->render();
        return ob_get_clean();
    }

    /**
     * Fetches the current instance of the flash messenger
     * @return Flash
     */
    final public function flash() {
        return engineGet('flash');
    }

}
