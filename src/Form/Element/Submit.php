<?php

/*
 */

namespace dScribe\Form\Element;

/**
 * Description of Element
 *
 * @author topman
 */
class Submit extends Button {

    public function create() {
        return parent::create(true);
    }

}
