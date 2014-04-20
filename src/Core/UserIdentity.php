<?php

namespace DScribe\Core;

final class UserIdentity {

    /**
     * Current user's id
     * @var mixed
     */
    protected $id;

    /**
     * Time the user was created
     * @var int
     */
    protected $loginTime;

    /**
     * Remote server address
     * @var string
     */
    protected $remoteServer;

    /**
     * Remote server port
     * @var string
     */
    protected $remotePort;

    /**
     * Class contructor
     * @param \DScribe\Core\AUser $user
     */
    final public function __construct(AUser $user = null) {
        if ($user === null) {
            $user = new GuestUser();
        }
        $this->id = $user->getId();
        $this->loginTime = time();
        $this->remoteServer = $_SERVER['REMOTE_ADDR'];
        $this->remotePort = $_SERVER['REMOTE_PORT'];

        $this->saveUser($user);
    }

    private function saveUser(AUser $user) {
        //@todo: replace saving in session with file saving
        \Session::save('USER', $user);
        return true;
    }

    /**
     * Fetches the current user
     * @return \DScribe\Core\AUser
     * @todo get user from file not session
     */
    final public function getUser() {
        $user = \Session::fetch('USER');
        if ($user !== null) {
            return $user;
        }

        return new GuestUser();
    }

    /**
     * Fetches the id
     * @return mixed
     */
    final public function getId() {
        return $this->id;
    }

    /**
     * Fetches the login time
     * @return int
     */
    final public function getLoginTime() {
        return $this->loginTime();
    }

    /**
     * Fetches the remote server address of user
     * @return string
     */
    final public function getRemoteServer() {
        return $this->remoteServer;
    }

    /**
     * Fetches the remote server port of user
     * @return string
     */
    final public function getRemotePort() {
        return $this->remotePort;
    }

    /**
     * Checks if the user is a guest
     * @return boolean
     */
    final public function isGuest() {
        return ($this->getUser()->is('guest'));
    }

    final public function __call($name, $args) {
        if (!method_exists($this, $name)) {
            if (substr($name, 0, 2) == 'is') {
                return $this->getUser()->is(substr($name, 2));
            }
        }
    }

    /**
     * Checks if properties exist in the current user and their values are as required
     * @param array $options
     * @return boolean
     */
    final public function checkUser(array $options) {
        return $this->getUser()->check($options);
    }

}
