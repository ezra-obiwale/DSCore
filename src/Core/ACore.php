<?php

namespace dScribe\Core;

abstract class ACore {

	/**
	 * Fetches the called class
	 * @return string
	 */
	final public function getClass() {
		return get_called_class();
	}

	/**
	 * Fetches the class name
	 * @param string $suffix
	 * @access protected
	 * @return string
	 */
	protected function className($suffix = '') {
		$exp = explode('\\', $this->getClass());

		return (!empty($suffix) &&
			substr($exp[count($exp) - 1], (strlen($exp[count($exp) - 1]) - strlen($suffix))) === $suffix) ?
			substr($exp[count($exp) - 1], 0, (strlen($exp[count($exp) - 1]) - strlen($suffix))) :
			$exp[count($exp) - 1];
	}

	/**
	 * Fetches the namespace of the current class
	 * @return string
	 */
	protected function getNamespace() {
		$np = explode('\\', $this->getClass());
		unset($np[count($np) - 1]);
		return join('\\', $np);
	}

	/**
	 * Fetches the module the current class belongs to according to namespace
	 * @return string
	 */
	protected function getModule() {
		$np = explode('\\', $this->getClass());
		return $np[0];
	}

	public function __toString() {
		return $this->getClass();
	}

	/**
	 * Fetches the class name
	 * @access public
	 */
	abstract public function getClassName();

}
