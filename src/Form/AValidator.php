<?php

namespace DScribe\Form;

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
    protected $error;

    /**
     * Indicates whether validation succeeds or fails
     * @var boolean
     */
    protected $valid;

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

    /**
     * Add filters to specified element
     * @param string $elementName
     * @param array $filters
     * @return \DScribe\Form\AValidator
     */
    final public function addFilters($elementName, array $filters) {
        $this->filters[$elementName] = $filters;
        return $this;
    }

    /**
     * Validates the data
     * @return boolean
     */
    private function validate() {
        if ($this->error === null)
            $this->error = array();

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

        if ($this->fieldsets) {
            foreach ($this->fieldsets as $name) {
                if (!isset($this->elements[$name]))
                    continue;
                if (!$this->elements[$name]
                                ->options
                                ->value
                                ->setData($this->data[$name] ? $this->data[$name] : new \Object())
                                ->isValid()) {
                    $valid = false;
                }
            }
        }
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
        $this->msg = array();
        
        if (is_null($this->error))
            $this->error = array();

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
     * Fetches the error messages
     * @return array
     */
    final public function getErrorMessages() {
        return $this->msg ? $this->msg : $this->prepareErrorMsgs();
    }

    /**
     * return array of filters to validate data against
     */
    abstract public function getFilters();

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
     * @param \Object|array $data
     */
    abstract public function setData($data);

    /**
     * Fetches the form errors
     * @return array
     */
    final public function getErrors() {
        return $this->error;
    }

}
