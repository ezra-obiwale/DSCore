<?php

namespace dScribe\Form;

use dScribe\Core\IModel,
	Exception,
	Object,
	Util;

/**
 * Fieldset class
 *
 * @author Ezra Obiwale
 * @todo Create individual element, validator, and filter classes for easier extensions
 */
class Fieldset extends AValidator {

	/**
	 * Attribute array
	 * @var array
	 */
	protected $attributes;

	/**
	 * @var IModel
	 */
	protected $model;

	/**
	 * Holds checkbox and radio elements
	 * @var array
	 */
	private $booleans;

	/**
	 *
	 * @var boolean
	 */
	private $multiple;
	
	/**
	 * Array of added fieldsets' names
	 * @var array
	 */
	protected $fieldsets;

	/**
	 * Class constructor
	 * @param string $name Name of fieldset
	 * @param array $attributes
	 */
	public function __construct($name, array $attributes = array()) {
		parent::__construct($name);
		$this->attributes = $this->noFilter = array();
		$this->setAttributes($attributes);
	}

	/**
	 * Sets attributes for fieldset
	 * @param array $attributes
	 * @return Fieldset
	 */
	public function setAttributes(array $attributes) {
		$this->attributes = array_merge($this->attributes, $attributes);
		return $this;
	}

	/**
	 * Sets a fieldset attribute
	 * @param string $attr
	 * @param mixed $val
	 * @return Fieldset
	 */
	final public function setAttribute($attr, $val) {
		$this->attributes[$attr] = $val;
		return $this;
	}

	/**
	 * Fetches an attribute of the fieldset
	 * @param string $attr
	 * @return mixed
	 */
	final public function getAttribute($attr) {
		return $this->attributes[$attr];
	}

	/**
	 * Fetches the attributes of the fieldset
	 * @param boolean $parsed
	 * @return string|array
	 */
	final public function getAttributes($parsed = false) {
		if (!is_array($this->attributes)) {
			$this->attributes = array();
		}
		return ($parsed) ? $this->parseAttributes() : $this->attributes;
	}

	/**
	 * Parses attributes for rendering
	 * @return string
	 */
	final public function parseAttributes($ignore = array()) {
		$return = '';
		foreach ($this->attributes as $attr => $val) {
			if (in_array($attr, $ignore)) continue;
			$return .= Util::camelToHyphen($attr) . '="' . $val . '" ';
		}
		return $return;
	}

	/**
	 * Maps the form to a model
	 * @param IModel $model
	 * @return Form
	 */
	final public function setModel(IModel $model) {
		$this->model = $model;
		if (count($model->toArray())) {
			$this->setData($model->toArray(false, true));
		}
		return $this;
	}

	/**
	 * Fetches the populated model
	 * @return IModel
	 */
	public function getModel() {
		if ($this->model && $this->isValid()) {
			$data = $this->getData(true);
			foreach ($this->fieldsets as $name) {
				// if fieldset element no longer exists
				if (!isset($this->elements[$name])) continue;

				$fieldset = $this->elements[$name]->options->value;
				$data[$name] = $fieldset->hasModel() ? $fieldset->getModel() : $fieldset->getData();
			}
			$this->model->populate($data);
		}

		return $this->model;
	}

	/**
	 * Checks if fieldset has a model attached to it
	 * @return boolean
	 */
	public function hasModel() {
		return $this->model !== null;
	}

	/**
	 * Fetches the class name of the form model
	 * @return null|string
	 */
	final public function getModelClass() {
		if ($this->model === null) return null;

		return get_class($this->model);
	}

	/**
	 * Adds an element to the fieldset
	 * @param array | \dScribe\Form\Element $element
	 * @return Fieldset
	 * @throws Exception
	 */
	public function add($element) {
		$element = $this->processElement($element);
		$this->elements[$element->name] = $element;

		return $this;
	}

	/**
	 * Changes the type element that was added
	 * @param string $elementName The name of the added element
	 * @param string $newElementType The type of element to change the added element to
	 * @return \dScribe\Form\Fieldset
	 */
	final public function changeElementType($elementName, $newElementType) {
		if (array_key_exists($elementName, $this->elements)) {
			$element = $this->elements[$elementName]->toArray(true);
			$element['type'] = $newElementType;
			$elementClass = 'dScribe\Form\Element\\' . ucfirst($element['type']);
			$this->elements[$elementName] = class_exists($elementClass) ? new $elementClass($element, true,
																				   'values') : new Element($element, true, 'values');
		}
		return $this;
	}

	private function processElement($element) {
		if ((is_object($element) && !is_a($element, 'dScribe\Form\Element')) ||
				(!is_object($element) && !is_array($element)))
				throw new Exception('Form elements must be either an array or an object subclass of dScribe\Form\Element');
		else if (is_array($element)) {
			if (!isset($element['type'])) {
				throw new \Exception('Form elements must of key type');
			}
			$elementClass = 'dScribe\Form\Element\\' . ucfirst($element['type']);
			$element = class_exists($elementClass) ? new $elementClass($element, true, 'values') : new Element($element,
																									  true, 'values');
		}
		if (@$this->data[$element->name]) {
			$element->data = $this->data[$element->name];
			unset($this->data[$element->name]);
		}

		if (in_array($element->type, array('checkbox', 'radio'))) {
			$this->booleans[$element->name] = $element->name;
		}
		else if ($element->type === 'fieldset') {
			$this->fieldsets[] = $element->name;
		}
		else if ($element->type === 'hidden' && $element->name === 'csrf') {
			$element->setCsrfKey($this->getName());
		}

		if (!$element->filters) {
			$filters = $this->getFilters();
			if (@$filters[$element->name]) $element->filters = $filters[$element->name];
		}

		return $element;
	}

	public function addBefore($elementName, $element) {
		$element = $this->processElement($element);
		if (array_key_exists($elementName, $this->elements)) {
			$keys = array_keys($this->elements);
			$values = array_values($this->elements);
			$positions = array_flip($keys);
			$position = $positions[$elementName];
			array_splice($keys, $position, 0, $element->name);
			array_splice($values, $position, 0, array($element));
			$this->elements = array_combine($keys, $values);
		}
		else {
			$this->elements[$element->name] = $element;
		}
		return $this;
	}

	public function addAfter($elementName, $element) {
		$element = $this->processElement($element);
		if (array_key_exists($elementName, $this->elements)) {
			$keys = array_keys($this->elements);
			$values = array_values($this->elements);
			$positions = array_flip($keys);
			$position = $positions[$elementName];
			array_splice($keys, $position + 1, 0, $element->name);
			array_splice($values, $position + 1, 0, array($element));
			$this->elements = array_combine($keys, $values);
		}
		else {
			$this->elements[$element->name] = $element;
		}
		return $this;
	}

	/**
	 * Fetches all elements in the fieldset
	 * @return array
	 */
	final public function getElements() {
		return $this->elements;
	}

	/**
	 * Fetches an element
	 * @param string $name
	 * @return Element
	 */
	final public function get($name) {
		return $this->elements[$name];
	}

	/**
	 * Array of filters for the elements
	 * @return array
	 */
	public function getFilters() {
		return array();
	}

	/**
	 * Signifies a filter for an element should be ignored when validating
	 * @param string $elementName
	 * @return Fieldset
	 */
	final public function ignoreFilter($elementName) {
		return $this->removeFilter($elementName);
	}

	/**
	 * Removes a filter for an element
	 * @param string $elementName
	 * @return Fieldset
	 */
	final public function removeFilter($elementName) {
		$this->elements[$elementName]->noFilter = true;
		return $this;
	}

	/**
	 * Removes an element
	 * @param string $elementName
	 * @param bool $return Indicates whether to return the element being removed
	 * @return Fieldset
	 */
	final public function remove($elementName, $return = false) {
		if (isset($this->elements[$elementName])) {
			if ($return) $return = $this->elements[$elementName];
			$this->ignoreFilter($elementName);
			unset($this->elements[$elementName]);
			$elementName = str_replace('[]', '', $elementName);
			unset($this->elements[$elementName]);
			unset($this->data[$elementName]);
		}

		return $return ? $return : $this;
	}

	/**
	 * Sets data to validate
	 * @param \Object | array $data
	 * @return Fieldset|Element
	 * @throws Exception
	 */
	final public function setData($data) {
		if ((is_object($data) && !is_a($data, 'Object')) || (!is_object($data) &&
				!is_array($data)))
				throw new \Exception('Data must be either an <strong>array</strong> or an <strong>object that extends Object</strong> and not <strong>' . gettype($data)) . '</strong>';
		$data = is_array($data) ? $data : $data->toArray();
		foreach ($this->booleans as $name) {
			if (!array_key_exists($name, $data)) $data[$name] = 0;
		}
		foreach ($data as $attr => $value) {
			if (@$this->elements[$attr]) {
				$this->elements[$attr]->setData($value);
			}
		}
		$this->data = $data;
		$this->valid = null;
		return $this;
	}

	/**
	 * Fetches the filtered data
	 * @return Object
	 */
	final public function getData($toArray = false) {
		if (is_null($this->valid)) throw new \Exception('Form must be validated before you can get data');

		$return = array();
		if (count($this->data)) $return = $this->data;
		return ($toArray) ? $return : new \Object($return);
	}

	final public function isMultiple() {
		$this->multiple = true;
		return $this;
	}

	/**
	 * Renders the elements of the fieldset out
	 * @return string
	 */
	public function render() {
		$rendered = '';
		foreach ($this->elements as $element) {
			if (!method_exists($this, 'openTag')) {
				$element->name = $this->multiple ?
						$element->name . '[]' :
						$this->getName() . '[' . $element->name . ']';

				$element->parent = $this->getName();
			}
			$rendered .= $element->render();
		}
		return $rendered;
	}

	/**
	 * Removes the data in the form
	 * @return Form
	 */
	public function reset() {
		foreach ($this->elements as $element) {
			$element->reset();
		}
		return $this;
	}

}
