<?php
/*
 */

namespace DScribe\Form;

use Object,
    Exception;

/**
 * Description of Element
 *
 * @author topman
 */
class Element extends Object {

    public static $count;
    public static $css = false;
    public $data;

    public function __construct(array $data = array(), $preserveArray = false,
            $preserveKeyOnly = null) {
        $this->options = new Object();
        $this->attributes = new Object();

        parent::__construct($data, $preserveArray, $preserveKeyOnly);

        if (!$this->name) throw new Exception('Form elements must have a name');
        $this->type = strtolower($this->type);

        if (get_class($this->options) !== 'Object')
            throw new Exception('Form element options of "' . $this->name . '" must be an array');

        if (empty($this->attributes->id)) {
            $this->attributes->id = $this->name;
            $this->dId = true;
        } else
            $this->dId = false;

        if ($this->attributes->value) {
            if (!$this->options->value && $this->options->value != 0)
                $this->options->value = $this->attributes->value;
            unset($this->attributes->value);
        }

        if (empty($this->options->value) && empty($this->options->values))
            $this->options->value = null;

        if ($this->options->toggleShow) $this->toggleShow();
    }

    protected function toggleShow() {
        if (!$this->options->toggleShow->element)
            throw new \Exception('Element must be specified for option toggleShow'
            . ' in element "' . $this->name . '"');
        else if (!isset($this->options->toggleShow->on) || !isset($this->options->toggleShow->on->action) ||
                !isset($this->options->toggleShow->on->value) || !isset($this->options->toggleShow->on->show))
            throw new \Exception('Option toggleShow must have key "on" with an array value '
            . 'having keys "action" (click, blur, change, etc), "value" (element value), "disable" (bool)');

        if (!Element::$css):
            ?>
            <style>
                .ds-hidden {
                    display:none;
                }
            </style>
            <?php
            Element::$css = true;
        endif;
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                $this = document.querySelector('[name="<?= $this->name ?>"]');
                $this.addEventListener('<?= $this->options->toggleShow->on->action ?>', function () {
//                    alert(('<?= in_array($this->type, array('checkbox', 'radio')) ?>' +
//                                    $this.('checked')));
//                    if (('<?= !in_array($this->type, array('checkbox', 'radio')) ?>' &&
//                            $this.value == '<?= $this->options->toggleShow->on->value ?>') ||
//                            ('<?= in_array($this->type, array('checkbox', 'radio')) ?>' &&
//                                    $this.getAttribute('checked')))
//                        document.querySelector('[name="<?= $this->options->toggleShow->element ?>"]').setAttribute('readonly', 'readonly');
//                    else
//                        document.querySelector('[name="<?= $this->options->toggleShow->element ?>"]').removeAttribute('readonly');

                });
            });
        </script>
        <?php
    }

    /**
     * Parses attributes for rendering
     * @param array $attrs
     * @return string
     */
    final public function parseAttributes() {
        $return = '';
        if (!$this->attributes) $this->attributes = new Object();
        foreach ($this->attributes->toArray() as $attr => $val) {
            $return .= \Util::camelToHyphen($attr) . '="' . $val . '" ';
        }
        return $return;
    }

    /**
     * Renders all element info (inlineInfo, blockInfo and error message) to the
     * element tag. This should be appended to the input tag after rendering.
     * @param array $errorMsgs
     * @return string
     */
    public function prepareInfo() {
        $return = '';
        if ($this->options->inlineInfo)
            $return .= '<span class="help-inline">' . $this->options->inlineInfo . '</span>';
        if ($this->options->blockInfo || $this->errors)
            $return .= '<span class="help-block">' . $this->errors . $this->options->blockInfo . '</span>';
        return $return;
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * Fetches the current value of the element
     * @return mixed
     */
    protected function getValue() {
        $value = null;
        if (($this->data == '0' || !empty($this->data)) && !is_object($this->data)) {
            $value = ($this->parent && is_array($this->data)) ? $this->data[0] : $this->data;
        } else if ($this->options->default == '0' || !empty($this->options->default)) {
            $value = $this->options->default;
        } else if (($this->options->value == '0' || !empty($this->options->value)) && !is_object($this->options->value)) {
            $value = $this->options->value;
        }

        if ($this->options->processValue) {
            $value = call_user_func($this->options->processValue, $value);
        }
        return $value;
    }

    protected function getName() {
        return $this->name . (isset($this->attributes->multiple) ? '[]' : '');
    }

    /**
     * Create the element tag
     * @return string
     */
    public function create() {
        return '<input type="' . $this->type . '" ' .
                'name="' . $this->getName() . '" ' .
                'value="' . $this->getValue() . '" ' .
                $this->parseAttributes() . ' />';
    }

    /**
     * Prepare the element for rendering, creating the tag and adding provided
     * information
     * @return string
     */
    public function prepare() {
        if ($this->type === 'file')
            $this->data = '';
        return $this->create() . $this->prepareInfo();
    }

    /**
     * Renders the label of the element
     * @param bool $close Inidicates whether to close the label or leave open
     * @return string
     */
    public function renderLabel($close = true) {
        ob_start();
        if ($this->options->label):
            $label = $this->options->label;
            if (is_object($label)) {
                if ($label->attrs)
                        $attrs = \Util::parseAttrArray($label->attrs->toArray());
                $label = $label->text;
            }
            ?>
            <label for="<?= $this->attributes->id ?>" <?= $attrs ?>><?= $label ?>
                <?php if ($close): ?>
                </label>
                <?php
            endif;
        endif;
        return ob_get_clean();
    }

    /**
     * Render the element for output
     * @return string
     */
    public function render() {
        if ($this->parent) {
            if (!static::$count[$this->parent])
                    static::$count[$this->parent] = 0;
            static::$count[$this->parent] ++;

            $this->attributes->id += static::$count[$this->parent];
        }
        ob_start();
        ?>
        <div class="element-group <?= $this->type ?> <?= $this->errors ? 'form-error' : null ?>">
            <?= $this->renderLabel() ?>
            <?= $this->prepare() ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function validate(Filterer $filterer, $data) {
        if ($this->noFilter) return true;
        $filterer->reset();
        $filterer->setData($data);
        $filterer->setElementData($this->data);

        if (!is_object($this->data))
                $filterer->StripTags($this->data,
                    $this->filters->allowTags ?
                            $this->filters->allowTags :
                            ($this->filters->allowTags ? $this->filters->allowTags
                                        : ''));
        $valid = true;
        foreach ($this->filters as $filter => $options) {
            if (is_object($options)) $options = $options->toArray();

            if (method_exists($filterer, $filter)) {
                if (!call_user_func_array(array($filterer, $filter), array($options))) {
                    $valid = false;
                }
            }
        }
        $this->data = $filterer->getElementData();
        $this->prepareErrorMsgs($filterer->getErrors());
        return $valid;
    }

    private function prepareErrorMsgs($errors) {
        if (!$errors || ($errors && !count($errors)))
            return null;
        ob_start();
        ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
        <?php endforeach; ?>
        </ul>
        <?php
        $this->errors = ob_get_clean();
    }

    public function __toString() {
        return get_called_class();
    }

    /**
     * Checks whether element is of the given type
     * @param string $type
     * @return bool
     */
    public function is($type) {
        return (strtolower($type) === $this->type);
    }

    public function reset() {
        unset($this->data);
    }

}
