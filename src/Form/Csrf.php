<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DScribe\Form;

/**
 * Description of Csrf
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
class Csrf {

    protected $name;

    public function __construct($name = 'secureForm') {
        $this->name = md5($name);
    }

    public function create() {
        if (!$this->fetch())
                \Session::save($this->name, \Util::randomPassword(20));
        return $this;
    }

    public function fetch() {
        return \Session::fetch($this->name);
    }

    public function isValid($code) {
        return ($code === $this->fetch());
    }

    public function remove() {
        \Session::remove($this->name);
        return $this;
    }

    public function getName() {
        return $this->name;
    }

}
