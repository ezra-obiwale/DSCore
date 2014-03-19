<?php

class Session {

    /**
     * String to prepend all session keys with
     * @var string
     */
    private static $prepend = '__DS_';

    /**
     * Saves to session
     * @param string $key
     * @param mixed $value
     */
    public static function save($key, $value) {
        if (!isset($_SESSION))
            @session_start();

        $_SESSION[self::$prepend . $key] = $value;
    }

    /**
     * Fetches from session
     * @param string $key
     * @return mixed
     */
    public static function fetch($key) {
        if (!isset($_SESSION))
            @session_start();

        if (isset($_SESSION[self::$prepend . $key]))
            return $_SESSION[self::$prepend . $key];
    }

    /**
     * Removes from session
     * @param string $key
     */
    public static function remove($key) {
        if (!isset($_SESSION))
            @session_start();

        if (isset($_SESSION[self::$prepend . $key]))
            unset($_SESSION[self::$prepend . $key]);
    }

    public static function reset() {
        if (!isset($_SESSION))
            @session_start();

        session_destroy();
    }

}
