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
        <form class="form-horizontal" <?= $form->parseAttributes() ?>>
            <?php
            foreach ($form->getElements() as $element) {
                if ($element->type === 'fieldset') {
                    ?>
                    <fieldset id="<?= $element->attributes->id ?>" <?= $element->parseAttributes() ?>>
                        <?php if ($element->options->label): ?>
                            <legend><?= $element->options->label ?></legend>
                        <?php endif; ?>
                        <?php
                        foreach ($element->options->value->getElements() as $element):
                            static::horizontalElement($element);
                        endforeach;
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
            <form class="form-inline">
                <?php
                foreach ($form->getElements() as $element) {
                    if ($element->type === 'fieldset') {
                        ?>
                        <fieldset id="<?= $element->attributes->id ?>" <?= $element->parseAttributes() ?>>
                            <?php if ($element->options->label): ?>
                                <legend><?= $element->options->label ?></legend>
                            <?php endif; ?>
                            <?php
                            foreach ($element->options->value->getElements() as $element):
                                static::inlineElement($element);
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
            ob_start();
            if ($element->options->label) {
                ?>
                <label for="<?= $element->attributes->id ?>" class="<?= $element->type ?>"><?= $element->options->label ?></label>
                <?php
            }
            echo $element->create();
            return ob_get_clean();
        }

    }
    
