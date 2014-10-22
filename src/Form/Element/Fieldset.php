<?php
/*
 */

namespace DScribe\Form\Element;

use DScribe\Form\Element,
    DScribe\Form\Filterer;

/**
 * Description of Element
 *
 * @author topman
 */
class Fieldset extends Element {

    public function __construct(array $data = array(), $preserveArray = false, $preserveKeyOnly = null) {
        parent::__construct($data, $preserveArray, $preserveKeyOnly);
    }

    public function prepare() {
        
    }

    public function render() {
        ob_start();
        ?>
        <fieldset id="<?= $this->name ?>" style="margin-bottom:10px">
            <?php if ($this->options->label): ?>
                <legend><?= $this->options->label ?></legend>
            <?php endif; ?>
            <?= $this->options->value->render() ?>
            <?php if ($this->options->multiple): ?>
                <button type="button" id="fieldsetMore<?= ucfirst($this->name) ?>"><?=
                    (is_object($this->options->multiple) &&
                    $this->options->multiple->buttonLabel) ?
                            $this->options->multiple->buttonLabel : 'more'
                    ?></button>
                <script>
                    function deleteAddition(btn) {
                        btn.parentNode.parentNode.removeChild(btn.parentNode);
                        count--;
                        if (count < parseInt('<?= $this->options->multiple->max ?>')) {
                            button.style.display = 'block';
                        }
                    }

                    var fieldset = document.getElementById('<?= $this->name ?>');
                    fieldset.scrollIntoView();
                    var button = document.getElementById('fieldsetMore<?= ucfirst($this->name) ?>');
                    var legend = fieldset.firstElementChild;
                    fieldset.removeChild(legend);
                    fieldset.removeChild(button);
                    var more = fieldset.innerHTML;
                    fieldset.insertBefore(legend);
                    fieldset.appendChild(button);
                    var count = 0;
                    button.addEventListener('click', function (e) {
                        if ((count + 1) == parseInt('<?= $this->options->multiple->max ?>')) {
                            this.style.display = 'none';
                        }
                        var m = document.createElement('div');
                        m.innerHTML = '<div style="border:1px dashed">' + more + ' <button class="close" type="button" onclick="deleteAddition(this)" style="display:block;margin-bottom:10px">delete</button></div>';
                        fieldset.removeChild(button);
                        while (m.firstChild) {
                            fieldset.appendChild(m.firstChild);
                        }
                        fieldset.appendChild(button);
                        fieldset.lastElementChild.scrollIntoView();
                        count++;
                    });
                </script>
            <?php endif; ?>
        </fieldset>
        <?php
        return ob_get_clean();
    }

    public function validate(Filterer $filterer) {
        return $this->options->value->isValid();
    }

}
