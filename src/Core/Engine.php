<?php

namespace DScribe\Core;

use DBScribe\Connection,
    DScribe\View\View,
    PDO,
    ReflectionMethod,
    Session,
    Util;

class Engine {

    protected static $config;
    protected static $userId;
    protected static $flash;
    protected static $isVirtual;
    protected static $db;
    protected static $urls;
    protected static $inject;
    protected static $serverPath;

    /**
     * Gets (settings from) the config file
     * @param $_ Pass in as many params to indicate the array path to required config.
     * If last parameter is boolean, that will indicate whether to throw exception if required config
     * is not found. Defaults to TRUE.
     * @return mixed
     * @throws Exception
     */
    public static function getConfig() {
        $args = func_get_args();
        if (count($args) === 0) {
            return static::$config;
        }
        $except = true;
        if (gettype($args[count($args) - 1]) === 'boolean') {
            $except = $args[count($args) - 1];
            unset($args[count($args) - 1]);
        }

        $value = null;
        $path = '';
        $error = false;

        foreach ($args as $key => $arg) {
            if ($key === 0) {
                $path = '$config[' . $arg . ']';

                if (!isset(static::$config[$arg])) {
                    $error = true;
                    break;
                }

                $value = & static::$config[$arg];
            }
            else {
                $path .= '[' . $arg . ']';

                if (!isset($value[$arg])) {
                    $error = true;
                    break;
                }

                $value = & $value[$arg];
            }
        }

        if ($error && $except) {
            throw new Exception('Invalid config path "' . $path . '"', true);
        }
        elseif ($error) {
            return null;
        }

        return $value;
    }

    /**
     * Fetches classes to inject from the config
     * @param string $type all|controllers|services|views
     * @return array
     */
    public static function getInject($type = null) {
        if ($type === null)
            return static::$inject;
        elseif (isset(static::$inject[$type]))
            return static::$inject[$type];

        return array();
    }

    /**
     * Fetches the server mode that the application is being run under
     * @return string development|production|...
     */
    public static function getServer() {
        if (NULL !== $server = static::getConfig('server', false)) {
            return $server;
        }

        return 'production';
    }

    /**
     * Creates a database connection using \DBScribe\Connection
     */
    protected static function initDB() {
        $dbConfig = static::getConfig('db', static::getServer());
        static::$db = new Connection($dbConfig['dsn'], $dbConfig['user'], $dbConfig['password'], $dbConfig['options']);
        static::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!empty($dbConfig['tablePrefix']))
            static::$db->setTablePrefix($dbConfig['tablePrefix']);
    }

    /**
     * Fetches an instance of the database connection
     * @return Connection|null
     */
    public static function getDB() {
        if (static::$db === null)
            static::initDB();

        return static::$db;
    }

    /**
     * Fetches the urls from the server request uri
     * @return array
     */
    public static function getUrls() {
        if (static::$urls !== null)
            return static::$urls;

        $uri = $_SERVER['REQUEST_URI'];

        static::$isVirtual = true;
        if (substr($uri, 0, strlen($_SERVER['SCRIPT_NAME'])) === $_SERVER['SCRIPT_NAME']) {
            $uri = str_replace('/index.php', '', $uri);
        }
        else if (stristr($_SERVER['SCRIPT_NAME'], '/index.php', true)) {
            static::$serverPath = stristr($_SERVER['SCRIPT_NAME'], '/index.php', true);

            $uri = str_replace(array(static::$serverPath, '/index.php'), '', $uri);
            static::$isVirtual = false;
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);
        static::$urls = static::updateArrayKeys(explode('/', str_replace('?' . $_SERVER['QUERY_STRING'], '', $uri)), true);
        return static::$urls;
    }

    /**
     * Fetches the path to the server root
     * @return string
     */
    public static function getServerPath() {
        return (static::isVirtual() || static::$serverPath == '/public') ? '/' : static::$serverPath . '/';
    }

    /**
     * Check if server is virtual or not
     * @return boolean
     */
    public static function isVirtual() {
        return static::$isVirtual;
    }

    /**
     * Checks if the module is an alias and returns the ModuleName
     * @param string $module
     * @return string
     */
    protected static function checkAlias($module) {
        // $module alias not called
        if (array_key_exists($module, static::getConfig('modules')))
            return $module;

        // check module alias
        foreach (static::getConfig('modules') as $cModule => $moduleOptions) {
            if (!array_key_exists('alias', $moduleOptions))
                continue;

            if ($moduleOptions['alias'] === Util::camelToHyphen($module))
                return $cModule;
        }

        // module nor alias found
        return $module;
    }

    /**
     * Fetches the alias for the given module
     * @param string $module
     * @return string
     */
    public static function getModuleAlias($module) {
        if (NULL !== $alias = static::getConfig('modules', $module, 'alias', false)) {
            return $alias;
        }

        return Util::camelToHyphen($module);
    }

    /**
     * Fetches the current module
     * @return string
     */
    public static function getModule() {
        $urls = static::getUrls();
        if (!empty($urls) && count($urls) >= 1) {
            $return = $urls[0];
        }
        else {
            $return = static::getDefaultModule();
        }

        return static::checkAlias(ucfirst(Util::hyphenToCamel($return)));
    }

    /**
     * Fetches the default module from the config
     * @param boolean $getAlias Indicates whether to return the alias if available or not
     * @return string
     */
    public static function getDefaultModule($getAlias = false) {
        if (!$module = static::getConfig('defaults', 'module', false)) {
            $modules = static::getConfig('modules');
            $moduleNames = array_keys($modules);
            $module = $moduleNames[0];
        }

        return $getAlias ? static::getModuleAlias($module) : $module;
    }

    /**
     * Fetches the current controller
     * @param boolean $exception Indicates whether to thrown an exception or not if not found
     * @return string
     */
    public static function getController($exception = true) {
        $urls = static::getUrls();

        if (!empty($urls) && count($urls) >= 2) {
            $return = ucfirst($urls[1]);
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

    /**
     * Fetches the default controller from the config
     * @param string $module
     * @param boolean $exception Indicates whether to throw an exception if not found
     * @return string
     */
    public static function getDefaultController($module = null, $exception = false) {
        $module = ($module === null) ? static::getDefaultModule() : $module;

        return static::getConfig('modules', $module, 'defaults', 'controller', $exception);
    }

    /**
     * Fetches the current action
     * @param boolean $exception Indicates whether to thrown an exception or not if not found
     * @return string
     */
    public static function getAction($exception = true) {
        $urls = static::getUrls();
        if (!empty($urls) && count($urls) >= 3) {
            $return = strtolower($urls[2]);
        }
        else {
            if (!$return = static::getDefaultAction(static::getModule(), false)) {
                if ($exception)
                    throw new \Exception('Required action not found');
                return '-';
            }
        }

        return lcfirst(Util::hyphenToCamel($return));
    }

    /**
     * Fetches the default action from the config
     * @param string $module
     * @param boolean $exception Indicates whether to throw an exception if not found
     * @return string
     */
    public static function getDefaultAction($module = null, $exception = false) {
        $module = ($module === null) ? static::getDefaultModule() : $module;

        return static::getConfig('modules', $module, 'defaults', 'action', $exception);
    }

    public static function getDefaultLayout() {
        return static::getConfig('defaults', 'defaultLayout');
    }

    /**
     * Fetches the parameters for the action from the request uri
     * @return array
     */
    public static function getParams() {
        $urls = static::getUrls();

        unset($urls[0]); // module
        unset($urls[1]); // controller
        unset($urls[2]); // action

        return static::updateArrayKeys($urls);
    }

    protected static function updateArrayKeys(array $array, $removeEmptyValues = false) {
        $return = array();
        foreach ($array as $value) {
            if ($removeEmptyValues && $value !== '0' && empty($value))
                continue;
            $return[] = urldecode($value);
        }
        return $return;
    }

    /**
     * Creates an instance of the current controller class
     * @return AController
     * @throws Exception
     */
    protected static function getControllerClass($live = true) {
        $class = static::getModule() . '\Controllers\\' . static::getController() .
                'Controller';

        if (!class_exists($class))
            ControllerException::notFound($class);

        if (!in_array('DScribe\Core\AController', class_parents($class)))
            throw new \Exception('Controller Exception: Controller "' . $class . '" does not extend "DScribe\Core\AController"');

        return ($live) ? new $class() : $class;
    }

    /**
     * Authenticates the current user against the controller and action
     * @param string $controller
     * @param string $action
     */
    protected static function authenticate($controller, $action, $params) {
        $auth = new Authenticate(static::$userId, $controller, $action);
        if (!$auth->execute()) {
            $controller->accessDenied($action, $params);
            exit;
        }
    }

    /**
     * Resets the user to guest
     * @param AUser $user
     * @param int $duration Duration for which the identity should be valid
     */
    public static function resetUserIdentity(AUser $user = NULL, $duration = null) {
        static::$userId = new UserIdentity($user, $duration);
        if (!$user)
            Session::reset();
        static::saveSession();
    }

    /**
     * Fetches the identity of the current user
     * @return UserIdentity
     */
    public static function getUserIdentity() {
        return static::$userId;
    }

    /**
     * Fetches an instance of the flash messenger
     * @return Flash
     */
    public static function getFlash() {
        if (static::$flash === null)
            static::$flash = new Flash();

        return static::$flash;
    }

    /**
     * Saves the current user identity to session
     */
    public static function saveSession() {
        Session::save('UID', static::$userId);
    }

    /**
     * Restores the current user identity from session
     */
    protected static function fetchSession() {
        static::$userId = Session::fetch('UID');
    }

    /**
     * Initializes the engine
     * @param array $config
     */
    public static function init(array $config) {
        static::$config = $config;
        static::checkConfig($config);

        static::fetchSession();

        if (static::$userId === null)
            static::$userId = new UserIdentity();

        static::$inject = include CONFIG . 'inject.php';
    }

    protected static function checkConfig(array $config) {
        if (!static::getConfig('modules', false))
            throw new \Exception('Modules not specified in the config file. Please consult the documentation', true);
        elseif (!static::checkModules(static::getConfig('modules', false)))
            throw new \Exception('Invalid "modules" settings in the config file. Please consult the documentation', true);
        elseif (!static::getConfig('app', false))
            throw new \Exception('App settings not specified in the config file. Please consult the documentation', true);
        elseif (!static::getConfig('app', 'name', false))
            throw new \Exception('App name not specified in the config file. Please consult the documentation', true);
        elseif (!static::getConfig('defaults', false))
            throw new \Exception('Defaults settings not specified in the config file. Please consult the documentation', true);
        elseif (!static::getConfig('defaults', 'theme', false))
            throw new \Exception('Default theme not specified in the config file. Please consult the documentation', true);
    }

    protected static function checkModules($modules) {
        if (!is_array($modules)) {
            return false;
        }
        else {
            $modules = array_keys($modules);
            foreach ($modules as $module) {
                if (!is_string($module)) {
                    return false;
                }
            }
        }
        return true;
    }

    protected static function moduleIsActivated() {
        if (!array_key_exists(ucfirst(Util::hyphenToCamel(static::getModule())), static::getConfig('modules')))
            throw new \Exception('Module "' . static::getModule() . '" not activated');
    }

    public static function getModulePath() {
        return MODULES . static::getModule() . DIRECTORY_SEPARATOR;
    }

    public static function reloadPage() {
        header('Location: ' . static::getServerPath() . join('/', static::getUrls()));
        exit;
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
            $view = new View();

            $controller = static::getControllerClass();
            $controller->setView($view);
            $action = static::getAction();

            if (!in_array($action, $controller->getActions())) {
                ControllerException::invalidAction($action);
            }

            $params = static::getParams();
            static::authenticate($controller, $action, $params);
            $view->setController($controller)
                    ->setAction($action);

            $refMethod = new ReflectionMethod($controller, $action . 'Action');

            if (count($params) < $refMethod->getNumberOfRequiredParameters()) {
                ControllerException::invalidParamCount();
            }
            $actionRet = call_user_func_array(array($controller, $action . 'Action'), $params);

            if ($actionRet !== null && !is_array($actionRet) &&
                    (is_object($actionRet) && get_class($actionRet) !== 'DScribe\View\View')) {
                ControllerException::invalidActionResult();
            }

            if (is_array($actionRet))
                $view->variables($actionRet);
            elseif (is_object($actionRet))
                $view = $actionRet;
            ob_start();
            $view->render();
            $data = ob_get_clean();
            if ($cache && !Session::fetch('noCache'))
                $cache->save($name, $data);
            echo $data;
            static::terminate();
        }
    }

    /**
     * 
     * @return Cache|Null
     */
    protected static function canCache() {
        $request = new Request();
        if (Session::fetch('noCache') || $request->isPost() || $request->hasFile() || static::getFlash()->hasMessage()) {
            return false;
        }

        $noCacheActions = call_user_func(array(static::getControllerClass(false), 'noCache'));
        return ((is_bool($noCacheActions) && !$noCacheActions) || (is_array($noCacheActions) && !in_array(\Util::camelToHyphen(static::getAction()), \Util::arrayValuesCamelTo($noCacheActions, '-')))) ?
                new Cache(static::getUserIdentity()->getUser()) : null;
    }

    /**
     * Cleans up necessary things when engine is done
     */
    protected static function terminate() {
        static::saveSession();
        Session::remove('noCache');
        Session::remove('mapperIgnore');
    }
    
}
