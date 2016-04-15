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
class Button extends Element {

	public function create($noName = false) {
		if (!$this->attributes) $this->attributes = new Object();
		return $noName ?
				'<button type="' . $this->type . '" ' .
				$this->parseAttributes($this->attributes->toArray()) .
				'>' . $this->getValue() . '</button>' :
				'<button name="' . $this->getName() . '"  type="' . $this->type . '" ' .
				$this->parseAttributes($this->attributes->toArray()) .
				'>' . $this->getValue() . '</button>';
	}

}
