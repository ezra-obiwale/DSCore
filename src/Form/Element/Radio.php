<?php

/*
 */

namespace DScribe\Form\Element;

/**
 * Description of Element
 *
 * @author topman
 */
class Radio extends Checkbox {
    
    public function getName() {
        return $this->name;
    }
    
    public function getType() {
        return 'radio';
    }


}
