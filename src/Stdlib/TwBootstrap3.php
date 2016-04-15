<?php

/**
 * Description of TwitterBootstrap
 *
 * @author topman
 */
class TwBootstrap3 {

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
	 *                          "tab1" => "content",<br />
	 *                          "tab2" => "content",<br />
	 *                          "tab3" => "content",<br />
	 *                      );
	 * @param array $options <br />
	 *   array( <br />
	 *       "active" => "tab2", <br />
	 *       "class" => "nav class", <br />
	 *       "tabClass" => "the class", <br />
	 *       "position" => "top|right|below|left", <br />
	 *       "style" => "tabs|pills", <br />
	 *       "fade" => TRUE}FALSE, <br />
	 *   ); <br />
	 * @return type
	 */
	public static function createTabs(array $contents, array $options = array()) {
		ob_start();
		?>
		<?php if ($options['position'] !== 'below'): ?>
			<ul class="nav nav-<?= $options['style'] ? $options['style'] : 'tabs' ?> <?= $options['class'] ?>">
				<?php
				$tabs = array_keys($contents);
				foreach ($tabs as $key => $tab) {
					?>
					<li class="<?= ($tab == @$options["active"]) ? "active" : "" ?> <?= @$options["tabClass"] ?>">
						<a role="tab" href="#tab-<?= preg_replace('/[^a-zA-z0-9]/', '-', $tab) ?>" data-toggle="tab"><?= $tab ?></a>
					</li>
					<?php
				}
				?>
			</ul>
		<?php endif; ?>
		<div class="tab-content">
			<?php
			$count = 1;
			foreach ($contents as $tab => $content) {
				?>
				<div class="tab-pane <?= $options['fade'] ? 'fade' : '' ?> <?=
				($tab == @$options["active"]) ? "in active" : ""
				?>" id="tab-<?=
					 preg_replace('/[^a-zA-z0-9]/', '-', $tab)
					 ?>">
						 <?= $content ?>
				</div>
				<?php
				$count++;
			}
			?>
		</div>
		<?php if ($options['position'] === 'below'): ?>
			<ul class="nav nav-<?= $options['style'] ? $options['style'] : 'tabs' ?> <?= $options['class'] ?>">
				<?php
				$tabs = array_keys($contents);
				foreach ($tabs as $key => $tab) {
					?>
					<li class="<?= ($tab == @$options["active"]) ? "active" : "" ?> <?= @$options["tabClass"] ?>">
						<a role="tab" href="#tab-<?= preg_replace('/[^a-zA-z0-9]/', '-', $tab) ?>" data-toggle="tab"><?= $tab ?></a>
					</li>
					<?php
				}
				?>
			</ul>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Creates an alert message
	 *
	 * @param string $type Ex. success|danger|error
	 * @param string $message The alert message
	 * @param string|boolean $class Class selectors to add to the alert container
	 * OR replaces $closable if boolean
	 * @param boolean $closable Indicates whether the alert should have the close button
	 * @return string
	 */
	public static function createAlert($type, $message, $class = null, $closable = true) {
		ob_start();
		?>
		<div class="alert alert-<?= $type . ' ' . $class ?> alert-dismissible" role="alert">
			<?php if (($class === true || !is_bool($class)) && $closable): ?>
				<button type="button" class="close" data-dismiss="alert">
					<span aria-hidden="true">&times;</span>
					<span class="sr-only">Close</span>
				</button>
			<?php endif; ?>
			<?= $message ?>
		</div>
		<?php
		return ob_get_clean();
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
	 * 		<b>noAnimation</b> (boolean)    -	Indicates whether to remove the animation or not<br />
	 * 		<b>small</b> (boolean)	-	Indicates whether to render a small modal or not<br />
	 * 		<b>large</b> (boolean)	-	Indicates whether to render a large modal or not<br />
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
		<a href="<?= isset($options['href']) ? $options['href'] : '#' ?>" data-target="#<?= $id ?>" role="button" <?=
		isset($options['linkAttrs']) ? self::parseAttributes($options['linkAttrs']) : ''
		?> data-toggle="modal"><?=
			   isset($options['linkLabel']) ? $options['linkLabel'] : 'Launch Modal'
			   ?></a>

		<?php
		self::$modalLinks[$id] = ob_get_clean();
		ob_start();
		if ($withLink) echo self::$modalLinks[$id];
		?>
		<!-- Modal -->
		<section id="<?= $id ?>" class="modal <?= (!$options['noAnimation'] ? 'fade' : '') ?> <?= $options['modalAttrs']['class'] ?> <?=
		isset($options['modalClass']) ? $options['modalClass'] : ''
		?>" <?=
		isset($options['modalAttrs']) ? self::parseAttributes($options['modalAttrs'], array('class')) : ''
		?> tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog <?= $options['small'] ? 'modal-sm' : '' ?> <?=
			$options['large'] ? 'modal-lg' : ''
			?>">
				<div class="modal-content">
					<?php
					if (isset($options['header']) && (!is_bool($options['header']) ||
							(is_bool($options['header'] && $options['header'])))):
						?>
						<header class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;<span class="sr-only">Close</span></button>
							<h4 id="myModalLabel" class="modal-title <?= $options['headerAttrs']['class'] ?>" <?=
							isset($options['headerAttrs']) ? self::parseAttributes($options['headerAttrs'], array('class')) : ''
							?>><?=
									isset($options['header']) ? $options['header'] : 'Modal Title'
									?></h4>
						</header>
					<?php endif; ?>
					<main class="modal-body <?=
					isset($options['contentClass']) ? $options['contentClass'] : ''
					?>">
							  <?=
							  isset($options['content']) ? $options['content'] : '<i icon="icon-refresh"></i> loading content ...'
							  ?>
					</main>
				</div>
				<?php
				if (isset($options['footer']) && !is_bool($options['footer']) ||
						(isset($options['footer']) && is_bool($options['footer']) &&
						$options['footer'])):
					?>
					<footer class="modal-footer" <?=
					isset($options['footerAttrs']) ? self::parseAttributes($options['footerAttrs']) : ''
					?>>
						<div class="row">
							<div class="col-md-12">
								<?php
								if (empty($options['footer']) || is_bool($options['footer'])):
									?>
									<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?=
										isset($options['closeButtonLabel']) ? $options['closeButtonLabel'] : 'Close'
										?></button>
									<?php
									if (!isset($options['noActionButton']) || (isset($options['noActionButton']) &&
											!$options['noActionButton'])):
										?>
										<button type="button" class="btn btn-primary">Save changes</button>
										<?php
									endif;
								else:
									echo $options['footer'];
								endif;
								?>
							</div>
						</div>
					</footer>
				<?php endif; ?>
			</div>
		</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieves the link to a modal
	 * @param string $modalId Id of the modal
	 * @return string|null
	 */
	public static function modalLink($modalId) {
		if (isset(self::$modalLinks[$modalId])) return self::$modalLinks[$modalId];
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
		<div class = "dropdown <?php echo $groupClass ?>">
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
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
			<?php
			foreach ($list as $label => $options):
				if (!isset($options['link'])) $options['link'] = '#';
				?>
				<?php
				if (!is_array($options) && strtolower($options) === 'divider'):
					?>
					<li role="presentation" class="divider"></li>
					<?php continue; ?>
				<?php endif; ?>
				<li role="presentation" <?=
				(isset($options['children'])) ? 'class="dropdown-submenu"' : ''
				?>>
					<a role="menuitem" tabindex="-1" <?=
					(isset($options['children'])) ? 'class="dropdown-toggle" data-toggle="dropdown"' : ''
					?> href="<?php echo @$options["link"] ?>" <?php
					   echo (!empty($options["attributes"])) ? self::parseAttributes($options["attributes"]) : ""
					   ?>><?php echo $label ?></a>
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
			if (in_array($attr, $ignore)) continue;
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
	 * 				)<br />
	 * 			)<br />
	 * @param array $attributes Array of attributes for the carousel<br /><br />
	 * 		Example:<br />
	 * 			Array (<br />
	 * 				array(<br />
	 * 					'controls' => true[|false],<br />
	 * 					'indicators' => true[|false],<br />
	 * 					'class' => '',<br />
	 * 				)<br />
	 * 			)<br />
	 * @return string
	 */
	public static function carousel(array $items, array $attributes = array()) {
		self::$carousels++;
		$id = (isset($attributes['id'])) ? $attributes['id'] : 'myCarousel' . self::$carousels;
		$attributes['indicators'] = isset($attributes['indicators']) ? $attributes['indicators'] : true;
		$class = (isset($attributes['class'])) ? $attributes['class'] . ' carousel slide' : 'carousel slide';
		$attributes['id'] = $id;
		$attributes['class'] = $class;
		$controls = (isset($attributes['controls']) && !$attributes['controls']) ? false : true;
		unset($attributes['controls']);
		ob_start();
		?>
		<div <?= self::parseAttributes($attributes) ?> data-ride="carousel" >
			<?php if ($attributes['indicators']): ?>
				<ol class="carousel-indicators">
					<?php
					for ($i = 0; $i < count($items); $i++):
						?>
						<li style="border:1px solid #000;background-color:#aaa;" data-target="#<?= $id ?>" data-slide-to="<?= $i ?>" class="<?=
						!$i ? 'active' : ''
						?>"></li>
						<?php endfor; ?>
				</ol>
			<?php endif; ?>
			<!-- Carousel items -->
			<div class="carousel-inner">
				<?php foreach ($items as $t_id => $item): ?>
					<?php if (isset($item['img'])): ?>
						<div id="<?= $t_id ?>" class="item <?=
						(isset($item['active']) && $item['active']) ? 'active' : ''
						?>">
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
		<?php
		return ob_get_clean();
	}

	/**
	 * Create panel
	 * @param array $sections Array of groups to add to the carousel
	 * 		Example:
	 * 			Array (
	 * 					'heading' => 'Panel Heading',
	 * 					'body' => 'Panel content to be shown|hidden',
	 *                  'attachment' => HTML String to attach to panel
	 *                  'footer' => 'Panel Footer'
	 * 			)
	 * @param array $attributes Array of attributes for the accordion. An optional
	 * attribute is 'close' which takes a boolean indicating whether the accordion
	 * should be collapsed(true) or not (false)
	 * @return string
	 */
	public static function panel(array $sections, array $attributes = array()) {
		ob_start();
		?>
		<div class="panel panel-<?=
		$attributes['style'] ? $attributes['style'] :
				'default'
		?> <?= $attributes['class'] ?>" <?=
		static::parseAttributes($attributes, array('class', 'style'))
		?>>
				 <?php
				 if ($sections['heading']):
					 ?>
				<div class="panel-heading"><?= $sections['heading'] ?></div>
				<?php
			endif;
			if ($sections['body']):
				?>
				<div class="panel-body"><?= $sections['body'] ?></div>
				<?php
			endif;
			if ($sections['attachment']) echo $sections['attachment'];
			if ($sections['footer']):
				?>
				<div class="panel-footer"><?= $sections['footer'] ?></div>
				<?php
			endif;
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Creates an accordion
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
		<div data-close="<?= @$attributes['close'] ?>" class="accordion <?= @$attributes['class'] ?>" id="accordion<?= self::$accordions ?>" <?=
		self::parseAttributes($attributes, array('class', 'id', 'close'))
		?>>
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
			$(document).ready(function () {
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
	 * <b>rows (array)</b>: Array of attributes for each row<br />
	 * <b>spans (array)</b>: Array of attributes for each span. This is only
	 *  only applicable if option wrap is True.
	 * @return string
	 */
	public static function groupIntoRows(array $items, $itemsPerRow, array $options = array()) {
		ob_start();
		?>
		<div class="row <?= @$options['rows']['class'] ?>"
		<?=
		self::parseAttributes(@$options['rows'], array('class'))
		?>>
				 <?php
				 foreach ($items as $key => $item) {
					 if ($key && $key % $itemsPerRow === 0) {
						 ?>
				</div>
				<div class="row <?= @$options['rows']['class'] ?>">
					<?php
				}

				if (@$options['wrap'] !== false):
					?>
					<div class="col-md-<?= round(12 / $itemsPerRow) ?> <?= @$options['spans']['class'] ?>"
					<?=
					self::parseAttributes(@$options['spans'], array('class'))
					?>>
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

	/**
	 * Creates a progress bar
	 * @param string $message Message to show in the bar
	 * @param array $options [(int) min, (int) max, (int) current, (bool) striped, (bool) active]
	 * @return string
	 */
	public static function progress($message = 'Loading. Please wait ...', array $options = array()) {
		ob_start();
		?>
		<div class="progress">
			<div role="progressbar" aria-valuenow="<?=
			$options['current'] ? $options['current'] : '100'
			?>" aria-valuemin="<?= $options['min'] ? $options['min'] : '0' ?>"
				 aria-valuemax="<?= $options['max'] ? $options['max'] : '100'
			?>" class="progress-bar <?=
				 ($options['striped']) ? 'progress-bar-striped' : ''
				 ?> progress-bar-<?= $options['style'] ?> <?=
				 ($options['active']) ? 'active' : ''
				 ?>" style="width:<?= $options['current'] ?>%"><?= $message ?></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Create a popover and it's link
	 *
	 * @param string $content Content of the popover
	 * @param string $label The label for the link that'll call the popover
	 * @param string $placement top|bottom|left|right
	 * @param string $title
	 * @param string $class
	 * @return string
	 */
	public static function popover($content, $label, $container = 'body', $placement = 'top',
								$title = null, $class = null) {
		ob_start();
		?>
		<a href="#" data-toggle="popover" class="<?= $class ?> popover-dismiss" data-container="<?= $container ?>"
		   rel="popover" data-placement="<?= $placement ?>" data-content="<?= $content ?>" <?=
		   $title ? ' data-original-title="' .
				   $title . '"' : ''
		   ?>><?= $label ?></a>
		   <?php
		   return ob_get_clean();
	   }

	   /**
		* Create media content
		* @param array $media [[image => str src|array attrs,heading,body,children],[]]
		* @return string
		*/
	   public static function media(array $media) {
		   ob_start();
		   foreach ($media as $medium):
			   ?>
			<div class="media">
				<a class="pull-left" href="#">
					<img class="media-object" <?=
					is_array($medium['image']) ?
							Util::parseAttrArray($medium['image']) :
							'src="' . $medium['image'] . '"'
					?> />
				</a>
				<div class="media-body">
					<h4 class="media-heading"><?= $medium['heading'] ?></h4>
					<?= $medium['body'] ?>
					<?=
					$medium['children'] ? static::media($medium['children']) : null
					?>
				</div>
			</div>
			<?php
		endforeach;
		return ob_get_clean();
	}

	/**
	 *
	 * @param int $rating
	 * @param bool|string $showNumber If TRUE, will show number with badge-pill-count
	 * If string, will use the string as the style of badge, ie. badge-{$showNumber}
	 * @param bool $useHearts Indicates whether to use hearts instead of stars
	 * @return string
	 */
	public static function ratings($rating, $showNumber = true, $useHearts = false) {
		ob_start();
		for ($i = 1; $i <= 5; $i++) {
			?>
			<i class="glyphicon glyphicon-<?= !$useHearts ? 'star' : 'heart' ?><?=
			($rating < $i) ? '-empty' : ''
			?>"></i>
			   <?php
		   }
		   if ($showNumber) {
			   ?>
			<span class="badge badge-<?= is_string($showNumber) ? $showNumber : 'pill-count' ?>">
				<?= $rating ?>
			</span>
			<?php
		}
		return ob_get_clean();
	}

	private static $activeRatings = 1;

	/**
	 * Create ratings that can be selected
	 * @param int $defaultRating
	 * @param bool $useHearts Indicates whether to use hearts instead of stars
	 * @return string
	 */
	public static function activeRatings($defaultRating = 3, $useHearts = false,
									  $elementName = 'rating') {
		$class = !$useHearts ? 'star' : 'heart';
		ob_start();
		for ($i = 1; $i <= 5; $i++) {
			?>
			<a style="color:inherit;text-decoration: none" href="#" data-value="<?= $i ?>"
			   class="active-ratings-<?= static::$activeRatings ?>">
				<i class="glyphicon glyphicon-<?= $class ?><?=
				($defaultRating < $i) ? '-empty' : ''
				?>">
				</i>
			</a>
			<?php
		}
		?>
		<input class="active-ratings-<?= static::$activeRatings ?>" type="hidden" name="<?= $elementName ?>" value="<?= $defaultRating ?>" />
		<script>
			$(document).ready(function () {
				$('a.active-ratings-<?= static::$activeRatings ?>').click(function (e) {
					e.preventDefault();
					$('input.active-ratings-<?= static::$activeRatings ?>').val($(this).data('value'));
					$clicked = $(this);
					$('a.active-ratings-<?= static::$activeRatings ?>').each(function (i, v) {
						if ($clicked.data('value') >= $(v).data('value'))
							$(v).children('i').removeClass('glyphicon-<?= $class ?>-empty').addClass('glyphicon-<?= $class ?>');
						else
							$(v).children('i').addClass('glyphicon-<?= $class ?>-empty').removeClass('glyphicon-<?= $class ?>');
					});
				});
			});
		</script>
		<?php
		static::$activeRatings++;
		return ob_get_clean();
	}

}
