<?php

/*
 */

namespace DScribe\Form\Element;

use DScribe\Form\Element,
    DScribe\Core\Engine,
    Object;

/**
 * Description of Element
 *
 * @author topman
 */
class Select extends Element {

    public function create() {
        if ((isset($this->options->values) && !is_array($this->options->values)))
            throw new Exception('Values in options for select element "' . $this->name .
            '" must an array of "element_value" => "element_label"');
        else if (isset($this->options->object) && (!isset($this->options->object->class)))
            throw new Exception('Class not specified for select object in element "' . $this->name . '"');
        else if (isset($this->options->object) && isset($this->options->object->class) &&
                !class_exists($this->options->object->class))
            throw new Exception('Class "' . $this->options->object->class . '", as select object for element "' .
            $this->name . '", not found');
        else if (isset($this->options->object)) {
            $model = new $this->options->object->class;
            $table = Engine::getDB()->table($model->getTableName(), $model);
            $this->options->values = new Object();

            $values = array();
            $criteria = (isset($this->options->object->criteria)) ?
                    $this->options->object->criteria : array();

            if (isset($this->options->object->sort)) {
                if (is_string($this->options->object->sort)) {
                    $order = $table->orderBy($this->options->object->sort);
                }
                elseif (is_object($this->options->object->sort)) {
                    if (!isset($this->options->object->sort->column))
                        throw new Exception('Select element with object can only be sorted by columns. No column specified for element "' . $this->name . '"');
                    if (isset($this->options->object->sort->direction))
                        $order = $table->orderBy($this->options->object->sort->column, $this->options->object->sort->direction);
                    else
                        $order = $table->orderBy($this->options->object->sort->column);
                } else {
                    throw new Exception('Sort value for select element with object can be either an array or a string. Invalid value for element for element "' . $this->name . '"');
                }
            }

            $rows = (isset($order)) ? $order->select($criteria) : $table->select($criteria);
            foreach ($rows as $row) {
                if (!isset($this->options->object->labels) && !isset($this->options->object->values))
                    throw new \Exception('Option(s) "labels" and/or "values" is required for select objects');

                $label = $value = null;
                if (isset($this->options->object->labels)) {
                    if (method_exists($row, $this->options->object->labels))
                        $label = $row->{$this->options->object->labels}();
                    elseif (property_exists($row, $this->options->object->labels))
                        $label = $row->{$this->options->object->labels};
                    else
                        throw new \Exception('Class "' . $this->options->object->class . '" does not contain ' .
                        'property/method "' . $this->options->object->labels . '"');
                }

                if (isset($this->options->object->values)) {
                    if (method_exists($row, $this->options->object->values))
                        $value = $row->{$this->options->object->values}();
                    elseif (property_exists($row, $this->options->object->values))
                        $value = $row->{$this->options->object->values};
                    else
                        throw new \Exception('Class "' . $this->options->object->class . '" does not contain ' .
                        'property/method "' . $this->options->object->values . '"');

                    if (!isset($label)) {
                        $label = preg_replace('/[^A-Z0-9\s\'"-]/i', ' ', $value);
                    }
                }

                if (!isset($value)) {
                    $value = $label;
                }

                $values[$label] = $value;
            }

            $this->options->values->add($values);
        }

        $return = '<select name="' . $this->name . (isset($this->attributes->multiple) ? '[]' : '') . '" ' .
                $this->parseAttributes($this->attributes->toArray()) . '>';

        if (isset($this->options->emptyValue)) {
            $return .= '<option value="">' . $this->options->emptyValue . '</option>';
        }

        if (isset($this->options->values)) {
            foreach ($this->options->values as $label => $value) {
                if ($this->options->valueIsLabel)
                    $label = $value;
                $optGroup = false;
                if (is_object($value)) {
                    $value = $value->toArray();
                    $optGroup = true;
                    $return .= '<optGroup';
                    if (!is_int($label)) {
                        $return .= ' label="' . $label . '"';
                    }
                    $return .= '>';
                }
                elseif (is_array($value)) {
                    $optGroup = true;
                    $return .= '<optGroup';
                    if (!is_int($label)) {
                        $return .= ' label="' . $label . '"';
                    }
                    $return .= '>';
                }
                else {
                    $value = array($label => $value);
                }
                if (is_array($label) || is_object($label)) {
                    throw new Exception('Values of "option[values]" array for element "' .
                    $this->name . '" cannot be an array or an object');
                }
                foreach ($value as $lab => $val) {
                    $selected = '';
                    if (($this->data && ($this->data == $val || (is_array($this->data) && in_array($val, $this->data)))) ||
                            !isset($this->data) && isset($this->options->default) && $this->options->default == $val)
                        $selected = 'selected="selected"';

                    $return .= '<option value="' . $val . '" ' .
                            $selected . '>' . $lab . '</option>';
                }

                if ($optGroup) {
                    $return .= '</optGroup>';
                }
            }
        }
        $return .= '</select>';

        return $return;
    }

}
