<?php
/*
 */

namespace dScribe\Form\Element;

use dScribe\Form\Element,
	Exception;

/**
 * Description of Element
 *
 * @author topman
 */
class Fieldset extends Element {

	private static $loadMultipleScript = true;

	public function __construct(array $data = array(), $preserveArray = false, $preserveKeyOnly = null) {
		if (!is_object($data['options']['value']) ||
				(is_object($data['options']['value']) && !is_a($data['options']['value'], 'dScribe\Form\Fieldset'))) {
			throw new Exception('Form element "' . $data['name'] .
			'" must have a value of type "dScribe\Form\Fieldset"');
		}
		parent::__construct($data, $preserveArray, $preserveKeyOnly);
	}

	public function setData($data) {
		parent::setData($data);
		if ((is_object($data) && is_a($data, 'Object')) || (!is_object($data) && is_array($data)))
				$this->options->value->setData($data);
	}

	/**
	 * Renders the super prepare useless
	 */
	public function prepare() {
		
	}

	public function render() {
		ob_start();
		?>
		<fieldset id="<?= $this->name ?>" style="margin-bottom:10px">
			<?php
			$fieldset = $this->options->value;
			if (isset($this->options->multiple)) $fieldset->isMultiple();
			$fieldset = $fieldset->setName($this->name)
					->render();
			?>
			<?php
			if ($this->options->label):
				$label = $this->options->label->text;
				if (!$label) $label = $this->options->label;
				?>
				<legend><?= $label ?> <?php $this->getMultipleButton($fieldset) ?></legend>
			<?php
			endif;

			if (!$this->options->label) $this->getMultipleButton($fieldset);
			echo $fieldset;
			?>
		</fieldset>
		<?php
		return ob_get_clean();
	}

	public function getMultipleButton($fieldset) {
		if (!isset($this->options->multiple)) return null;

		if (!isset($this->options->multiple->button))
				$this->options->multiple->add(array('button' => array()));

		if (!isset($this->options->multiple->button->attributes))
				$this->options->multiple->button->add(array('attributes' => array()));

		if (static::$loadMultipleScript) {
			?>
			<noscript>
			<span class="alert alert-error">Javascript is required. Please turn it on</span>
			</noscript>
			<script>
				var __fieldsets = {};
				function __toggleButton(fieldset) {
					btn = fieldset.querySelector('button.__multi');
					if (__fieldsets[fieldset.id].max > 0 && __fieldsets[fieldset.id].count >= __fieldsets[fieldset.id].max) {
						btn.style.display = 'none';
					} else {
						btn.style.display = 'inline';
					}
				}
				function __createFChildren(fieldset, values) {
					if (values) {
						for (key in values) {
							if (!values.hasOwnProperty(key)) {
								continue;
							}
							var m = document.createElement('div');
							var content = __fieldsets[fieldset.id].content;
							m.innerHTML = '<div class="extra" style="position:relative"><button tabIndex="-1" class="close" type="button" data-fieldset="' +
									fieldset.id + '" onclick="__deleteExtra(this)" style="display:block;margin:5px;color:red">x</button>' + content + ' </div>';
							values[key].forEach(function (v, i) {
								var field = m.querySelector('[name$="[' + v.name + '][]"]');
								if (field) {
									field.value = v.value;
									field.id = field.id + '_' + key;
									var label = m.querySelector('label[for="' + v.name + '"]');
									if (label) {
										label.setAttribute('for', field.id);
									}
								}
							});
							while (m.firstChild) {
								fieldset.appendChild(m.firstChild);
							}
							__fieldsets[fieldset.id].count++;
							__toggleButton(fieldset);
						}
					} else {
						var m = document.createElement('div');
						var content = __fieldsets[fieldset.id].content;
						m.innerHTML = '<div class="extra" style="position:relative"><button tabIndex="-1" class="close" type="button" data-fieldset="' +
								fieldset.id + '" onclick="__deleteExtra(this)" style="display:block;margin:5px;color:red">x</button>' + content + ' </div>';

						var field = m.querySelector('input:not([type="checkbox"]):not([type="radio"]):not([type="button"]):not([type="submit"]):not([type="reset"]),select,textarea');
						if (field)
							field.value = '';
						while (m.firstChild) {
							fieldset.appendChild(m.firstChild);
						}
						__fieldsets[fieldset.id].count++;
						__toggleButton(fieldset);
					}
				}
				function __deleteExtra(btn) {
					btn.parentNode.parentNode.removeChild(btn.parentNode);
					var fieldset = document.querySelector('fieldset#' + btn.getAttribute('data-fieldset'));
					__fieldsets[fieldset.id].count--;
					__toggleButton(fieldset);
				}
				document.addEventListener('DOMContentLoaded', function () {
					for (var key in __fieldsets) {
						if (__fieldsets[key].extras) {
							var extras = __fieldsets[key].extras;
							var values = {};
							for (var ky in extras) {
								if (!extras.hasOwnProperty(ky)) {
									continue;
								}
								if (typeof extras[ky] === 'object') {
									extras[ky].forEach(function (v, j) {
										if (j) {
											if (!values[j])
												values[j] = new Array;

											values[j].push({name: ky, value: v});
										}
									});
								}
							}
							__createFChildren(document.getElementById(key), values);
						}
					}
					var btns = document.querySelectorAll('button.__multi');
					for (i = 0; i < btns.length; i++) {
						btns[i].addEventListener('click', function (e) {
							var fieldset = document.querySelector('fieldset#' + this.getAttribute('data-target'));
							__createFChildren(fieldset);
						});
					}
				});
			</script>
			<?php
		}
		?>
		<button type="button" id="__btn<?= ucfirst($this->name) ?>"
				data-target="<?= $this->name ?>"
				class="__multi <?= $this->options->multiple->button->attributes->class ?>"
				<?=
				$this->parseAttributes($this->options->multiple
						->button->attributes->toArray(), array('class'));
				?>>
					<?=
					$this->options->multiple->button->label ?
							$this->options->multiple->button->label : 'more'
					?>
		</button>
		<script>
			__fieldsets['<?= $this->name ?>'] = {
				content: '<?= trim(str_replace(array("\n", "\r", "\t", "  "), '', $fieldset)) ?>',
				max: parseInt('<?= $this->options->multiple->max ? $this->options->multiple->max : -1 ?>'),
				count: 1,
				extras: JSON.parse('<?= json_encode($this->data) ?>')
			};
		</script>
		<?php
		static::$loadMultipleScript = false;
	}

	public function validate() {
		return $this->options->value->isValid();
	}

	public function reset() {
		parent::reset();
		$this->options->value->reset();
		return $this;
	}

}
