<?php

namespace dScribe\Form;

/**
 * A collection of filters with which to filter elements
 *
 * @author topman
 * @todo Add more filters
 */
class Filterer {

	/**
	 * Array of data from form
	 * @var array
	 */
	protected $data;

	/**
	 * Data of the currently filtering element
	 * @var mixed
	 */
	protected $elementData;

	/**
	 * Array of errors
	 * @var array
	 */
	protected $errors;

	/**
	 * Class constructor
	 * @param array $data Data from form
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}

	public function setData($data) {
		$this->data = $data;
		return $this;
	}

	public function getElementData() {
		return $this->elementData;
	}

	public function setElementData($elementData) {
		$this->elementData = $elementData;
		return $this;
	}

	public function reset() {
		$this->errors = null;
		return $this;
	}

	public function addError($error) {
		if ($error) $this->errors[] = $error;
		return $this;
	}

	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Checks if a custom message exists or returns the default
	 * @param string $defMsg Default message of the filter
	 * @param array $options Element filter Options
	 * @return string
	 */
	private function checkMessage($defMsg, array $options) {
		if (is_array($options) && isset($options['message']))
				return $options['message'] ? $options['message'] : null;

		return $defMsg;
	}

	/**
	 * Makes an element required
	 * @param string $name Name of the element to filter
	 * @param boolean|string|array $options Boolean indicates required or not. 
	 * String indicates required with custom message.
	 * Array indicates required and has options
	 * @return boolean
	 */
	public function required($options) {
		if ((is_array($options) || (!is_array($options) && $options))) {
			if ((is_array($this->elementData) && (empty($this->elementData) || (empty($this->elementData[0]) && count($this->elementData) === 1))) ||
					(!is_array($this->elementData) && trim($this->elementData) === '')) {
				if (is_bool($options)) $this->addError('Field is required');
				else if (is_array($options) && isset($options['message'])) {
					$this->addError($options['message']);
				}
				else
						$this->addError((!is_array($options) && !is_object($options)) ? $options : 'Field is required');
				return false;
			}
			return true;
		}
		return true;
	}

	/**
	 * Checks if the value of the element matches the given option
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 * @throws \Exception
	 */
	public function match(array $options) {
		if (isset($options['element'])) {
			if (isset($this->data[$options['element']])) {
				if ($this->elementData == $this->data[$options['element']]) {
					return true;
				}
			}
			else {
				return true;
			}
		}
		else if (isset($options['value'])) {
			if ($this->elementData == $options['value']) {
				return true;
			}
		}

		$this->addError($this->checkMessage('Values mismatch', $options));
		return false;
	}

	/**
	 * Checks if the value of the element does not match the given option
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 * @throws \Exception
	 */
	public function notMatch(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (isset($options['element'])) {
			if (isset($this->data[$options['element']])) {
				if ($this->elementData != $this->data[$options['element']]) {
					return true;
				}
			}
			else return true;
		}
		else if (isset($options['value'])) {
			if ($this->elementData != $options['value']) {
				return true;
			}
		}

		$this->addError($this->checkMessage('Values must not match', $options));
		return false;
	}

	/**
	 * Checks if the value of the element is a valid email address
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function email(array $options) {
		if (empty($this->elementData) || filter_var($this->elementData, FILTER_VALIDATE_EMAIL)) {
			$this->elementData = filter_var($this->elementData, FILTER_SANITIZE_EMAIL);
			return true;
		}

		$this->addError($this->checkMessage('Invalid email adddress', $options));
		return false;
	}

	/**
	 * Checks if the values of the element is a valid url
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function url(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (empty($this->elementData) || filter_var($this->elementData, FILTER_VALIDATE_URL)) {
			$this->elementData = filter_var($this->elementData, FILTER_SANITIZE_URL);
			return true;
		}

		$this->addError($this->checkMessage('Value is not a valid url', $options));
		return false;
	}

	/**
	 * Checks if the contents of the element are all alphabets
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function alpha(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (!$options['regex'])
				$options['regex'] = ($options['acceptSpace']) ? '/[^a-zA-Z\s]/' : '/[^a-zA-Z]/';
		if (!preg_match($options['regex'], $this->elementData)) return true;

		$this->addError($this->checkMessage('Value can only contain alphabets', $options));
		return false;
	}

	/**
	 * Checks if the contents of the element are either alphabets or numbers
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function alphaNum(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (!$options['regex'])
				$options['regex'] = ($options['acceptSpace']) ? '/[^a-zA-Z0-9\s]/' : '/[^a-zA-Z0-9]/';
		if (!preg_match($options['regex'], $this->elementData)) return true;

		$this->addError($this->checkMessage('Value can only contain alphabets and numbers', $options));
		return false;
	}

	/**
	 * Checks if the content of the element is decimal
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function decimal(array $options) {
		if (($this->elementData != '0' && !$this->elementData) || ctype_digit($this->elementData))
				return true;

		$this->addError($this->checkMessage('Value can only contain numbers and a dot', $options));
		return false;
	}

	/**
	 * @see Filterer::Digit()
	 */
	public function number(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (stristr($this->elementData, '.')) {
			$this->addError($this->checkMessage('Value can only contain numbers', $options));
			return false;
		}

		if (!isset($options['message'])) $options['message'] = 'Value can only contain numbers';

		return $this->decimal($options);
	}

	/**
	 * Checks if the value of the element is greater than the given value
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function greaterThan(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (isset($options['element'])) {
			$than = ' "' . $this->cleanElement($options['element']) . '"';
			if (isset($this->data[$options['element']])) {
				if ($this->elementData > $this->data[$options['element']] || (empty($this->elementData) && $this->elementData !== 0)) {
					return true;
				}
			}
		}
		else if (isset($options['value'])) {
			$than = $options['value'];
			if ($this->elementData > $options['value']) {
				return true;
			}
		}

		$this->addError($this->checkMessage('Value must be greater than ' . $than, $options));
		return false;
	}

	/**
	 * Checks if the value of the element is less than the given value
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function lessThan(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (isset($options['element'])) {
			$than = ' "' . $this->cleanElement($options['element']) . '"';
			if (isset($this->data[$options['element']])) {
				if ($this->elementData < $this->data[$options['element']]) {
					return true;
				}
			}
		}
		else if (isset($options['value'])) {
			$than = $options['value'];
			if ($this->elementData < $options['value']) {
				return true;
			}
		}

		$this->addError($this->checkMessage('Value must be less than ' . $than, $options));
		return false;
	}

	/**
	 * Checks if the length of the value of the element is not less than the required length
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function minLength(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if ($options['value'] && strlen($this->elementData) >= $options['value']) return true;

		$this->addError($this->checkMessage('Length must not be less than ' . $options['value'], $options));
		return false;
	}

	/**
	 * Checks if the length of the value of the element is not more than the required length
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function maxLength(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if ($options['value'] && strlen($this->elementData) <= $options['value']) return true;

		$this->addError($this->checkMessage('Length must not be more than ' . $options['value'], $options));
		return false;
	}

	/**
	 * Checks if the value of the element is greater or equal to the given value
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function greaterOrEqualTo(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (isset($options['element'])) {
			$than = ' "' . $this->cleanElement($options['element']) . '"';
			if (isset($this->data[$options['element']])) {
				if ($this->elementData >= $this->data[$options['element']] || (empty($this->elementData) && $this->elementData !== 0)) {
					return true;
				}
			}
		}
		else if (isset($options['value'])) {
			$than = $options['value'];
			if ($this->elementData >= $options['value']) {
				return true;
			}
		}

		$this->addError($this->checkMessage('Value must be greater than or equal to ' . $than, $options));
		return false;
	}

	/**
	 * Checks if the value of the element is less  or equal to the given value
	 * @param string $name Name of the element to filter
	 * @param  array $options Keys may include [message]
	 * @return boolean
	 */
	public function lessOrEqualTo(array $options) {
		if ($this->elementData != '0' && !$this->elementData) return true;
		if (isset($options['element'])) {
			$than = ' "' . $this->cleanElement($options['element']) . '"';
			if (isset($this->data[$options['element']])) {
				if ($this->elementData <= $this->data[$options['element']]) {
					return true;
				}
			}
		}
		else if (isset($options['value'])) {
			$than = $options['value'];
			if ($this->elementData <= $options['value']) {
				return true;
			}
		}

		$this->addError($this->checkMessage('Value must be less than or equal to ' . $than, $options));
		return false;
	}

	/**
	 * Strips tags from the value of the given name
	 * @see strip_tags()
	 * @param string $name Name of the element to strip value's tags
	 * @param string $allow Tags that will should not be stripped
	 * @return boolean
	 */
	public function stripTags($data, $allow = '') {
		strip_tags($data, $allow);
		return $this;
	}

	private function cleanElement($element) {
		return ucwords(str_replace('_', ' ', \Util::camelTo_($element)));
	}

}
