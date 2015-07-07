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
class Link extends Element {
    
    public function __construct(array $data = array(), $preserveArray = false, $preserveKeyOnly = null) {
        parent::__construct($data, $preserveArray, $preserveKeyOnly);
        if (!$this->options->url)
            throw new \Exception('Link element "' . $this->name . '" does not have a specified url in the options');
    }
    
    public function validate(\DScribe\Form\Filterer $filterer) {
        return true;
    }

    public function create() {
        if (!$this->attributes)
            $this->attributes = new Object();
        return '<a href="' . $this->options->url . '"' .
                $this->parseAttributes($this->attributes->toArray()) .
                '>' . $this->getValue() . '</a>';
    }

}
