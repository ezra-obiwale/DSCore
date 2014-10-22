<?php

namespace DScribe\Core;

use Object,
    Util;

/**
 * Description of Request
 *
 * @author topman
 */
class Request {

    /**
     * The data sent with the request
     * @var Object
     */
    protected $data;

    /**
     *
     * @var Object
     */
    protected $post;

    /**
     * @var Object
     */
    protected $get;

    /**
     *
     * @var Object
     */
    protected $files;

    /**
     * Global server http variables
     * @var \Object
     */
    protected $http;

    /**
     * Global server variables
     * @var \Object
     */
    protected $server;

    /**
     * Class constructor
     */
    public function __construct() {
        parse_str(file_get_contents('php://input'), $this->data);
        $this->data = $this->data ? new Object($this->data, true) :
                new Object();
        $this->post = new Object($_POST, true);
        $this->get = new Object($_GET, true);
        $this->files = new Object($_FILES);
        $this->initServer();
    }

    /**
     * Fetches the data sent with the request
     * @param bool $asArray Indicates whether the data should be in an array. It
     * will be an object of \Object if false
     * @return array
     */
    public function getData($asArray = false) {
        return $asArray ? $this->data->toArray() : $this->data;
    }

    /**
     * initializes server values
     */
    private function initServer() {
        $this->http = new Object();
        $this->server = new Object();

        foreach ($_SERVER as $key => $val) {
            $key = str_replace('request_', '', strtolower($key));
            if (substr($key, 0, 5) === 'http_') {
                $this->http->{Util::_toCamel(substr($key, 5))} = $val;
            }
            elseif (substr($key, 0, 7) === 'server_') {
                $this->server->{Util::_toCamel(substr($key, 7))} = $val;
            }
            else {
                $this->{Util::_toCamel($key)} = $val;
            }
        }
    }

    public function __call($name, $arguments) {
        if (!method_exists($this, $name)) {
            if (substr($name, 0, 2) === 'is') {
                return ($_SERVER['REQUEST_METHOD'] === strtoupper(substr($name, 2)));
            }
        }
    }

    /**
     * Checks if the request is an ajax
     * @return boolean
     */
    public function isAjax() {
        return (isset($this->http->xRequestedWith) && $this->http->xRequestedWith = 'XMLHttpRequest' ||
                isset($this->http->requestedWith) && $this->http->requestedWith = 'XMLHttpRequest');
    }

    /**
     * Checks if the request has files
     * @return boolean
     */
    public function hasFile() {
        return (count($_FILES) > 0);
    }

    public function getPost($asArray = false) {
        return $asArray ? $this->post->toArray() : $this->post;
    }

    public function getGet($asArray = false) {
        return $asArray ? $this->get->toArray() : $this->get;
    }

    /**
     * Fetches the files content
     * @return \Object
     */
    public function getFiles($asArray = false) {
        return $asArray ? $this->files->toArray() : $this->files;
    }

    /**
     * Fetches the server http content
     * @return \Object
     */
    public function getHttp() {
        return $this->http;
    }

    /**
     * Fetches the server content
     * @return \Object
     */
    public function getServer() {
        return $this->server;
    }

}
