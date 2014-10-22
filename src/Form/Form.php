<?php

namespace DScribe\Form;

/**
 * @todo allow as much external adding, changing and removing of elements and attributes as possible
 */
class Form extends Fieldset {

    public function __construct($name = 'form', array $attributes = array()) {
        if (!isset($attributes['name']))
            $attributes['name'] = $name;
        if (!isset($attributes['id']))
            $attributes['id'] = $name;

        parent::__construct($name, $attributes);
    }

    /**
     * Opens the form
     * @return \DScribe\Form\Forms
     */
    public function openTag() {
        return '<form ' . $this->parseAttributes($this->attributes) . '>' . "\n";
    }

    /**
     * Closes the form
     * @return \DScribe\Form\Forms
     */
    public function closeTag() {
        return '</form>' . "\n";
    }

    /**
     * Renders the form to the browser
     * @return \DScribe\Form\Forms
     */
    public function render() {
        ob_start();
        echo $this->openTag();
        echo parent::render();
        echo $this->closeTag();

        return ob_get_clean();
    }

    /**
     * Removes the data in the form
     * @return Form
     */
    public function reset() {
        $this->data = array();
        return $this;
    }

}
