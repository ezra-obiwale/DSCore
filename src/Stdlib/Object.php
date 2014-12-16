<?php

class Object {

    private $integerKeys;

    /**
     * Class constructor
     * @param array $data Array of data to initialize into the object
     * @param boolean $preserveArray Indicates whether to leave arrays or make them object too
     * @param boolean $preserveKeyOnly Indicates key whose array to preserve
     */
    public function __construct(array $data = array(), $preserveArray = false, $preserveKeyOnly = null) {
        $this->integerKeys = array();
        $this->add($data, $preserveArray, $preserveKeyOnly);
    }

    /**
     * Adds data to the object
     * @param array $data
     * @param boolean $preserveArray Indicates whether to preserve array or turn them objects too
     * @return Object
     * @throws \Exception
     */
    public function add(array $data, $preserveArray = false, $preserveKeyOnly = null) {
        foreach ($data as $key => $value) {
            if (is_array($value) && (!$preserveArray || ($preserveArray && $preserveKeyOnly && $key !== $preserveKeyOnly))) {
                $this->$key = (array_key_exists(0, $value)) ? $value : new Object($value, $preserveArray, $preserveKeyOnly);
                continue;
            }

            if (is_int($key)) {
                $this->integerKeys[$key] = $value;
                continue;
            }

            $attr = Util::hyphenToCamel(Util::_toCamel($key));
            $this->{$attr} = $value;
        }
        return $this;
    }

    public function __get($name) {
        if (!property_exists($this, $name) && array_key_exists($name, $this->integerKeys)) {
            return $this->integerKeys[$name];
        }
    }

    public function set($property, $value) {
        $this->$property = $value;
        return $this;
    }
    
    public function get($property) {
        return $this->$property;
    }

    /**
     * Check of their are properties in the object
     * @return boolean
     */
    public function notEmpty() {
        return count($this->toArray()) ? true : false;
    }

    /**
     * Returns array of properties
     * @param $recursive
     * @return array
     */
    public function toArray($recursive = false) {
        $return = get_object_vars($this);
        if ($recursive) {
            foreach ($return as $ppt => &$val) {
                if ($ppt === 'integerkeys')
                    continue;
                if (is_object($val) && method_exists($val, 'toArray')) {
                    $val = $val->toArray(true);
                }
            }
        }
        unset($return['integerKeys']);
        return $return;
    }

    /**
     * Counts the number of properties in the object
     * @return int
     */
    public function count() {
        return count($this->toArray());
    }

    /**
     * Removes a property from the object
     * @param string $property
     * @return Object
     */
    public function remove($property) {
        if (isset($this->$property)) {
            unset($this->$property);
        }

        return $this;
    }

    public function reset() {
        foreach (get_object_vars($this) as $prop => $value) {
            unset($this->$prop);
        }
        $this->integerKeys = null;
    }
}
