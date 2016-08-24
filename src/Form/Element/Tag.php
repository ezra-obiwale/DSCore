<?php

/*
 */

namespace DScribe\Form\Element;

use DScribe\Form\Element,
	Object;

/**
 * Description of Tag
 * Creates just an html element tag with the given content
 * Default element tag is 'div'
 *
 * @author topman
 */
class Tag extends Element {

	public function __construct(array $data = array(), $preserveArray = false, $preserveKeyOnly = null) {
		parent::__construct($data, $preserveArray, $preserveKeyOnly);
		if (!$this->options->tag) $this->options->tag = 'div';
		if (!$this->options->content) $this->options->content = '';
	}

	public function prepareTag() {
		return '';
	}
	
	protected function getValue() {
		return $this->options->content;
	}

	public function create() {
		if (!$this->attributes) $this->attributes = new Object();
		return '<' . $this->options->tag . ' ' . $this->parseAttributes($this->attributes->toArray())
				. '>' . $this->options->content . '</' . $this->options->tag . '>';
	}

}
