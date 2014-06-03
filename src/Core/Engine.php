<?php

namespace DScribe\Core;

use DBScribe\Connection,
    DScribe\View\View,
    PDO,
    ReflectionMethod,
    Session,
    Util;

class Engine {

    private static $config;
    private static $userId;
    private static $flash;
    private static $isVirtual;
    private static $db;
    private static $urls;
    private static $inject;
    private static $serverPath;

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
            return self::$config;
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

                if (!isset(self::$config[$arg])) {
                    $error = true;
                    break;
                }

                $value = & self::$config[$arg];
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
            return self::$inject;
        elseif (isset(self::$inject[$type]))
            return self::$inject[$type];

        return array();
    }

    /**
     * Fetches the server mode that the application is being run under
     * @return string development|production|...
     */
    public static function getServer() {
        if (NULL !== $server = self::getConfig('server', false)) {
            return $server;
        }

        return 'production';
    }

    /**
     * Creates a database connection using \DBScribe\Connection
     */
    private static function initDB() {
        $dbConfig = self::getConfig('db', self::getServer());
        self::$db = new Connection($dbConfig['dsn'], $dbConfig['user'], $dbConfig['password'], $dbConfig['options']);
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!empty($dbConfig['tablePrefix']))
            self::$db->setTablePrefix($dbConfig['tablePrefix']);
    }

    /**
     * Fetches an instance of the database connection
     * @return Connection|null
     */
    public static function getDB() {
        if (self::$db === null)
            self::initDB();

        return self::$db;
    }

    /**
     * Fetches the urls from the server request uri
     * @return array
     */
    public static function getUrls() {
        if (self::$urls !== null)
            return self::$urls;

        $uri = $_SERVER['REQUEST_URI'];

        self::$isVirtual = true;
        if (substr($uri, 0, strlen($_SERVER['SCRIPT_NAME'])) === $_SERVER['SCRIPT_NAME']) {
            $uri = str_replace('/index.php', '', $uri);
        }
        else if (stristr($_SERVER['SCRIPT_NAME'], '/index.php', true)) {
            //remove path to the index.php

            self::$serverPath = stristr($_SERVER['SCRIPT_NAME'], '/index.php', true);

            $uri = str_replace(array(self::$serverPath, '/index.php'), '', $uri);
            self::$isVirtual = false;
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);
        self::$urls = self::updateArrayKeys(explode('/', str_replace('?' . $_SERVER['QUERY_STRING'], '', $uri)), true);
        return self::$urls;
    }

    /**
     * Fetches the path to the server root
     * @return string
     */
    public static function getServerPath() {
//        return '/';
        // check that of loading files (e.g. js && css). Seems to work fine.
        return (self::isVirtual() || self::$serverPath == '/public') ? '/' : self::$serverPath . '/';
    }

    /**
     * Removes the cached page of a url path
     * @param string $urlPath
     * @return type
     */
    public static function removeCache($urlPath = null) {
        $cache = new Cache();
        return $cache->remove($urlPath ? $urlPath : join('/', self::getUrls()));
    }

    /**
     * Hides the cached page of a url path
     * @param string $urlPath
     * @return boolean
     */
    public static function hideCache($urlPath = null) {
        $path = ($urlPath) ? ROOT . 'public/' . $urlPath : ROOT . 'public/' . join('/', self::getUrls());
        if (is_dir($path)) {
            \Session::save('hiddenCache', $path . '_');
            rename($path, $path . '_');
        }
        return false;
    }

    /**
     * Restores the hidden cached page of a url path
     * @param string $urlPath
     * @return boolean
     */
    public static function restoreHiddenCache($urlPath = null) {
        $path = ($urlPath) ? $urlPath : \Session::fetch('hiddenCache');
        Session::remove('hiddenCache');
        if (is_dir($path)) {
            return rename($path, substr($path, 0, strlen($path) - 1));
        }
        return true;
    }

    /**
     * Check if server is virtual or not
     * @return boolean
     */
    public static function isVirtual() {
        return self::$isVirtual;
    }

    /**
     * Checks if the module is an alias and returns the ModuleName
     * @param string $module
     * @return string
     */
    private static function checkAlias($module) {
        // $module alias not called
        if (array_key_exists($module, Engine::getConfig('modules')))
            return $module;

        // check module alias
        foreach (Engine::getConfig('modules') as $cModule => $moduleOptions) {
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
        if (NULL !== $alias = Engine::getConfig('modules', $module, 'alias', false)) {
            return $alias;
        }

        return Util::camelToHyphen($module);
    }

    /**
     * Fetches the current module
     * @return string
     */
    public static function getModule() {
        $urls = self::getUrls();
        if (!empty($urls) && count($urls) >= 1) {
            $return = $urls[0];
        }
        else {
            $return = self::getDefaultModule();
        }

        return self::checkAlias(ucfirst(Util::hyphenToCamel($return)));
    }

    /**
     * Fetches the default module from the config
     * @param boolean $getAlias Indicates whether to return the alias if available or not
     * @return string
     */
    public static function getDefaultModule($getAlias = false) {
        if (!$module = self::getConfig('defaults', 'module', false)) {
            $modules = self::getConfig('modules');
            $moduleNames = array_keys($modules);
            $module = $moduleNames[0];
        }

        return $getAlias ? self::getModuleAlias($module) : $module;
    }

    /**
     * Fetches the current controller
     * @param boolean $exception Indicates whether to thrown an exception or not if not found
     * @return string
     */
    public static function getController($exception = true) {
        $urls = self::getUrls();

        if (!empty($urls) && count($urls) >= 2) {
            $return = ucfirst($urls[1]);
        }
        else {
            if (!$return = self::getDefaultController(self::getModule())) {
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
        $module = ($module === null) ? self::getDefaultModule() : $module;

        return self::getConfig('modules', $module, 'defaults', 'controller', $exception);
    }

    /**
     * Fetches the current action
     * @param boolean $exception Indicates whether to thrown an exception or not if not found
     * @return string
     */
    public static function getAction($exception = true) {
        $urls = self::getUrls();
        if (!empty($urls) && count($urls) >= 3) {
            $return = strtolower($urls[2]);
        }
        else {
            if (!$return = self::getDefaultAction(self::getModule(), false)) {
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
        $module = ($module === null) ? self::getDefaultModule() : $module;

        return self::getConfig('modules', $module, 'defaults', 'action', $exception);
    }

    public static function getDefaultLayout() {
        return self::getConfig('defaults', 'defaultLayout');
    }

    /**
     * Fetches the parameters for the action from the request uri
     * @return array
     */
    public static function getParams() {
        $urls = self::getUrls();

        unset($urls[0]); // module
        unset($urls[1]); // controller
        unset($urls[2]); // action

        return self::updateArrayKeys($urls);
    }

    private static function updateArrayKeys(array $array, $removeEmptyValues = false) {
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
    private static function getControllerClass($live = true) {
        $class = self::getModule() . '\Controllers\\' . self::getController() .
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
    private static function authenticate($controller, $action, $params) {
        $auth = new Authenticate(self::$userId, $controller, $action);
        if (!$auth->execute()) {
            $controller->accessDenied($action, $params);
            exit;
        }
    }

    /**
     * Resets the user to guest
     * @param AUser $user
     */
    public static function resetUserIdentity(AUser $user = NULL) {
        self::$userId = new UserIdentity($user);
        if (!$user)
            Session::reset();
        self::saveSession();
    }

    /**
     * Fetches the identity of the current user
     * @return UserIdentity
     */
    public static function getUserIdentity() {
        return self::$userId;
    }

    /**
     * Fetches an instance of the flash messenger
     * @return Flash
     */
    public static function getFlash() {
        if (self::$flash === null)
            self::$flash = new Flash();

        return self::$flash;
    }

    /**
     * Saves the current user identity to session
     */
    public static function saveSession() {
        Session::save('UID', self::$userId);
    }

    /**
     * Restores the current user identity from session
     */
    private static function fetchSession() {
        self::$userId = Session::fetch('UID');
    }

    /**
     * Initializes the engine
     * @param array $config
     */
    private static function init(array $config) {
        self::fetchSession();

        if (self::$userId === null)
            self::$userId = new UserIdentity();

        self::$config = $config;
        self::checkConfig($config);
        self::$inject = include CONFIG . 'inject.php';
    }

    private static function checkConfig(array $config) {
        if (!self::getConfig('modules', false))
            throw new \Exception('Modules not specified in the config file. Please consult the documentation', true);
        elseif (!self::checkModules(self::getConfig('modules', false)))
            throw new \Exception('Invalid "modules" settings in the config file. Please consult the documentation', true);
        elseif (!self::getConfig('app', false))
            throw new \Exception('App settings not specified in the config file. Please consult the documentation', true);
        elseif (!self::getConfig('app', 'name', false))
            throw new \Exception('App name not specified in the config file. Please consult the documentation', true);
        elseif (!self::getConfig('defaults', false))
            throw new \Exception('Defaults settings not specified in the config file. Please consult the documentation', true);
        elseif (!self::getConfig('defaults', 'theme', false))
            throw new \Exception('Default theme not specified in the config file. Please consult the documentation', true);
    }

    private static function checkModules($modules) {
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

    private static function moduleIsActivated() {
        if (!array_key_exists(ucfirst(Util::hyphenToCamel(self::getModule())), self::getConfig('modules')))
            throw new \Exception('Module "' . self::getModule() . '" not activated');
    }
    
    public static function getModulePath() {
        return MODULES . self::getModule() . DIRECTORY_SEPARATOR;
    }

    public static function reloadPage() {
        header('Location: ' . self::getServerPath() . join('/', self::getUrls()));
        exit;
    }

    /**
     * Starts the engine
     * @param array $config
     * @todo Check caching controller actions
     */
    public static function run(array $config) {
        self::init($config);
        self::moduleIsActivated();
        $cache = self::canCache();

        $name = join('/', self::getUrls());
        if ($cache && $out = $cache->fetch($name)) {
            echo $out;
        }
        else {
            self::restoreHiddenCache();
            $view = new View();

            $controller = self::getControllerClass();
            $controller->setView($view);
            $action = self::getAction();

            if (!in_array($action, $controller->getActions())) {
                ControllerException::invalidAction($action);
            }

            $params = self::getParams();
            self::authenticate($controller, $action, $params);
            $view->setController($controller)
                    ->setAction($action);

            $refMethod = new ReflectionMethod($controller, $action . 'Action');

            if (count($params) < $refMethod->getNumberOfRequiredParameters()) {
                ControllerException::invalidParamCount();
            }
            $actionRet = call_user_func_array(array($controller, $action . 'Action'), $params);

            if ($actionRet !== null && gettype($actionRet) !== 'array' &&
                    (is_object($actionRet) && get_class($actionRet) !== 'DScribe\View\View')) {
                ControllerException::invalidActionResult();
            }

            if (gettype($actionRet) === 'array')
                $view->variables($actionRet);
            elseif (is_object($actionRet))
                $view = $actionRet;
            ob_start();
            $view->render();
            $data = ob_get_clean();
            if ($cache && !Session::fetch('noCache'))
                $cache->save($name, $data);
            echo $data;
            self::terminate();
        }
    }

    /**
     * 
     * @return Cache|Null
     */
    private static function canCache() {
        $request = new Request();
        if (Session::fetch('noCache') || $request->isPost() || $request->hasFile() || self::getFlash()->hasMessage()) {
            return false;
        }

        $noCacheActions = @call_user_func(array(self::getControllerClass(false), 'noCache'));
        return ((is_bool($noCacheActions) && !$noCacheActions) || (is_array($noCacheActions) && !in_array(\Util::camelToHyphen(self::getAction()), \Util::arrayValuesCamelTo($noCacheActions, '-')))) ?
                new Cache(self::getUserIdentity()->getUser()) : null;
    }

    /**
     * Cleans up necessary things when engine is done
     */
    private static function terminate() {
        self::saveSession();
        Session::remove('noCache');
        Session::remove('mapperIgnore');
    }

}
