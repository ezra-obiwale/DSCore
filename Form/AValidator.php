<?php

namespace DScribe\Form;

use Object;

/**
 * Validates a group of elements
 * @author Ezra Obiwale
 */
abstract class AValidator {

    /**
     * Message for each element
     * @var array
     */
    protected $msg;

    /**
     * Element filters
     * @var array
     */
    protected $filters;

    /**
     * Element filters to ignore
     * @var array
     */
    protected $noFilter;

    /**
     * Form elements
     * @var array
     */
    protected $elements;

    /**
     * Indicates whether the elements have been validated or not
     * @var boolean
     */
    protected $validated;

    /**
     * Values to validate
     * @var array
     */
    protected $data;

    /**
     * Element Errors
     * @var array
     */
    private $error;

    /**
     * Indicates whether validation succeeds or fails
     * @var boolean
     */
    private $valid;

    /**
     * Checkbox and radio elements
     * @var array
     */
    protected $booleans;

    /**
     * Fieldsets in form
     * @var array
     */
    protected $fieldsets;

    /**
     * Indicates whether the filters have been setup
     * @var boolean
     * @see AValidator::setupFilters()
     */
    private $setupFilter;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->msg = $this->filters = $this->elements = $this->data = $this->booleans = $this->fieldsets = $this->error = array();
        $this->validated = $this->setupFilter = false;
    }

    private function updateFilters(Object $element, $parentElementName = null) {
        if (!is_array($element->options->value->filters())) {
            throw new \Exception('Filter error: Filters for fieldset "' . $element->options->value->getName() . '" must return an array');
        }

        $filters = array();
        $name = ($parentElementName === null) ? $element->name : $parentElementName . '[' . $element->name . ']';
        foreach ($element->options->value->filters() as $nam => $filter) {
            $filters[$name . '[' . $nam . ']'] = $filter;
        }
        $this->filters = array_merge($this->getFilters(), $filters);
        $this->setupFilters($element->options->value->getElements(), $name);
        return $this;
    }

    private function setupFilters(array $elements, $parentElementName = null) {
        if ($this->setupFilter)
            return $this;
        foreach ($elements as $element) {
            if ($element->type !== 'fieldset')
                continue;

            $this->updateFilters($element, $parentElementName);
        }
        $this->setupFilter = true;
        return $this;
    }

    final public function addFilters($elementName, array $filters) {
        $this->filters[$elementName] = $filters;
        return $this;
    }

    /**
     * Validates the data
     * @return boolean
     */
    private function validate() {
        $filterer = new Filterer($this->data, $this->error);
        $valid = true;
        foreach ($this->getFilters() as $name => $filters) {
            if (@in_array($name, $this->noFilter))
                continue;

            foreach ($filters as $filter => $options) {
                if (method_exists($filterer, $filter)) {
                    if (!call_user_func_array(array($filterer, $filter), array($name, $options))) {
                        $valid = false;
                    }
                }
            }
        }

        $this->prepareErrorMsgs();
        $this->valid = $valid;
        return $valid;
    }

    /**
     * Validates the data
     * @return boolean
     */
    final public function isValid() {
        return ($this->valid === null) ? $this->validate() : $this->valid;
    }

    /**
     * prepares the error messages for display
     * @return array Array of prepared error messages
     */
    final public function prepareErrorMsgs() {
        foreach ($this->error as $name => $msgs) {
            ob_start();
            ?>
            <ul class="errors">
                <?php foreach ($msgs as $msg): ?>
                    <li><?= $msg ?></li>
                <?php endforeach; ?>
            </ul>
            <?php
            $this->msg[$name] = ob_get_clean();
        }
        return $this->msg;
    }

    /**
     * return array of filters to validate data against
     */
    abstract public function filters();

    /**
     * Fetches the filters to validate data against
     * @return array
     */
    public function getFilters() {
        $this->setupFilters($this->elements);
        return array_merge($this->filters, $this->filters());
    }

    /**
     * Checks if an element has errors
     * @param string $elementName
     * @return boolean
     */
    final public function hasError($elementName) {
        return (array_key_exists($elementName, $this->error));
    }

    /**
     * Sets data to validate
     * @param array|object $data
     */
    abstract public function setData($data);

    /**
     * Fetches the form errors
     * @return array
     */
    final public function getErrors() {
        return $this->error;
    }

    /**
     * Sets the form errors
     * @param array $errors
     * @return \DScribe\Form\AValidator
     */
    final public function setErrors(array $errors) {
        $this->error = $errors;
        return $this;
    }

}
