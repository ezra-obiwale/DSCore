<?php

/*
 */

namespace DScribe\Form\Element;

use DScribe\Form\Csrf,
    DScribe\Form\Element,
    DScribe\Form\Filterer;

/**
 * Description of Element
 *
 * @author topman
 */
class Hidden extends Element {

    public function setCsrfKey($key) {
        $this->key = $key;
        $this->prepCsrf();
        return $this;
    }
    
    protected function getValue() {
        if ($this->name === 'csrf') {
            $csrf = new Csrf($this->key);
            return $csrf->fetch();
        }
        return parent::getValue();
    }

    private function prepCsrf() {
        $csrf = new Csrf($this->key);
        $this->filters = array(
            'required' => true,
            'match' => array(
                'value' => $csrf->fetch(),
                'message' => ''
        ));
    }

    public function validate(Filterer $filterer) {
        $return = parent::validate($filterer);
        if ($this->name === 'csrf') {
            $csrf = new Csrf($this->key);
            $csrf->remove();
        }
        return $return;
    }

    public function create() {
        if ($this->name === 'csrf') { // store new csrf value
            $csrf = new Csrf($this->key);
            $this->options->value = $csrf->create()->fetch();
        }
        return parent::create();
    }

    public function render() {
        return $this->create();
    }

}
