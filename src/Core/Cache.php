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
    private $name;

    public function __construct(AUser $user = null) {
        $this->path = ($user) ? CACHE . $user->getId() . DIRECTORY_SEPARATOR : CACHE;
        if (!is_dir($this->path))
            mkdir($this->path, 0777, true);
    }

    private function getNamePath($name) {
        $sep = DIRECTORY_SEPARATOR;

        $exp = explode($sep, $name);
        $this->name = $exp[count($exp) - 1];
        unset($exp[count($exp) - 1]);
        $path = join($sep, $exp);

        if (!$path) {
            $path = '';
        }
        if ($path)
            $path .= DIRECTORY_SEPARATOR;

        return $path;
    }

    public function save($name, $data) {
        $path = $this->getNamePath($name);

        if ($name == '/') {
            $name = 'guest';
        }

        if (!is_dir($this->path . $path)) {
            mkdir($this->path . $path, 0777, true);
        }
        return file_put_contents($this->path . $path . $this->name, $data);
    }

    public function fetch($name) {
        if (!$name || $name == '/') {
            $name = 'guest';
        }

        $path = $this->getNamePath($name);

        if (is_readable($this->path . $path . $this->name)) {
            return file_get_contents($this->path . $path . $this->name);
        }
    }

    public function remove($name) {
        foreach (scandir(CACHE) as $path) {
            if (in_array($path, array('.', '..')))
                continue;
            if (is_readable(CACHE . $path . $name)) {
                if (unlink(CACHE . $path . $name)) {
                    $this->removeParentDir(CACHE . $path . $name);
                    return true;
                }
            }
        }
    }

    private function removeParentDir($dir) {
        if (count(scandir(dirname($dir))) === 2) {
            rmdir(dirname($dir));
            $this->removeParentDir(dirname($dir));
        }
    }

}
