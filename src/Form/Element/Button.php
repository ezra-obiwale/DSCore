<?php

/*
 */

namespace DScribe\Form\Element;

/**
 * Description of Element
 *
 * @author topman
 */
class Button extends Textarea {

    public function __construct(array $data = array(), $preserveArray = false, $preserveKeyOnly = null) {
        parent::__construct($data, $preserveArray, $preserveKeyOnly);

    }

    public function create() {
        return '<button name="' . $this->name . '"  type="' . $this->type . '" ' .
                $this->parseAttributes($this->attributes->toArray()) .
                '>' . $this->getValue($this->data) . '</button>';
    }

}
