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

    public $data;

    public function __construct(array $data = array(), $preserveArray = false, $preserveKeyOnly = null) {
        $this->options = new Object();
        $this->attributes = new Object();

        parent::__construct($data, $preserveArray, $preserveKeyOnly);

        if (!$this->name)
            throw new Exception('Form elements must have a name');
        $this->type = strtolower($this->type);

        if (get_class($this->options) !== 'Object')
            throw new Exception('Form element options of "' . $this->name . '" must be an array');
        if ($this->type === 'fieldset' && !isset($this->options->value))
            throw new Exception('Form fieldset element "' . $this->name .
            '" of must have a value of type "DScribe\Form\Fieldset"');
        elseif ($this->type === 'fieldset' && (!is_object($this->options->value) ||
                (is_object($this->options->value) && !in_array('DScribe\Form\Fieldset', class_parents($this->options->value))))) {
            throw new Exception('Form element "' . $this->name .
            '" must have a value of object "DScribe\Form\Fieldset"');
        }
        if (empty($this->attributes->id)) {
            $this->attributes->id = $this->name;
            $this->dId = true;
        }
        else
            $this->dId = false;

        if ($this->attributes->value) {
            if (!$this->options->value && $this->options->value != 0)
                $this->options->value = $this->attributes->value;
            unset($this->attributes->value);
        }

        if (empty($this->options->value) && empty($this->options->values))
            $this->options->value = null;
    }

    /**
     * Parses attributes for rendering
     * @param array $attrs
     * @return string
     */
    final public function parseAttributes() {
        $return = '';
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

    /**
     * Fetches the current value of the element
     * @return mixed
     */
    protected function getValue() {
        return (($this->data || $this->data === 0) && !is_object($this->data)) ?
                $this->data :
                ((isset($this->options->value)) ? $this->options->value : null);
    }

    protected function getName() {
        if ($this->parent) {
            return $this->parent . '[' . $this->name . ']';
        }
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
     * @param bool $custom Indicates whether a custom class will be rendering
     * the elements [TRUE] or the default form class [FALSE]
     * @return string
     */
    public function prepare() {
        if ($this->type === 'file')
            $this->data = '';
        return $this->create() . $this->prepareInfo();
    }

    public function render() {
        ob_start();
        ?>
        <div class="element-group <?= $this->type ?> <?= $this->errors ? 'form-error' : null ?>">
            <?php if ($this->options->label): ?>
                <label for="<?= $this->attributes->id ?>"><?= $this->options->label ?></label>
            <?php endif; ?>
            <?= $this->prepare() ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function validate(Filterer $filterer) {
        if ($this->noFilter)
            return true;

        $filterer->reset();
        $filterer->setElementData($this->data);

        if (!is_object($this->data))
            $filterer->StripTags($this->data, $this->filters->allowTags ?
                            $this->filters->allowTags :
                            ($this->filters->allowTags ? $this->filters->allowTags : ''));
        $valid = true;
        foreach ($this->filters as $filter => $options) {
            if (is_object($options))
                $options = $options->toArray();
            
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

}
