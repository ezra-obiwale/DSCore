<?php

/*
 */

namespace DScribe\Form\Element;

use DScribe\Form\Element,
    Object;

/**
 * Description of Element
 *
 * @author topman
 */
class Button extends Element {

    public function create() {
        if (!$this->attributes)
            $this->attributes = new Object();
        return '<button name="' . $this->getName() . '"  type="' . $this->type . '" ' .
                $this->parseAttributes($this->attributes->toArray()) .
                '>' . $this->getValue() . '</button>';
    }

}
