<?php

namespace DScribe\Core;

use DScribe\View\View;

abstract class AController extends AInjector {

    /**
     * The view instance
     * @var View
     */
    protected $view;

    /**
     * The layout file to use
     * @var string
     */
    protected $layout;

    /**
     * Instance of the controller's service class
     * @var AService
     */
    protected $service;

    /**
     * The Request instance
     * @var Request
     */
    protected $request;

    /**
     * Sets the view instance to use
     * @param View $view
     * @return AController
     */
    final public function setView(View $view) {
        $this->view = $view;
        $this->init();
        return $this;
    }

    /**
     * The magic method __construct() replacement
     */
    final protected function construct() {
        $this->request = new Request();
        $this->view = new View();
        $this->init();
    }

    /**
     * Replaces the magic method __construct() for child classes
     */
    protected function init() {
        
    }

    /**
     * Fetches the request instance
     * @return Request
     */
    final protected function getRequest() {
        if (!$this->request)
            $this->request = new Request ();
        return $this->request;
    }

    /**
     * Resets the user identity to guest
     * @param AUser $user
     * @return AController
     */
    final protected function resetUserIdentity(AUser $user = null) {
        Engine::resetUserIdentity($user);
        return $this;
    }

    /**
     * Prepares the injection
     * @return array
     */
    final protected function prepareInject() {
        $service = $this->getModule() . '\Services\\' . $this->getClassName() . 'Service';
        if (class_exists($service)) {
            return array_merge(parent::prepareInject(), $this->getConfigInject('controllers'), array(
                'service' => array(
                    'class' => $service
                ),
            ));
        }

        return array_merge($this->getConfigInject('controllers'), $this->inject());
    }

    /**
     * Fetches the layout for the controller
     * @return string
     */
    final public function getLayout() {
        return $this->layout;
    }

    /**
     * Fetches all actions in the controller
     * @return array
     */
    final public function getActions() {
        $return = array();
        foreach (get_class_methods($this) as $method) {
            if (substr($method, strlen($method) - 6) === 'Action')
                $return[] = substr($method, 0, strlen($method) - 6);
        }
        return $return;
    }

    /**
     * Gets the name of the controller without the "Controller" suffix
     * @return string
     */
    final public function getClassName() {
        return parent::className('Controller');
    }

    /**
     * Redirects to another resource
     * @param string $module
     * @param string|null $controller
     * @param string|null $action
     * @param array $params
     */
    final protected function redirect($module, $controller = null, $action = null, array $params = array()) {
        $module = ($module) ? $module : Engine::getModule();
        $controller = ($controller) ? $controller : Engine::getController();
        $action = ($action) ? $action : Engine::getAction();
        $params = (!empty($params)) ? $params : Engine::getParams();
        
        header('Location: ' . $this->view->url($module, $controller, $action, $params));
        exit;
    }

    /**
     * Fetches an array of classes to inject
     * @return array
     */
    protected function inject() {
        return array();
    }

    /**
     * Fetches the instance of flash messenger
     * @return Flash
     */
    final protected function flash() {
        return Engine::getFlash();
    }

    /**
     * Fetches the array of access rules for actions
     * @return array
     */
    public function accessRules() {
        return array(
            array('allow', array()),
        );
    }

    /**
     * What to do when access is denied a user
     * @param string $action
     * @throws \Exception
     */
    public function accessDenied($action, $args) {
        throw new \Exception('You do not have permission to view this page');
    }

    /**
     * Indicates whether not to cache actions, or actions to cache
     * @return boolean|array TRUE (don't cache any action) or FALSE (cache all actions) or array of actions not to cache
     */
    public function noCache() {
        return TRUE; // don't cache any action
    }

    /**
     * Fetches a config from the config file
     * @see Engine::getConfig()
     * @return mixed
     */
    final protected function getConfig() {
        return call_user_func_array(array(new Engine, 'getConfig'), func_get_args());
    }

    /**
     * Fetches the identity of the current user
     * @return UserIdentity
     */
    final protected function userIdentity() {
        return Engine::getUserIdentity();
    }

}
