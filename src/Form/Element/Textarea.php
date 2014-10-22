<?php

/*
 */

namespace DScribe\Form\Element;

use DScribe\Form\Element;

/**
 * Description of Element
 *
 * @author topman
 */
class Textarea extends Element {

    public function create() {
        return '<textarea name="' . $this->name . '" ' .
                $this->parseAttributes($this->attributes->toArray()) .
                '>' . $this->getValue($this->data) . '</textarea>';
    }
    
}
