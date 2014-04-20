<?php

use DScribe\Form\Form;

/**
 * Description of TwbForm
 *
 * @author topman
 */
class TwbForm {

    const HORIZONTAL = 'HORIZONTAL';
    const INLINE = 'INLINE';

    public function __invoke(Form $form, $type = self::HORIZONTAL, $subElementType = self::HORIZONTAL) {
        if ($type === self::HORIZONTAL) {
            return self::horizontal($form, $subElementType);
        }
        elseif ($type === self::INLINE) {
            return self::inline($form, $subElementType);
        }
    }

    public static function horizontal(Form $form, $subElementType = self::HORIZONTAL) {
        ob_start();
        ?>
        <form class = "form-horizontal" <?= $form->getAttributes(true) ?>>
            <?php
            $data = $form->getData();
            foreach ($form->prepare(true)->toArray() as $element):
                if ($element->type !== 'fieldset'):
                    if (isset($element->default) && isset($data->{$element->name})) {
                        $element->default = $data->{$element->name};
                    }
                    if (!in_array($element->type, array('submit', 'hidden'))) :
                        ?>
                        <div class = "control-group <?= $form->hasError($element->name) ? 'error' : '' ?>">
                        <?php endif; ?>
                        <?= self::horizontalElement($element, $form->getLabel($element->name), $subElementType) ?>
                        <?php if (!in_array($element->type, array('submit', 'hidden'))): ?>
                        </div>
                        <?php
                    endif;
                else:
                    ?>
                    <fieldset <?= $element->attributes ?> id="<?= $element->id ?>" >
                        <?php if (isset($element->label)): ?>
                            <legend <?= $element->label->attributes ?>><?= $element->label->label ?></legend>
                        <?php endif; ?>
                        <?php
                        foreach ($element->elements as $elem):
                            if ($element->type !== 'submit') :
                                ?>
                                <div class = "control-group <?= $form->hasError($element->name) ? 'error' : '' ?>">
                                <?php endif; ?>
                                <?= self::horizontalElement($elem, $form->getLabel($elem->name), $subElementType) ?>
                                <?php if ($element->type !== 'submit'): ?>
                                </div>
                                <?php
                            endif;
                        endforeach;
                        ?>
                    </fieldset>
                <?php
                endif;
            endforeach;
            ?>
            <?php
            return ob_get_clean();
        }

        private static function horizontalElement(\Object $element, $label, $subElementType = self::HORIZONTAL) {
            ob_start();
            if ($label && $element->type !== 'hidden'):
                if (!in_array($element->type, array('checkbox', 'radio')) || (in_array($element->type, array('checkbox', 'radio')) && is_object($element->tag))):
                    ?>
                    <label class = "control-label" for = "<?= $element->id ?>">
                        <?= $label->label ?>
                    </label>
                <?php endif; ?>
                <?php
            endif;

            if ($element->type !== 'hidden'):
                ?>
                <div class = "<?= ($element->type !== 'submit') ? 'controls' : 'form-actions' ?>">
                    <?php
                endif;
                if (in_array($element->type, array('checkbox', 'radio')) && !is_object($element->tag)):
                    ?>
                    <label class = "<?= $element->type ?>">
                    <?php endif; ?>
                    <?php
                    if (!is_object($element->tag)) {
                        echo str_replace('form-error', 'help-inline', $element->tag);
                    }
                    else {
                        foreach ($element->tag->toArray() as $label => $value) {
                            ?>
                            <label class="<?= $element->type ?> <?= ($subElementType === self::INLINE) ? 'inline' : '' ?>">
                                <input <?= ((isset($element->default) && $value === $element->default) || $value === $label) ? 'checked="checked"' : '' ?> type="<?= $element->type ?>" name="<?= ($element->type === 'checkbox') ? $element->name . '[]' : $element->name ?>" value="<?= $value ?>" /><?= $label ?>
                            </label>
                            <?php
                        }
                    }
                    ?>
                    <?php if ($label && in_array($element->type, array('checkbox', 'radio')) && !is_object($element->tag)): ?>
                        <?= $label->label ?>
                    </label>
                    <?php
                endif;
                if ($element->type !== 'hidden'):
                    ?>
                </div>
                <?php
            endif;
            return ob_get_clean();
        }

        public static function inline(Form $form) {
            ob_start();
            ?>
            <form class="form-inline">
                <?php
                foreach ($form->prepare(true)->toArray() as $element):
                    if ($element->type !== 'fieldset'):
                        ?>
                        <?= self::inlineElement($element, $form) ?>
                        <?php
                    else:
                        ?>
                        <fieldset <?= $element->attributes ?> id="<?= $element->id ?>" >
                            <?php if ($labelObject = $form->getLabel($element->name)): ?>
                                <legend <?= $labelObject->attributes ?>><?= $labelObject->label ?></legend>
                            <?php endif; ?>
                            <?php
                            foreach ($element->elements as $elem):
                                echo self::inlineElement($elem, $form);
                            endforeach;
                            ?>
                        </fieldset>
                    <?php
                    endif;
                endforeach;
                ?>
            </form>
            <?php
            return ob_get_clean();
        }

        public static function inlineElement(\Object $element, Form $form) {
            $label = $form->getLabel($element->name);
            ob_start();
            if ($element->type !== 'fieldset'):
                if ($label):
                    echo '<label ' . $label->attributes . '>';
                    if (!in_array($element->type, array('radio', 'checkbox')) || (in_array($element->type, array('radio', 'checkbox')) && is_object($element->tag))):
                        echo $label->label;
                    endif;
                endif;
                if (!is_object($element->tag)):
                    echo $element->tag;
                else:
                    foreach ($element->tag as $label => $value):
                        echo '<label class="' . $element->type . '"><input type="' . $element->type . '" name="' .
                        (($element->type === 'checkbox') ? $element->name . '[]' : $element->name) .
                        '" value="' . $value . '" />' . $label . '</label>';
                    endforeach;
                endif;
                if ($label):
                    if (in_array($element->type, array('radio', 'checkbox')) && !is_object($element->tag)):
                        echo $label->label;
                    endif;
                    echo '</label>';
                endif;
            else:
                echo '<fieldset ' . $element->attributes . '>';
                if (isset($element->label)):
                    echo '<legend>' . $element->label . '</legend>';
                endif;
                foreach ($element->elements as $elem) {
                    self::inlineElement($elem, $form);
                }
                echo '</fieldset>';
            endif;
            return ob_get_clean();
        }

    }
    