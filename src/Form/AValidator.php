<?php

namespace DScribe\Form;

/**
 * Validates a group of elements
 * @author Ezra Obiwale
 */
abstract class AValidator {

    /**
     * Form elements
     * @var array
     */
    protected $elements;

    /**
     * Values to validate
     * @var array
     */
    protected $data;

    /**
     * Indicates whether validation succeeds or fails
     * @var boolean
     */
    protected $valid;

    /**
     * Name of the fieldset/form to be used in csrf validation
     * @var string
     */
    private $name;

    /**
     * Class constructor
     */
    public function __construct($name) {
        $this->name = $name;
        $this->elements = array();
        $this->data = array();
    }

    /**
     * Add filters to specified element
     * @param string $elementName
     * @param array $filters
     * @return \DScribe\Form\AValidator
     */
    final public function addFilters($elementName, array $filters) {
        $this->elements[$elementName]->filters->add($filters);
        return $this;
    }

    /**
     * Validates the data
     * @return boolean
     */
    private function validate() {
        $filterer = new Filterer();
        $valid = true;
        foreach ($this->elements as $element) {
            if (!$element->validate($filterer, $this->data))
                $valid = false;
            $this->data[$element->name] = ($element->type === 'fieldset') ?
                    $element->options->value->getData(true) : $element->data;
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
     * return array of filters to validate data against
     */
    abstract public function getFilters();

    /**
     * Sets data to validate
     * @param \Object|array $data
     */
    abstract public function setData($data);
}
