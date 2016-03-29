<?php

/*
 */

namespace dScribe\Form\Element;

use dScribe\Form\Element,
    Object;

/**
 * Description of Element
 *
 * @author topman
 */
class Textarea extends Element {

    public function create() {
        if (!$this->attributes)
            $this->attributes = new Object();
        return '<textarea name="' . $this->getName() . '" ' .
                $this->parseAttributes($this->attributes->toArray()) .
                '>' . $this->getValue() . '</textarea>';
    }

}
