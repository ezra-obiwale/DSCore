<?php

namespace dScribe\Core;

abstract class AInjector extends ACore {

	private static $injecting = array();

	/**
	 * Class constructor
	 * @param boolean $inject
	 */
	final public function __construct($inject = false) {
		parent::__construct();
		if ($inject) {
			$this->doInject();
		}
		$this->construct();
	}

	final private static function addInjecting($className) {
		if (in_array($className, self::$injecting)) {
			if (self::$injecting[count(self::$injecting) - 1] === $className) return true;

			self::$injecting[] = $className;
			return false;
		}

		self::$injecting[] = $className;
		return true;
	}

	private static function removeInjecting($className) {
		$array = array_flip(self::$injecting);
		unset($array[$className]);
		self::$injecting = array_flip($array);
	}

	/**
	 * Fetches classes to inject from the config file. If given type is not "all",
	 * the required is merged with the "all"
	 * @param string $type all, controllers|controller|c,services|service|s, views|view|v
	 * @return array
	 */
	protected function getConfigInject($type) {
		switch (strtolower($type)) {
			case 'all':
			case 'a':
				return engineGet('inject', 'all');
			case 'controllers':
			case 'controller':
			case 'c':
				return array_merge(engineGet('inject', 'all'), engineGet('inject', 'controllers'));
			case 'services':
			case 'service':
			case 's':
				return array_merge(engineGet('inject', 'all'), engineGet('inject', 'services'));
			case 'views':
			case 'view':
			case 'v':
				return array_merge(engineGet('inject', 'all'), engineGet('inject', 'views'));
		}
	}

	/**
	 * Replaces the class constructor for children classes
	 */
	protected function construct() {
		
	}

	/**
	 * Checks the injection for cyclic dependencies
	 * @param string $className
	 * @return boolean
	 */
	final public function checkInjections($className) {
		if (is_array($this->inject())) {
			foreach ($this->inject() as $classArray) {
				if (!is_array($classArray) ||
						(is_array($classArray) && !isset($classArray['class'])) ||
						(is_array($classArray) && !class_exists($classArray['class']))) continue;

				if ($classArray['class'] === $className) return false;

				if (!$this->canInject($classArray, $className)) return false;
			}
		}

		return true;
	}

	/**
	 * Prepares the inject classes
	 * @return array
	 * @throws \Exception
	 */
	protected function prepareInject() {
		if (!is_array($this->inject())) {
			throw new \Exception('Injection failed. Method "inject" must return an array');
		}
		return $this->inject();
	}

	/**
	 * Checks if the class can be injected
	 * @param array $classArray
	 * @param string|null $className
	 * @return boolean
	 */
	private function canInject(array $classArray, $className = null) {
		$className = ($className === null) ? $this->getClass() : $className;

		if (!self::addInjecting($className)) {
			return false;
		}

		$refClass = new \ReflectionClass($classArray['class']);

		if ($refClass->isSubclassOf('dScribe\Core\AInjector')) {
			$class = new $classArray['class']();

			if (!$class->checkInjections($className)) return false;
		}
		self::removeInjecting($className);

		return (isset($classArray['params']) && $refClass->getConstructor()) ?
				$refClass->newInstanceArgs($classArray['params']) :
				$refClass->newInstance();
	}

	/**
	 * Performs the actual injection
	 * @throws \Exception
	 */
	private function doInject() {
		foreach ($this->prepareInject() as $alias => $classArray) {
			if (!is_array($classArray)) {
				throw new \Exception('Injection failed. Values of injection array in class "' .
				$this->getClass() . '" must be an array');
			}

			if (!isset($classArray['class'])) {
				throw new \Exception('Injection failed. Class not specified for injection for alias "' .
				$alias . '" in class "' . $this->getClass() . '"');
			}

			if (!class_exists($classArray['class'])) {
				throw new \Exception('Injection failed. Class "' . $classArray['class'] . '" with alias "' .
				$alias . '" does not exist in class "' . $this->getClass() . '"');
			}

			if (isset($classArray['params']) && !is_array($classArray['params'])) {
				$classArray['params'] = array($classArray['params']);
			}

			if (FALSE === ($class = $this->canInject($classArray))) {
				throw new \Exception('Injection failed. Unending injection cycle detected for class "' .
				$classArray['class'] . '" with alias "' . $alias . '" in "' . $this->getClass() . '"' . $this->parseInjected());
			}

			$this->$alias = $class;
		}
	}

	private function parseInjected() {
		$return = '<pre style="background-color:rgb(245,245,245);border:1px solid #ccc;border-radius:5px;margin-top:5px">';
		foreach (self::$injecting as $cnt => $injected) {
			if ($cnt > 0) $return .= str_repeat('---', $cnt) . ' ';
			$return .= $injected;
			if ($cnt < count(self::$injecting) - 1) $return .= '<br />';
		}
		return $return . '</pre>';
	}

	/**
	 * returns an array of class to inject
	 */
	abstract protected function inject();

	public function __call($name, $args) {
		if (!method_exists($this, $name) && array_key_exists($name, $this->prepareInject())) {
			return call_user_func_array($this->$name, $args);
		}
	}

}
