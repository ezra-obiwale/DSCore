<?php

namespace DScribe\Core;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cache
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
class Cache {

    private $path;
    private $isAjax;

    public function __construct(AUser $user = null, $isAjax = false) {
        if ($isAjax)
            $this->path = ($user) ? CACHE . $user->getId() . DIRECTORY_SEPARATOR : CACHE;
        else
            $this->path = ROOT . 'public' . DIRECTORY_SEPARATOR;

        $this->isAjax = $isAjax;
    }

    public function save($name, $data) {
        $sep = DIRECTORY_SEPARATOR;

        $exp = explode($sep, $name);
        if ($this->isAjax) {
            $name = $exp[count($exp) - 1];
            unset($exp[count($exp) - 1]);
        }
        $path = join($sep, $exp);

        if (!$path) {
            $path = '';
        }
        if ($path)
            $path .= DIRECTORY_SEPARATOR;

        if ($name == '/') {
            $name = 'guest';
        }

        if (!is_dir($this->path . $path)) {
            mkdir($this->path . $path, 0777, true);
        }

        if (!$this->isAjax) {
            $name = 'ds-index.html';
        }

        return file_put_contents($this->path . $path . $name, $data);
    }

    public function fetch($name) {
        if (!$name || $name == '/') {
            $name = 'guest';
        }
        if (is_readable($this->path . DIRECTORY_SEPARATOR . $name)) {
            return file_get_contents($this->path . DIRECTORY_SEPARATOR . $name);
        }
    }

    public function remove($name) {
        if (is_readable($this->path . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'ds-index.html')) {
            if (unlink($this->path . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'ds-index.html')) {
                if (!\Util::readDir($this->path . DIRECTORY_SEPARATOR . $name, \Util::FILES_ONLY)) {
                    rmdir($this->path . DIRECTORY_SEPARATOR . $name);
                }
                return true;
            }
            return false;
        }
    }

}
