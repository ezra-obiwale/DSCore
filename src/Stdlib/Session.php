<?php

class Session {

    /**
     * String to prepend all session keys with
     * @var string
     */
    private static $prepend = '__DS_';
    private static $lifetime;

    private static function init() {
        ini_set('session.gc_maxlifetime', self::getLifetime());
        session_set_cookie_params(self::getLifetime());
        if (!isset($_SESSION)) {
            if (!self::$lifetime) {
                $sessionExpirationHours = engineGet('Config', 'sessionExpirationHours');
                if (!$sessionExpirationHours)
                    $sessionExpirationHours = 2;
                self::$lifetime = 60 * 60 * $sessionExpirationHours;
            }
            @session_start();
        }
    }

    private static function close() {
        session_write_close();
    }

    /**
     * Set the life time for the session
     * @param int $lifetime
     */
    public function setLifetime($lifetime) {
        self::$lifetime = $lifetime;
    }

    /**
     * Fetch the life time for the session
     * @return int
     */
    public function getLifetime() {
        if (!self::$lifetime) {
            $sessionExpirationHours = engineGet('Config', 'sessionExpirationHours', false);
            if (!$sessionExpirationHours)
                $sessionExpirationHours = 2;
            self::$lifetime = 60 * 60 * $sessionExpirationHours;
        }

        return self::$lifetime;
    }

    /**
     * Saves to session
     * @param string $key
     * @param mixed $value
     * @param int $duration Duration for which the identity should be valid
     */
    public static function save($key, $value, $duration = null) {
        self::setLifetime($duration);
        static::init();
        $_SESSION[self::$prepend . $key] = $value;
        self::close();
    }

    /**
     * Fetches from session
     * @param string $key
     * @return mixed
     */
    public static function fetch($key) {
        static::init();
        if (isset($_SESSION[self::$prepend . $key]))
            return $_SESSION[self::$prepend . $key];
        self::close();
    }

    /**
     * Removes from session
     * @param string $key
     */
    public static function remove($key) {
        static::init();
        if (isset($_SESSION[self::$prepend . $key]))
            unset($_SESSION[self::$prepend . $key]);
        self::close();
    }

    /**
     * Reset (destroy) the session
     */
    public static function reset() {
        static::init();
        session_destroy();
        self::close();
    }

}
