<?php
/*
 */

namespace dScribe\Form\Element;

use dScribe\Form\Element,
    Object;

/**
 * Description of Element
 *
 * @author topman
 */
class Checkbox extends Element {

    public function __construct(array $data = array(), $preserveArray = false,
            $preserveKeyOnly = null) {
        parent::__construct($data, $preserveArray, $preserveKeyOnly);

        if (!isset($this->options->value) || (isset($this->options->value) && $this->options->value !=
                0)) $this->options->value = '1';
    }

    /**
     * Fetches name to use for multiple elements
     * @return string
     */
    public function getName() {
        return $this->name . '[]';
    }

    public function create() {
        if (!$this->attributes) $this->attributes = new Object();
        $return = '';
        if (isset($this->options->values) && !empty($this->options->values)) {
            if (!is_array($this->options->values))
                    throw new Exception('Values in options for ' . $this->type .
                ' element "' . $this->name .
                '" must an array of "element_label" => "element_value"');

            $return = '';
            foreach ($this->options->values as $label => $value) {
                if (is_object($value) || is_array($value)) {
                    throw new Exception('Values for ' . $this->type .
                    ' element "' . $this->name . '" cannot be an object or '
                    . 'an array');
                }

                if ($this->options->valueIsLabel) $label = $value;

                $checked = (($this->data == $value || (is_array($this->data) && in_array($value,
                                $this->data))) || (!$this->data && isset($this->options->default) &&
                        ($this->options->default == $value || (is_array($this->options->default) &&
                        in_array($value, $this->options->default))))) ?
                        'checked="checked" ' : '';

                $return .= '<label class="inner">' .
                        '<input type="' . $this->type . '" ' .
                        'name="' . $this->getName() . '" ' .
                        'value="' . $value . '" ' . $checked .
                        $this->parseAttributes($this->attributes->toArray()) .
                        ' />&nbsp;&nbsp;' .
                        $label . '</label>';
            }
        }
        else {
            $checked = ($this->data && $this->data != 0 || (!isset($this->data) &&
                    (isset($this->options->default) && $this->options->default)))
                        ?
                    'checked="checked" ' : '';

            $return = '<input type="' . $this->type . '" ' .
                    'name="' . $this->name . '" ' .
                    'value="1" ' . $checked .
                    $this->parseAttributes($this->attributes->toArray()) . ' />';
        }
        return $return;
    }

    public function render() {
        ob_start();
        ?>
        <div class="element-group <?= $this->type ?> <?= $this->errors ? 'form-error' : null ?>">
            <?php
            echo $this->renderLabel($this->options->values ? true : false);
            if (!$this->options->values): echo $this->prepare();
                ?>
            </label>
        <?php endif; ?>
        <?= $this->options->values ? $this->prepare() : null ?>
        </div>
        <?php
        return ob_get_clean();
    }

}
