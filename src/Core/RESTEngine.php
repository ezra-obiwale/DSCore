<?php

namespace dScribe\Core;

use dScribe\View\View,
    ReflectionMethod,
    Session,
    Util;

class RESTEngine extends Engine {

    public static function getModule() {
        $urls = static::getUrls();
        if (!empty($urls) && count($urls) >= 2) {
            $return = $urls[1];
        }
        else {
            $return = static::getDefaultModule();
        }
        return static::checkAlias(ucfirst(Util::hyphenToCamel($return)));
    }

    public static function getController($exception = true) {
        $urls = static::getUrls();

        if (!empty($urls) && count($urls) >= 3) {
            $return = ucfirst($urls[2]);
        }
        else {
            if (!$return = static::getDefaultController(static::getModule())) {
                if ($exception)
                    ControllerException::notFound();
                return '-';
            }
        }


        return ucfirst(Util::hyphenToCamel($return));
    }

    public static function getAppId() {
        $urls = static::getUrls();
        return $urls[0];
    }

    public static function getAction() {
        $request = new Request();
        $action = static::getConfig('REST', $request->method, false);
        switch ($request->method) {
            case 'POST': // Create
                $return = $action ? $action : 'new';
                break;
            case 'GET': // Read
                if (static::getParams()) { // get a model
                    if (is_array($action) && $action['withParam'])
                        $return = $action['withParam'];
                    else if ($action)
                        $return = $action;
                    else
                        $return = 'view';
                }
                else { // get all models
                    if (is_array($action) && $action['all'])
                        $return = $action['all'];
                    else if ($action)
                        $return = $action;
                    else
                        $return = 'index';
                }
                break;
            case 'PUT': // Update
                $return = $action ? $action : 'edit';
                break;
            case 'DELETE': // Delete
                $return = $action ? $action : 'delete';
                break;
        }

        return lcfirst(Util::hyphenToCamel($return));
    }

    /**
     * Starts the engine
     * @param array $config
     * @todo Check caching controller actions
     */
    public static function run(array $config) {
        static::init($config);
        static::moduleIsActivated();
        $cache = static::canCache();
        $name = join('/', static::getUrls());
        if ($cache && $out = $cache->fetch($name)) {
            echo $out;
        }
        else {
            $controller = static::getControllerClass();
            $action = static::getAction();
            if (!in_array($action, $controller->getActions())) {
                ControllerException::invalidAction($action);
            }

            $params = static::getParams();
            static::authenticate($controller, $action, $params);

            $refMethod = new ReflectionMethod($controller, $action . 'Action');

            if (count($params) < $refMethod->getNumberOfRequiredParameters()) {
                ControllerException::invalidParamCount();
            }
            $actionRet = call_user_func_array(array($controller, $action . 'Action'), $params);

            if (!is_array($actionRet)) {
                ControllerException::invalidActionResult();
            }
            static::terminate();
            
            header('Content-Type: application/json');
            die(json_encode($actionRet));
        }
    }

}
