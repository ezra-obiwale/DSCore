<?php

/**
 * Class WordFigure
 *
 * @author Ezra Obiwale
 */
class WordFigure {

	/**
	 * Value to work on
	 * @var mixed
	 */
	protected $todo;

	/**
	 * Indicates whether to translate to word or figure
	 * @var boolean
	 */
	protected $toWords;

	/**
	 * Constructor class
	 * Sets todo and waits for further instruction
	 *
	 * @param string|int|float $todo The value to work on
	 */
	public function __construct($todo = null) {
		$this->todo = $todo;
	}

	/**
	 * Converts the figure todo value to words
	 * @param string|int|float $todo The value to work on
	 */
	public function toWords($todo = null) {
		$this->toWords = true;

		if ($todo !== null) $this->todo = $todo;

		if ($this->todo !== null) return $this->doQuadrillion();
	}

	/**
	 * Converts the word todo value to figure
	 * @param string|int|float $todo The value to work on
	 */
	public function toFigure($todo = null) {
		$this->toWords = false;

		if ($todo !== null) $this->todo = $todo;

		if ($this->todo !== null) return $this->doQuadrillion();
	}

	private function doQuadrillion($value = null) {
		$return = '';
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			$quadrillion = (int) ($value / 1000000000000000);

			if ($quadrillion > 0) {
				$return = $this->doHundred($quadrillion) . ' quadrillion';
			}


			$diff = $value - ($quadrillion * 1000000000000000);
			if ($diff > 0) {
				if ($quadrillion > 0)
						$return .= (($diff >= 100 && ($diff % 100) === 0) || $diff < 100) ? ' and ' : ', ';
				$return .= $this->doTrillion($diff);
			}
		} else {
			
		}

		return $return;
	}

	private function doTrillion($value = null) {
		$return = '';
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			$trillion = (int) ($value / 1000000000000);

			if ($trillion > 0) {
				$return = $this->doHundred($trillion) . ' trillion';
			}


			$diff = $value - ($trillion * 1000000000000);
			if ($diff > 0) {
				if ($trillion > 0)
						$return .= (($diff >= 100 && ($diff % 100) === 0) || $diff < 100) ? ' and ' : ', ';
				$return .= $this->doBillion($diff);
			}
		} else {
			
		}

		return $return;
	}

	private function doBillion($value = null) {
		$return = '';
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			$billion = (int) ($value / 1000000000);

			if ($billion > 0) {
				$return = $this->doHundred($billion) . ' billion';
			}


			$diff = $value - ($billion * 1000000000);
			if ($diff > 0) {
				if ($billion > 0)
						$return .= (($diff >= 100 && ($diff % 100) === 0) || $diff < 100) ? ' and ' : ', ';
				$return .= $this->doMillion($diff);
			}
		} else {
			
		}

		return $return;
	}

	private function doMillion($value = null) {
		$return = '';
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			$million = (int) ($value / 1000000);

			if ($million > 0) {
				$return = $this->doHundred($million) . ' million';
			}

			$diff = $value - ($million * 1000000);
			if ($diff > 0) {
				if ($million > 0)
						$return .= (($diff >= 100 && ($diff % 100) === 0) || $diff < 100) ? ' and ' : ', ';
				$return .= $this->doThousand($diff);
			}
		} else {
			
		}

		return $return;
	}

	private function doThousand($value = null) {
		$return = '';
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			$thousand = (int) ($value / 1000);

			if ($thousand > 0) {
				$return = $this->doHundred($thousand) . ' thousand';
			}

			$diff = $value - ($thousand * 1000);
			if ($diff > 0) {
				if ($thousand > 0)
						$return .= (($diff >= 100 && ($diff % 100) === 0) || $diff < 100) ? ' and ' : ', ';
				$return .= $this->doHundred($diff);
			}
		} else {
			
		}

		return $return;
	}

	private function doHundred($value = null) {
		$return = '';
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			$hundred = (int) ($value / 100);

			if ($hundred > 0) {
				$return = $this->doTen($hundred) . ' hundred';
			}

			$diff = $value - ($hundred * 100);
			if ($diff > 0) {
				if ($hundred > 0) $return .= ' and ';
				$return .= $this->doTen($diff);
			}
		} else {
			
		}

		return $return;
	}

	private function doTen($value = null) {
		$return = '';
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			if ($value === 10) return 'ten';
			elseif ($value === 11) return 'eleven';
			elseif ($value === 12) return 'twelve';

			$level = array(
				2 => 'twenty',
				3 => 'thirty',
				4 => 'forty',
				5 => 'fifty',
				6 => 'sixty',
				7 => 'seventy',
				8 => 'eighty',
				9 => 'ninety',
			);

			$irreg = array(
				3 => 'thir',
				5 => 'fif',
				8 => 'eigh'
			);

			$pref = (int) ($value / 10);
			$diff = $value - ($pref * 10);
			$unit = $this->doUnit($diff);

			if ($pref === 0) return $unit;

			if ($diff === 0 && $pref > 1) return $level[$pref];

			if ($pref < 2) {
				return (isset($irreg[$diff])) ? $irreg[$diff] . 'teen' : $unit . 'teen';
			}

			return $level[$pref] . '-' . strtolower($unit);
		}
		else {
			
		}

		return $return;
	}

	private function doUnit($value) {
		if ($value === null) $value = $this->todo;

		if ($this->toWords) {
			$words = array(
				0 => 'zero',
				1 => 'one',
				2 => 'two',
				3 => 'three',
				4 => 'four',
				5 => 'five',
				6 => 'six',
				7 => 'seven',
				8 => 'eight',
				9 => 'nine',
			);

			return $words[$value];
		}
	}

}
