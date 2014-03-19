<?php

namespace DScribe\Form;

/**
 * Description of IHydrator
 *
 * @author topman
 */
interface IHydrator {

    /**
     * returns an array version of object properties
     */
    public function toArray();
}
