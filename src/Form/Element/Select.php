<?php

/*
 */

namespace DScribe\Form\Element;

use DScribe\Form\Element,
    Object,
    Exception;

/**
 * Description of Element
 *
 * @author topman
 */
class Select extends Element {

    private static $loadDependantScript = true;

    public function create() {
        if (!$this->attributes)
            $this->attributes = new Object();

        if ((isset($this->options->values) && !is_array($this->options->values)))
            throw new \Exception('Values in options for select element "' . $this->name .
            '" must have an array of "element_value" => "element_label"');
        else if (isset($this->options->object)) {
            if (!isset($this->options->object->class))
                throw new Exception('Class not specified for select object in element "' . $this->name . '"');
            else if (!is_object($this->options->object->class) && !class_exists($this->options->object->class))
                throw new Exception('Class "' . $this->options->object->class . '", as select object for element "' .
                $this->name . '", not found');
            else
                $this->targetObject();
        }

        if (isset($this->options->dependant)) {
            if (!isset($this->options->dependant->element))
                throw new \Exception('Element not specified for select dependant in element "' . $this->name . '"');
            else if (!isset($this->options->dependant->url))
                throw new \Exception('URL not specified for select dependant in element "' . $this->name . '"');
            else if (!isset($this->options->dependant->labels))
                throw new \Exception('Labels not specified for select dependant in element "' . $this->name . '"');
            else if (static::$loadDependantScript)
                $this->dependant();
            $this->attributes->class .= ' withDependant';
            $this->attributes->dataUrl = $this->options->dependant->url;
            $this->attributes->dataGet = $this->options->dependant->get;
            $this->attributes->dataLabels = $this->options->dependant->labels;
            $this->attributes->dataValues = $this->options->dependant->values;
            $this->attributes->dataDependant = $this->options->dependant->element;
        }
        $currentValue = $this->getValue();
        if ($currentValue)
            $this->attributes->dataSelected = $currentValue;
        if ($this->options->emptyValue)
            $this->attributes->dataEmptyValue = $this->options->emptyValue;

        $return = '<select name="' . $this->getName() . '" ' .
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
                    if ((is_array($currentValue) && in_array($val, $currentValue)) ||
                            (!is_array($currentValue) && $this->getValue() == $val))
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
        $return .= '<span class="loading-message ' . $this->attributes->id . '" style="display:none">' .
                ($this->options->loadingMessage ? $this->options->loadingMessage :
                        '<i><b>loading...</b></i>') . '</span>';
        return $return;
    }

    private function targetObject() {
        $model = new $this->options->object->class;
        $table = engineGet('DB')->table($model->getTableName(), $model);

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
                if (is_string($this->options->object->labels)) {
                    if (method_exists($row, $this->options->object->labels))
                        $label = $row->{$this->options->object->labels}();
                    elseif (property_exists($row, $this->options->object->labels))
                        $label = $row->{$this->options->object->labels};
                    else if (!is_object($this->options->object->labels)) {
                        throw new \Exception('Class "' . $this->options->object->class . '" does not contain ' .
                        'property/method "' . $this->options->object->labels . '"');
                    }
                } else {
                    if (empty($this->options->object->labels->pattern) ||
                            empty($this->options->object->labels->values)) {
                        throw new \Exception('Object labels array must have keys pattern and values');
                    }

                    $label = $this->options->object->labels->pattern;
                    foreach ($this->options->object->labels->values as $k => $v) {
                        if (method_exists($row, $v))
                            $v = $row->{$v}();
                        elseif (property_exists($row, $v))
                            $v = $row->{$v};
                        else if (!is_object($v)) {
                            throw new \Exception('Class "' . $this->options->object->class . '" does not contain ' .
                            'property/method "' . $v . '"');
                        }
                        $label = str_replace($k, $v, $label);
                    }
                }
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

        $this->options->values = $values;
    }

    private function dependant() {
        ?>
        <script type="text/javascript">
            var selectDefaults = {};
            document.addEventListener('DOMContentLoaded', function () {
                var source = document.querySelector('select.withDependant');
                source.addEventListener('change', function () {
                    var target = document.querySelector(source.getAttribute('data-dependant'));
                    while (target.lastChild) {
                        target.removeChild(target.lastChild);
                    }
                    if (!source.value && target.getAttribute('data-empty-value')) {
                        var def = document.createElement('option'),
                                defVal = document.createTextNode(target.getAttribute('data-empty-value'));
                        def.setAttribute('value', '');
                        def.appendChild(defVal);
                        target.appendChild(def);
                    }
                    else {
                        var targetDisplay = target.style.display;
                        target.style.display = 'none';
                        var loadingMessage = document.querySelector('.loading-message.' + target.getAttribute('id'));
                        loadingMessage.style.display = 'inline';
                        var httpRequest = new XMLHttpRequest(), result = null;
                        httpRequest.onreadystatechange = function () {
                            if (!result && httpRequest.responseText) {
                                result = JSON.parse(httpRequest.responseText);

                                result.forEach(function (v, i) {
                                    var option = document.createElement('option'),
                                            content = document.createTextNode(v[source.getAttribute('data-labels')]);

                                    if (source.getAttribute('data-values'))
                                        option.setAttribute('value', v[source.getAttribute('data-values')]);
                                    if ((source.getAttribute('data-values') &&
                                            target.getAttribute('data-selected') == v[source.getAttribute('data-values')]) ||
                                            (!source.getAttribute('data-values') &&
                                                    target.getAttribute('data-selected') == v[source.getAttribute('data-labels')]))
                                        option.setAttribute('selected', 'selected');
                                    option.appendChild(content);

                                    target.appendChild(option);
                                });
                                loadingMessage.style.display = 'none';
                                target.style.display = targetDisplay;
                            }
                        };
                        if (source.getAttribute('data-get')) {
                            url = source.getAttribute('data-url') + '?' + source.getAttribute('data-get') + '=' + source.value;
                        } else {
                            url = source.getAttribute('data-url') + '/' + source.value;
                        }
                        httpRequest.open('GET', url);
                        httpRequest.send();
                    }
                });
                if (source.value) {
                    if (document.createEventObject) {
                        var evt = document.crateEventObject();
                        source.fireEvent('onChange', evt);
                    } else {
                        var evt = document.createEvent('HTMLEvents');
                        evt.initEvent('change', true, true);
                        source.dispatchEvent(evt);
                    }
                }
            });
        </script>
        <?php

        static::$loadDependantScript = false;
    }

}
