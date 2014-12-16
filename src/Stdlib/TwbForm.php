<?php

use DScribe\Form\Element,
    DScribe\Form\Form;

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

    public static function horizontal(Form $form) {
        ob_start();
        ?>
        <form class="form-horizontal <?= $form->getAttribute('class') ?>" <?= $form->parseAttributes(array('class')) ?>>
            <?php
            foreach ($form->getElements() as $element) {
                if ($element->type === 'fieldset') {
                    ?>
                    <fieldset id="<?= $element->attributes->id ?>" <?= $element->parseAttributes() ?>>
                        <?php
                        ob_start();
                        foreach ($element->options->value->getElements() as $elem):
                            $elem->name = isset($element->options->multiple) ?
                                    $element->name . '[' . $elem->name . '][]' :
                                    $element->name . '[' . $elem->name . ']';
                            $elem->parent = $element->name;
                            static::horizontalElement($elem);
                        endforeach;
                        $fieldset = ob_get_clean();
                        if ($element->options->label):
                            ?>
                            <legend><?= $element->options->label ?><?= $element->getMultipleButton($fieldset) ?></legend>
                            <?php
                        endif;
                        if (!$element->options->label)
                            echo $element->getMultipleButton($fieldset);
                        echo $fieldset;
                        ?>
                    </fieldset>
                    <?php
                } else {
                    static::horizontalElement($element);
                }
            }
            ?>
        </form>
        <?php
        return ob_get_clean();
    }

    private static function horizontalElement(Element $element) {
        ?>
        <?php if ($element->type === 'submit'): ?>
            <div class="form-actions">
            <?php elseif ($element->type !== 'hidden'): ?>
                <div class="control-group">
                <?php endif; ?>
                <?php if ($element->options->label): ?>
                    <label class="control-label" for="<?= $element->attributes->id ?>">
                        <?= $element->options->label ?>
                    </label>
                <?php endif; ?>
                <?php if (!in_array($element->type, array('submit', 'hidden'))): ?>
                    <div class="controls">
                    <?php endif; ?>
                    <?= $element->create() . $element->prepareInfo() ?>
                    <?php if (!in_array($element->type, array('submit', 'hidden'))): ?>
                    </div>
                <?php endif; ?>
                <?php if ($element->type !== 'hidden'): ?>
                </div>
            <?php endif; ?>
            <?php
        }

        public static function inline(Form $form) {
            ob_start();
            ?>
            <style>
                .form-inline button {
                    margin:5px;
                }
            </style>
            <form class="form-inline" <?= $form->parseAttributes() ?>>
                <?php
                foreach ($form->getElements() as $element) {
                    if ($element->type === 'fieldset') {
                        ?>
                        <fieldset id="<?= $element->attributes->id ?>" <?= $element->parseAttributes() ?>>
                            <?php if ($element->options->label): ?>
                                <legend><?= $element->options->label ?></legend>
                            <?php endif; ?>
                            <?php
                            foreach ($element->options->value->getElements() as $elem):
                                $elem->parent = $element->name;
                                static::inlineElement($elem);
                            endforeach;
                            ?>
                        </fieldset>
                        <?php
                    } else {
                        static::inlineElement($element);
                    }
                }
                ?>
            </form>
            <?php
            return ob_get_clean();
        }

        public static function inlineElement(Element $element) {
            if ($element->options->label) {
                ?>
                <label for="<?= $element->attributes->id ?>" class="<?= $element->type ?>"><?= $element->options->label ?></label>
                <?php
            }
            echo $element->create() . $element->prepareInfo();
        }

    }
    
