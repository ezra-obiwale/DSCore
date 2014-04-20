<?php

/**
 * Description of TwitterBootstrap
 *
 * @author topman
 */
class TwBootstrap {

    /**
     * Number of modals on page already
     * @var int
     */
    protected static $modals = 0;

    /**
     * Number of accordions on page already
     * @var int
     */
    protected static $accordions = 0;

    /**
     * Number of carousels in current page
     * @var int
     */
    protected static $carousels = 0;

    /**
     * Array containing all preserved modal links
     * @var array
     */
    protected static $modalLinks = array();

    /**
     *
     * @param array $contents array(<br />
      "tab1" => "content",<br />
      "tab2" => "content",<br />
      "tab3" => "content",<br />
      );
     * @param array $options array (
     * @return type
     */
    public static function createTabs(array $contents, array $options = array()) {
        ob_start();

        array(
            "active" => "tab2",
            "tab_class" => "the class",
        );
        ?>
        <div class="tabbable"> <!-- Only required for left/right tabs -->
            <ul class="nav nav-tabs">
                <?php
                $tabs = array_keys($contents);
                foreach ($tabs as $key => $tab) {
                    ?>
                    <li class="<?= ($tab == @$options["active"]) ? "active" : "" ?> <?= @$options["tab_class"] ?>">
                        <a href="#tab<?= $key + 1 ?>" data-toggle="tab"><?= $tab ?></a>
                    </li>
                    <?php
                }
                ?>
            </ul>
            <div class="tab-content">
                <?php
                $count = 1;
                foreach ($contents as $tab => $content) {
                    ?>
                    <div class="tab-pane <?= ($tab == @$options["active"]) ? "active" : "" ?>" id="tab<?= $count ?>">
                        <p><?= $content ?></p>
                    </div>
                    <?php
                    $count++;
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Creates an alert message
     *
     * @param string $type Ex. success|danger|error
     * @param string $message The alert message
     * @param string $head [optional] The alert heading
     * @return string
     */
    public static function createAlert($type, $message, $head = "") {
        $to_return = "<div class='alert alert-block alert-{$type}'>
              <button type='button' class='close' data-dismiss='alert'>×</button>";

        if (!empty($head)) {
            $to_return .= "<u><h2>{$head}</h2></u>";
        }

        $to_return .= addslashes($message) .
                "</div>";

        return stripslashes($to_return);
    }

    /**
     * Creates a modal
     *
     * @param array $options Array of options to create the modal with.
     * <p>
     * 	All keys are optional and include:<br /><br />
     * 		<b>href</b> (string)			-	The href of the link<br />
     * 		<b>linkLabel</b> (string)		-	The label to show in the link<br />
     * 		<b>linkAttrs</b> (array)		-	Attributes to pass to the link<br />
     * <br />
     * 		<b>modalClass</b> (string)		-	Class(es) to append the class attribute of the modal container<br />
     * 		<b>modalId</b> (string)			-	Id for the modal container.<br />
     * <br />
     * 		<b>header</b> (string|boolean)	-	The header to show in the modal or FALSE if not too show<br />
     * 		<b>headerAttrs</b> (array)		-	Attributes to pass to the header container<br />
     * <br />
     * 		<b>content</b> (string)			-	The content of the modal
     * 		<b>contentClass</b> (string)	-	Class(es) to append to the content class attribute
     * <br />
     * 		<b>footer</b> (string|boolean)	-	The footer to show in the modal or FALSE if not too show<br />
     * 		<b>footerAttrs</b> (array)		-	Attributes to pass to the footer container<br />
     * 		<b>closeButtonLabel</b> (string)-	Label of the close button<br />
     * 		<b>noActionButton</b> (boolean)	-	Indicates whether to remove the action button or not<br />
     * </p>
     * @param boolean $withLink Indicates if the link should be returned with the modal content.
     * If not, the link can be accessed through method modalLink() with the id as the parameter.
     * @return string
     */
    public static function modal(array $options = array(), $withLink = true) {
        self::$modals++;
        $id = isset($options['modalId']) ? $options['modalId'] : 'myModal' . self::$modals;
        ob_start();
        ?>
        <!-- Button to trigger modal -->
        <a href="<?= isset($options['href']) ? $options['href'] : '#' ?>" data-target="#<?= $id ?>" role="button" <?= isset($options['linkAttrs']) ? self::parseAttributes($options['linkAttrs']) : '' ?> data-toggle="modal"><?= isset($options['linkLabel']) ? $options['linkLabel'] : 'Launch Modal' ?></a>

        <?php
        self::$modalLinks[$id] = ob_get_clean();
        ob_start();
        if ($withLink)
            echo self::$modalLinks[$id];
        ?>
        <!-- Modal -->
        <section id="<?= $id ?>" class="modal hide fade <?= isset($options['modalClass']) ? $options['modalClass'] : '' ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <?php
            if (isset($options['header']) &&
                    !is_bool($options['header']) ||
                    (isset($options['header']) && is_bool($options['header']) &&
                    $options['header'])):
                ?>
                <header class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel" <?= isset($options['headerAttrs']) ? self::parseAttributes($options['headerAttrs']) : '' ?>><?= isset($options['header']) ? $options['header'] : 'Modal Header' ?></h3>
                </header>
            <?php endif; ?>
            <div style="overflow-x: visible!important" class="modal-body <?= isset($options['contentClass']) ? $options['contentClass'] : '' ?>">
                <p><?= isset($options['content']) ? $options['content'] : '<i icon="icon-refresh"></i> loading content ...' ?></p>
            </div>
            <?php
            if (isset($options['footer']) &&
                    !is_bool($options['footer']) ||
                    (isset($options['footer']) && is_bool($options['footer']) &&
                    $options['footer'])):
                ?>
                <footer class="modal-footer" <?= isset($options['footerAttrs']) ? self::parseAttributes($options['footerAttrs']) : '' ?>>
                    <?php if (empty($options['footer']) || is_bool($options['footer'])): ?>
                        <button class="btn" data-dismiss="modal" aria-hidden="true"><?= isset($options['closeButtonLabel']) ? $options['closeButtonLabel'] : 'Close' ?></button>
                        <?php if (!isset($options['noActionButton']) || (isset($options['noActionButton']) && !$options['noActionButton'])): ?>
                            <button class="btn btn-primary">Save changes</button>
                            <?php
                        endif;
                    else:
                        echo $options['footer'];
                    endif;
                    ?>
                </footer>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    private static $addJs = true;

    public static function customModal(array $options = array(), $withLink = true) {
        self::$modals++;
        $id = isset($options['modalId']) ? $options['modalId'] : 'myModal' . self::$modals;

        $linkAttrs = isset($options['linkAttrs']) ? $options['linkAttrs'] : array();
        if ($linkAttrs['class'])
            $linkAttrs['class'] .= ' open-modal';
        else
            $linkAttrs['class'] = 'open-modal';

        $options['linkAttrs'] = $linkAttrs;

        ob_start();
        ?>
        <!-- Button to trigger modal -->
        <a href="<?= isset($options['href']) ? $options['href'] : '#' ?>" data-target="#<?= $id ?>" role="button" <?= isset($options['linkAttrs']) ? self::parseAttributes($options['linkAttrs']) : '' ?> ><?= isset($options['linkLabel']) ? $options['linkLabel'] : 'Launch Modal' ?></a>

        <?php
        self::$modalLinks[$id] = ob_get_clean();
        ob_start();
        if ($withLink)
            echo self::$modalLinks[$id];
        ?>
        <!-- Modal -->
        <section id="<?= $id ?>" style="overflow-x:auto!important" class="modal hide fade <?= isset($options['modalClass']) ? $options['modalClass'] : '' ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <?php
            if (isset($options['header']) &&
                    !is_bool($options['header']) ||
                    (isset($options['header']) && is_bool($options['header']) &&
                    $options['header'])):
                ?>
                <header class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel" <?= isset($options['headerAttrs']) ? self::parseAttributes($options['headerAttrs']) : '' ?>><?= isset($options['header']) ? $options['header'] : 'Modal Header' ?></h3>
                </header>
            <?php endif; ?>
            <article style="overflow-x: auto!important;position:relative" class="modal-body <?= isset($options['contentClass']) ? $options['contentClass'] : '' ?>">
                <p><?= isset($options['content']) ? $options['content'] : '<i icon="icon-refresh"></i> loading content ...' ?></p>
            </article>
            <?php
            if (isset($options['footer']) &&
                    !is_bool($options['footer']) ||
                    (isset($options['footer']) && is_bool($options['footer']) &&
                    $options['footer'])):
                ?>
                <footer class="modal-footer" <?= isset($options['footerAttrs']) ? self::parseAttributes($options['footerAttrs']) : '' ?>>
                    <?php if (empty($options['footer']) || is_bool($options['footer'])): ?>
                        <button class="btn" data-dismiss="modal" aria-hidden="true"><?= isset($options['closeButtonLabel']) ? $options['closeButtonLabel'] : 'Close' ?></button>
                        <?php if (!isset($options['noActionButton']) || (isset($options['noActionButton']) && !$options['noActionButton'])): ?>
                            <button class="btn btn-primary">Save changes</button>
                            <?php
                        endif;
                    else:
                        echo $options['footer'];
                    endif;
                    ?>
                </footer>
            <?php endif; ?>
        </section>
        <?php
        if (self::$addJs) {
            ?>
            <script>
                $(document).ready(function() {
                    $('a.open-modal').on('click', function(e) {
                        e.preventDefault();
                        var modal = $(this).attr('data-target');
                        $(modal).modal({
                            show: true
                        }).on('shown', function() {
                            $('body').addClass('no-scroll');
                        }).on('hidden', function() {
                            $('body').removeClass('no-scroll');
                        });
                        if (!$(modal).hasClass('loaded') || $(modal).hasClass('reuse')) {
                            var modalBody = $(this).children('.modal-body');
                            $(modalBody).html('<div class="progress progress-strip active"><div class="bar" style="width:100%">Loading content. Please wait ...</div></div>');
                            $(modal).load($(this).attr('href'), function(data) {
                                h = $(window).height() - 200;
                                $(this).css({
                                    position: 'absolute',
                                    left: ($(window).width() - $(this).width()) / 2,
                                    height: h,
                                    top: 100,
                                    margin: 0,
                                    'overflow-x': 'auto'
                                });

                                $(modalBody).html(data).css({
                                    height: $(this).height(),
                                    //                        width: $(modalBody).width() - 5,
                                    'max-height': $(this).height() - 30
                                });
                                $(this).addClass('loaded');
                            });
                        }
                    });
                });
            </script>
            <?php
            self::$addJs = false;
        }

        return ob_get_clean();
    }

    /**
     * Retrieves the link to a modal
     * @param string $modalId Id of the modal
     * @return string|null
     */
    public static function modalLink($modalId) {
        if (isset(self::$modalLinks[$modalId]))
            return self::$modalLinks[$modalId];
    }

    /**
     *
     * @param string $label
     * @param array $list Array of label => (array)options<br />
     * 		<b>options</b> keys [link, (array)attributes]
     * @param string $groupClass Class to add to the btn-group
     * @param string $linkClass Class to add to the link
     * @return string
     */
    public static function createDropDown($label, array $list, $groupClass = null, $linkClass = null) {
        ob_start();
        ?>
        <div class = "btn-group <?php echo $groupClass ?>">
            <a class = "btn dropdown-toggle <?php echo $linkClass ?>" style="margin-top:-10px!important;" data-toggle = "dropdown" href = "#">
                <?php echo $label ?>
                <span class = "caret"></span>
            </a>
            <?php self::doDropDown($list) ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function doDropDown($list) {
        ?>
        <ul class = "dropdown-menu">
            <?php
            foreach ($list as $label => $options):
                if (!isset($options['link']))
                    $options['link'] = '#';
                ?>
                <?php if (!is_array($options) && strtolower($options) === 'divider'): ?>
                    <li class="divider"></li>
                    <?php continue; ?>
                <?php endif; ?>
                <li <?= (isset($options['children'])) ? 'class="dropdown-submenu"' : '' ?>>
                    <a <?= (isset($options['children'])) ? 'class="dropdown-toggle" data-toggle="dropdown"' : '' ?> href="<?php echo @$options["link"] ?>" <?php echo (!empty($options["attributes"])) ? self::parseAttributes($options["attributes"]) : "" ?>><?php echo $label ?></a>
                    <?php
                    if (isset($options['children'])) {
                        self::doDropDown($options['children']);
                    }
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php
    }

    /**
     * Parses the attributes for use
     * @param array $attributes
     * @param array $ignore Array of attributes to ignore
     * @return string
     */
    final public static function parseAttributes($attributes, $ignore = array()) {
        if (!is_array($attributes)) {
            return $attributes;
        }
        $return = "";
        foreach ($attributes as $attr => $value) {
            if (in_array($attr, $ignore))
                continue;
            $return .= $attr . '="' . $value . '" ';
        }

        return $return;
    }

    /**
     * Creates a carousel
     * @param array $items Array of items to add to the carousel<br /><br />
     * 		Example:<br />
     * 			Array (<br />
     * 				array(<br />
     * 					'img' => '<img src="path/to/src" />',<br />
     * 					'caption' => '',<br />
     * 					'active' => true|false,<br />
     * 					'controls' => true|false,<br />
     * 				)<br />
     * 			)<br />
     * @param array $attributes Array of attributes for the carousel
     * @return string
     */
    public static function carousel(array $items, array $attributes = array()) {
        self::$carousels++;
        $id = (isset($attributes['id'])) ? $attributes['id'] : 'myCarousel' . self::$carousels;
        $class = (isset($attributes['class'])) ? $attributes['class'] . ' carousel slide' : 'carousel slide';
        $attributes['id'] = $id;
        $attributes['class'] = $class;
        $controls = (isset($attributes['controls']) && !$attributes['controls']) ? false : true;
        unset($attributes['controls']);
        ob_start();
        ?>
        <div <?= self::parseAttributes($attributes) ?> >
            <!-- Carousel items -->
            <div class="carousel-inner">
                <?php foreach ($items as $t_id => $item): ?>
                    <?php if (isset($item['img'])): ?>
                        <div id="<?= $t_id ?>" class="item <?= (isset($item['active']) && $item['active']) ? 'active' : '' ?>">
                            <?= $item['img'] ?>
                            <?php if (isset($item['caption'])): ?>
                                <div class="carousel-caption">
                                    <?= $item['caption'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($controls): ?>
                <!-- Carousel nav -->
                <a class="carousel-control left" href="#<?= $id ?>" data-slide="prev">&lsaquo;</a>
                <a class="carousel-control right" href="#<?= $id ?>" data-slide="next">&rsaquo;</a>
            <?php endif; ?>
        </div>
        <script>
            $(function() {
                $('#<?= $id ?>').carousel();
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Creates an accordion
     * @todo Allow closing on attribute close. Currently, TWB closes it but no 
     * longer allows opening. So, this is to be implemented in the future
     * @param array $groups Array of groups to add to the carousel
     * 		Example:
     * 			Array (
     * 				array(
     * 					'heading' => 'Group Heading',
     * 					'body' => 'Group content to be shown|hidden',
     * 				)
     * 			)
     * @param array $attributes Array of attributes for the accordion. An optional 
     * attribute is 'close' which takes a boolean indicating whether the accordion
     * should be collapsed(true) or not (false)
     * @return string
     */
    public static function accordion(array $groups, array $attributes = array()) {
        self::$accordions++;
        $collapse = 1;
        ob_start();
        ?>
        <div data-close="<?= @$attributes['close'] ?>" class="accordion <?= @$attributes['class'] ?>" id="accordion<?= self::$accordions ?>" <?= self::parseAttributes($attributes, array('class', 'id', 'close')) ?>>
            <?php
            foreach ($groups as $key => $group) {
                if (empty($group['heading']) || empty($group['body'])) {
                    throw new Exception('Each accordion group must have a heading and a body');
                }
                ?>
                <div class="accordion-group">
                    <div class="accordion-heading">
                        <a class="accordion-toggle" data-toggle="collapse"
                           data-parent="#accordion<?= self::$accordions ?>" 
                           href="#group-<?= self::$accordions . $key ?>">
                               <?= $group['heading'] ?>
                        </a>
                    </div>
                    <div class="accordion-body collapse in" id="group-<?= self::$accordions . $key ?>">
                        <div class="accordion-inner">
                            <?= $group['body'] ?>
                        </div>
                    </div>
                </div>
                <?php
                $collapse++;
            }
            ?>
        </div>
        <script>
            $(function() {
                $('#accordion<?= self::$accordions ?>').collapse();
            })
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Groups given items into rows with class "row-fluid"
     * @param array $items Array of items to group
     * @param integer $itemsPerRow Number of items per row. This should be any of 1,2,3,4,6,12
     * @param array $options Array of additional options with the following possible keys:<br />
     * <b>wrap (boolean)</b>: True to wrap each item with div containers having
     * class depicting the right span size<br />
     * <b>row (array)</b>: Array of attributes for each row<b/>
     * <b>spans (array)</b>: Array of attributes for each span. This is only
     *  only applicable if option wrap is True.
     * @return string
     */
    public static function groupIntoRows(array $items, $itemsPerRow, array $options = array()) {
        ob_start();
        ?>
        <div class="row-fluid <?= @$options['row']['class'] ?>" 
             <?= self::parseAttributes(@$options['row'], array('class')) ?>>
                 <?php
                 foreach ($items as $key => $item) {
                     if ($key && $key % $itemsPerRow === 0) {
                         ?>
                </div>
                <div class="row-fluid <?= @$options['row']['class'] ?>">
                    <?php
                }

                if (@$options['wrap'] !== false):
                    ?>
                    <div class="span<?= round(12 / $itemsPerRow) ?> <?= @$options['spans']['class'] ?>"
                         <?= self::parseAttributes(@$options['spans'], array('class')) ?>>
                         <?php
                     endif;
                     echo $item;
                     if (@$options['wrap'] !== false):
                         ?>
                    </div>
                    <?php
                endif;
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

}