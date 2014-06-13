<?php

namespace DScribe\Form;

/**
 * A collection of filters with which to filter elements
 *
 * @author topman
 * @todo Add more filters
 * @todo GreaterThan, LessThan, NotMatch
 */
class Filterer {

    /**
     * Array of data to filter
     * @var array
     */
    protected $data;

    /**
     * Array of errors
     * @var array
     */
    protected $error;

    /**
     * Class constructor
     * @param array $data Data to filter
     * @param array $error Array to store errors
     */
    public function __construct(array &$data, array &$error) {
        $this->data = & $data;
        $this->error = & $error;
    }

    /**
     * Checks if a custom message exists or returns the default
     * @param string $defMsg Default message of the filter
     * @param array $options Element filter Options
     * @return string
     */
    private function checkMessage($defMsg, array $options) {
        if (is_array($options) && isset($options['message']))
            return $options['message'];

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
    public function required($name, $options) {
        if (array_key_exists($name, $this->data) && ((is_array($options)) || (!is_array($options) && $options))) {
            if ((is_array($this->data[$name]) && (empty($this->data[$name]) || (empty($this->data[$name][0]) && count($this->data[$name]) === 1))) || (!is_array($this->data[$name]) && @trim($this->data[$name]) === '')) {
                if (is_bool($options))
                    $this->error[$name][] = 'Field is required';
                else if (is_array($options) && isset($options['message'])) {
                    $this->error[$name][] = $options['message'];
                }
                else
                    $this->error[$name][] = (!is_array($options) && !is_object($options)) ? $options : 'Field is required';

                return false;
            }

            return true;
        }
        return false;
    }

    /**
     * Checks if the value of the element matches the given option
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     * @throws \Exception
     */
    public function Match($name, array $options) {
        if (isset($options['element'])) {
            if (isset($this->data[$options['element']])) {
                if ($this->data[$name] == $this->data[$options['element']]) {
                    return true;
                }
            }
            else {
                return true;
            }
        }
        else if (isset($options['value'])) {
            if ($this->data[$name] == $options['value']) {
                return true;
            }
        }

        $this->error[$name][] = $this->checkMessage('Values mismatch', $options);
        return false;
    }

    /**
     * Checks if the value of the element does not match the given option
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     * @throws \Exception
     */
    public function NotMatch($name, array $options) {
        if (isset($options['element'])) {
            if (isset($this->data[$options['element']])) {
                if ($this->data[$name] != $this->data[$options['element']]) {
                    return true;
                }
            }
            else
                return true;
        }
        else if (isset($options['value'])) {
            if ($this->data[$name] != $options['value']) {
                return true;
            }
        }

        $this->error[$name][] = $this->checkMessage('Values must not match', $options);
        return false;
    }

    /**
     * Checks if the value of the element is a valid email address
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function Email($name, array $options) {
        if (empty($this->data[$name]) || filter_var($this->data[$name], FILTER_VALIDATE_EMAIL)) {
            $this->data[$name] = filter_var($this->data[$name], FILTER_SANITIZE_EMAIL);
            return true;
        }

        $this->error[$name][] = $this->checkMessage('Value is not a valid email adddress', $options);
        return false;
    }

    /**
     * Checks if the values of the element is a valid url
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function Url($name, array $options) {
        if (empty($this->data[$name]) || filter_var($this->data[$name], FILTER_VALIDATE_URL)) {
            $this->data[$name] = filter_var($this->data[$name], FILTER_SANITIZE_URL);
            return true;
        }

        $this->error[$name][] = $this->checkMessage('Value is not a valid url', $options);
        return false;
    }

    /**
     * Checks if the contents of the element are all alphabets
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function Alpha($name, array $options) {
        $regex = (isset($options['acceptSpace']) && $options['acceptSpace']) ? '/[^a-zA-Z\s]/' : '/[^a-zA-Z]/';
        if (!preg_match($regex, $this->data[$name]))
            return true;

        $this->error[$name][] = $this->checkMessage('Value can only contain alphabets', $options);
        return false;
    }

    /**
     * Checks if the contents of the element are either alphabets or numbers
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function AlphaNum($name, array $options) {
        $regex = (isset($options['acceptSpace']) && $options['acceptSpace']) ? '/[^a-zA-Z0-9\s]/' : '/[^a-zA-Z0-9]/';
        if (!preg_match($regex, $this->data[$name]))
            return true;

        $this->error[$name][] = $this->checkMessage('Value can only contain alphabets and numbers', $options);
        return false;
    }

    /**
     * Checks if the content of the element is decimal
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function Decimal($name, array $options) {
        if (ctype_digit($this->data[$name]))
            return true;

        $this->error[$name][] = $this->checkMessage('Value can only contain numbers and a dot', $options);
        return false;
    }

    /**
     * @see Filterer::Digit()
     */
    public function Number($name, array $options) {
        if (stristr($this->data[$name], '.')) {
            $this->error[$name][] = $this->checkMessage('Value can only contain numbers', $options);
            return false;
        }
        
        if (!isset($options['message']))
            $options['message'] = 'Value can only contain numbers';
        
        return $this->Decimal($name, $options);
    }

    /**
     * Checks if the value of the element is greater than the given value
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function GreaterThan($name, array $options) {
        if (isset($options['element'])) {
            if (isset($this->data[$options['element']])) {
                $than = 'field "' . $options['element'] . '"';
                if ($this->data[$name] > $this->data[$options['element']] || (empty($this->data[$name]) && $this->data[$name] !== 0)) {
                    return true;
                }
            }
        }
        else if (isset($options['value'])) {
            $than = $options['value'];
            if ($this->data[$name] > $options['value']) {
                return true;
            }
        }

        $this->error[$name][] = $this->checkMessage('Value must be greater than ' . $than, $options);
        return false;
    }

    /**
     * Checks if the value of the element is less than the given value
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function LessThan($name, array $options) {
        if (isset($options['element'])) {
            if (isset($this->data[$options['element']])) {
                $than = 'field "' . $options['element'] . '"';
                if ($this->data[$name] < $this->data[$options['element']]) {
                    return true;
                }
            }
        }
        else if (isset($options['value'])) {
            $than = $options['value'];
            if ($this->data[$name] < $options['value']) {
                return true;
            }
        }

        $this->error[$name][] = $this->checkMessage('Value must be less than ' . $than, $options);
        return false;
    }

    /**
     * Checks if the length of the value of the element is not less than the required length
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function MinLength($name, array $options) {
        if (!isset($options['value'])) {
            throw new \Exception('Filter "MinLength" must have a value to compare against');
        }

        if (strlen($this->data[$name]) >= $options['value'])
            return true;

        $this->error[$name][] = $this->checkMessage('Length must not be less than ' . $options['value'], $options);
        return false;
    }

    /**
     * Checks if the length of the value of the element is not more than the required length
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function MaxLength($name, array $options) {
        if (!isset($options['value'])) {
            throw new \Exception('Filter "MaxLength" must have a value to compare against');
        }

        if (strlen($this->data[$name]) <= $options['value'])
            return true;

        $this->error[$name][] = $this->checkMessage('Length must not be more than ' . $options['value'], $options);
        return false;
    }

    /**
     * Checks if the value of the element is greater or equal to the given value
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function GreaterOrEqualTo($name, array $options) {
        if (isset($options['element'])) {
            if (isset($this->data[$options['element']])) {
                $than = 'field "' . $options['element'] . '"';
                if ($this->data[$name] >= $this->data[$options['element']] || (empty($this->data[$name]) && $this->data[$name] !== 0)) {
                    return true;
                }
            }
        }
        else if (isset($options['value'])) {
            $than = $options['value'];
            if ($this->data[$name] >= $options['value']) {
                return true;
            }
        }

        $this->error[$name][] = $this->checkMessage('Value must be greater than ' . $than, $options);
        return false;
    }

    /**
     * Checks if the value of the element is less  or equal to the given value
     * @param string $name Name of the element to filter
     * @param  array $options Keys may include [message]
     * @return boolean
     */
    public function LessOrEqualTo($name, array $options) {
        if (isset($options['element'])) {
            if (isset($this->data[$options['element']])) {
                $than = 'field "' . $options['element'] . '"';
                if ($this->data[$name] <= $this->data[$options['element']]) {
                    return true;
                }
            }
        }
        else if (isset($options['value'])) {
            $than = $options['value'];
            if ($this->data[$name] <= $options['value']) {
                return true;
            }
        }

        $this->error[$name][] = $this->checkMessage('Value must be less than ' . $than, $options);
        return false;
    }

}
