<?php

use DScribe\Form\Element,
    DScribe\Form\Form;

/**
 * Description of TwbForm
 *
 * @author topman
 */
class TwbForm3 {

    const HORIZONTAL = 'HORIZONTAL';
    const INLINE = 'INLINE';

    public function __invoke(Form $form, $type = self::HORIZONTAL,
            $subElementType = self::HORIZONTAL) {
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
        <form class="form-horizontal <?= $form->getAttribute('class') ?>" role="form" <?=
        $form->parseAttributes(array(
            'class'))
        ?>>
                  <?php
                  foreach ($form->getElements() as $element) {
                      if ($element->type === 'fieldset') {
                          ?>
                    <fieldset id="<?= $element->attributes->id ?>" <?= $element->parseAttributes() ?>>
                        <?php
                        ob_start();
                        foreach ($element->options->value->getElements() as
                                    $elem):
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
        if ($element->type !== 'hidden'):
            ?>
            <div class="form-group <?= $element->attributes->id ?> <?=
            $element->options->hide ? 'hidden' : ''
            ?>">
                     <?php
                 endif;
                 if ($element->options->label):
                     $class = $attrs = null;
                     $label = $element->options->label;
                     if (is_object($label)) {
                         $class = $label->attrs->class;
                         if ($label->attrs)
                                 $attrs = Util::parseAttrArray($label->attrs->toArray(),
                                             array('class'));
                         $label = $label->text;
                     }
                     ?>
                <label class="control-label <?= $class ?>" <?= $attrs ?> for="<?= $element->attributes->id ?>">
                    <?= $label ?>
                </label>
                <?php
            endif;
            if (!in_array($element->type, array('hidden'))):
                $class = $element->options->containerAttrs->class;
                if ($element->options->containerAttrs)
                        $attrs = Util::parseAttrArray($element->options->containerAttrs->toArray(),
                                    array('class'));
                ?>
                <div class="<?= $class . ' ' . $element->type ?> " <?= $attrs ?>>
                    <?php
                endif;
                if ($element->attributes->class) {
                    $element->attributes->class .= ' form-control';
                }
                else {
                    $element->attributes->class = ' form-control';
                }
                ?>
                <?= $element->create() . $element->prepareInfo(Element::BLOCK_INFO) ?>
                <?php if ($element->type !== 'hidden'): ?>
                </div>
                <div><?= $element->prepareInfo(Element::INLINE_INFO) ?></div>
            </div>
            <?php
        endif;
    }

    public static function inline(Form $form) {
        ob_start();
        ?>
        <style>
            .form-inline button {
                margin:5px;
            }
        </style>
        <form class="form-inline" role="form" <?= $form->parseAttributes() ?>>
            <?php
            foreach ($form->getElements() as $element) {
                if ($element->type === 'fieldset') {
                    ?>
                    <fieldset id="<?= $element->attributes->id ?>" <?= $element->parseAttributes() ?>>
                        <?php if ($element->options->label): ?>
                            <legend><?= $element->options->label ?></legend>
                        <?php endif; ?>
                        <?php
                        foreach ($element->options->value->getElements() as
                                    $elem):
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
        if (!in_array($element->type,
                        array('checkbox', 'radio', 'submit', 'button', 'reset', 'hidden'))) {
            ?>
            <div class="form-group">
                <?php
            }
            if ($element->options->label) {
                $label = $element->options->label;
                $class = $attrs = null;
                if (is_object($label)) {
                    $class = $label->attrs->class;
                    $attrs = Util::parseAttrArray($label->attrs, array('class'));
                    $label = $label->text;
                }
                ?>
                <label for="<?= $element->attributes->id ?>" class="<?= $class . ' ' . $element->type ?>" <?= $attrs ?>>
                    <?= $label ?>
                    <?php
                    if (!in_array($element->type, array('checkbox', 'radio'))):
                        ?>
                    </label>
                    <?php
                endif;
            }
            if ($element->attributes->class) {
                $element->attributes->class .= ' form-control';
            }
            else {
                $element->attributes->class = ' form-control';
            }
            echo $element->create() . $element->prepareInfo;
            if (!in_array($element->type,
                            array('checkbox', 'radio', 'submit', 'button', 'reset'))) {
                ?>
            </div>
            <?php
        }
    }

}
