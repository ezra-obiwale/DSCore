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
     * Global post variables
     * @var \Object
     */
    protected $post;

    /**
     * Global get variables
     * @var \Object
     */
    protected $get;

    /**
     * Global files variables
     * @var \Object
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
        $this->post = new Object($_POST, true);
        $this->get = new Object($_GET, true);
        $this->files = new Object($_FILES);

        $this->initServer();
    }

    /**
     * initializes server values
     */
    private function initServer() {
        $this->http = new Object();
        $this->server = new Object();

        foreach ($_SERVER as $key => $val) {
            $key = strtolower($key);
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

    /**
     * Checks if the request is a get
     * @return boolean
     */
    public function isGet() {
        return $this->get->notEmpty();
    }

    /**
     * Checks if the request is a post
     * @return boolean
     */
    public function isPost() {
        return $this->post->notEmpty();
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
        return $this->files->notEmpty();
    }

    /**
     * Fetches the post content
     * @return \Object
     */
    public function getPost() {
        return $this->post;
    }

    /**
     * Fetches the get content
     * @return \Object
     */
    public function getGet() {
        return $this->get;
    }

    /**
     * Fetches the files content
     * @return \Object
     */
    public function getFiles() {
        return $this->files;
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
