<?php

namespace dScribe\View;

use dScribe\Core\AInjector,
	dScribe\Core\Flash,
	Exception,
	Object,
	Util;

class Renderer extends AInjector {

	/**
	 * The view object
	 * @var View
	 */
	protected $view;

	/**
	 * Path to public assests
	 * @var string
	 */
	protected $publicAssetsPath;
	protected $loadedAssets;

	protected function construct() {
		parent::construct();
		$this->loadedAssets = array(
			'css' => array(),
			'js' => array(),
			'icon' => array(),
			'misc' => array(),
			'update' => array(),
		);
	}

	/**
	 * Sets the view to render
	 * @param View $view
	 * @return Renderer
	 */
	final public function setView(View $view) {
		$this->view = $view;
		return $this;
	}

	/**
	 * Fetches the view object
	 * @return View
	 */
	final public function getView() {
		return $this->view;
	}

	/**
	 * Fetches the current module
	 * @param mixed $check Compare the module with this and return boolean if
	 * they are the same
	 * @return mixed
	 */
	protected function module($check = null) {
		$module = Util::camelToHyphen($this->view->getModule());
		return $check ? ($check == $module) : $module;
	}

	/**
	 * Fetches the current controller
	 * @param mixed $check Compare the controller with this and return boolean if
	 * they are the same
	 * @return mixed
	 */
	protected function controller($check = null) {
		$controller = $this->view->getController();
		$controller = $controller ? Util::camelToHyphen($controller->getClassName()) : null;
		return $check ? ($check == $controller) : $controller;
	}

	/**
	 * Fetches the current action
	 * @param mixed $check Compare the action with this and return boolean if
	 * they are the same
	 * @return mixed
	 */
	protected function action($check = null) {
		$action = Util::camelToHyphen($this->view->getAction());
		return $check ? ($check == $action) : $action;
	}

	/**
	 * Fetches the parameters set to the current action
	 * @param mixed $check Compare the parameters with this and return boolean.
	 * If array, return TRUE if all values are in parameters. If not array,
	 * return TRUE if exists in parameters
	 * @return mixed
	 */
	protected function params($check = null) {
		$params = $this->view->getParams();
		if ($check) {
			if (is_array($check)) {
				$inter = array_intersect($check, $params);
				return (count($inter) === count($check));
			}
			else {
				return in_array($check, $params);
			}
		}
		return $params;
	}

	protected function currentPath() {
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * Gets the information from the application configuration
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		return (strtolower($name) === 'view') ? $this->view : engineGet('config', 'app', $name, false);
	}

	/**
	 * Fetches the configuration object
	 * @return Object
	 */
	final protected function config() {
		return new Object(engineGet('config'));
	}

	/**
	 * Fetches the identity of the current user
	 * @return \dScribe\Core\UserIdentity
	 */
	final protected function userIdentity() {
		return engineGet('userIdentity');
	}

	/**
	 * Loads a layout
	 * @param string $layoutName Without the extension
	 * @param array $variables Array of variables to pass into the layout
	 * [name => value] with "dsLayout" as an exemption of name
	 * @param boolean $fromTheme Indicates whether to get theme layouts or
	 * search through modules
	 * @return string
	 */
	final public function loadLayout($layoutName, array $variables = array(), $fromTheme = false) {
		$dsLayout = $this->getLayoutPath($layoutName, $fromTheme);
		if (!$dsLayout) return '';

		foreach ($variables as $var => $value) {
			$$var = $value;
		}
		include $dsLayout;
	}

	/**
	 * Fetches the layout path
	 * @param string|null $layout
	 * @return boolean
	 * @throws Exception
	 */
	private function getLayoutPath($layout = null, $fromTheme = false) {
		$layout = ($layout === null) ? $this->view->getController()->getLayout() : $layout;
		if (!$layout) return false;

		if (!$fromTheme && is_readable(MODULES . $this->view->getModule() . DIRECTORY_SEPARATOR . 'View' .
						DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout .
						'.phtml'))
				return MODULES . $this->view->getModule() . DIRECTORY_SEPARATOR . 'View' .
					DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout .
					'.phtml';
		elseif (is_readable(THEMES . engineGet('config', 'defaults', 'theme') .
						DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.phtml'))
				return THEMES . engineGet('config', 'defaults', 'theme') . DIRECTORY_SEPARATOR .
					'layouts' . DIRECTORY_SEPARATOR . $layout . '.phtml';
		else {
			throw new Exception('Layout "' . $layout . '" not found both at the module and theme levels');
		}
	}

	/**
	 * Fetches the flash messenger instance
	 * @return Flash
	 */
	final public function flash() {
		return engineGet('flash');
	}

	/**
	 * Renders the action content to the browser
	 * @param string $content The error message if any
	 * @throws Exception
	 */
	final public function render($content = null) {
		if ($content === null) {
			foreach ($this->view->getVariables() as $var => $val) {
				$$var = $val;
			}

			$viewFile = $this->view->getViewFile();
			if (is_array($viewFile) && !is_readable(MODULES . $viewFile[0] . DIRECTORY_SEPARATOR . 'View' .
							DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR .
							$viewFile[1] . DIRECTORY_SEPARATOR . $viewFile[2] . '.phtml'))
					throw new Exception('View layout "' . join(DIRECTORY_SEPARATOR, $viewFile) . '" not found');
			else if (is_string($viewFile) && !is_readable($viewFile)) // loading from vendor
					throw new Exception('View layout "' . $viewFile . '" not found');

			// include action view
			ob_start();

			if (is_array($viewFile)) {
				include_once MODULES . $viewFile[0] . DIRECTORY_SEPARATOR . 'View' .
						DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR .
						$viewFile[1] . DIRECTORY_SEPARATOR . $viewFile[2] . '.phtml';
				}
			else if (is_string($viewFile)) // loading from vendor
				include_once $viewFile;

			$content = ob_get_clean();

			if (!$this->view->isPartial()) {
				// include controller layout
				$controllerLayout = self::getLayoutPath();
				if ($controllerLayout) {
					ob_start();
					include_once $controllerLayout;

					$content = ob_get_clean();
				}
				else if (engineGet('config', 'defaults', 'defaultLayout', false)) {
					ob_start();
					$this->loadLayout(engineGet('config', 'defaults', 'defaultLayout'), array('content' => $content));
					$content = ob_get_clean();
				}
				else {
					throw new Exception('No layout found.');
				}
			}
		}
		else {
			if (!$errorLayout = engineGet('config', 'modules', $this->view->getModule(), 'defaults', 'errorLayout', false)) {
				if (!$errorLayout = engineGet('config', 'defaults', 'errorLayout', false)) {
					throw new Exception('Error layout not found', true);
				}
			}

			if (is_array($errorLayout)) {
				if (!array_key_exists('guest', $errorLayout))
						throw new Exception('Error layout not found for "guest"', true);

				if (array_key_exists($this->userIdentity()->getUser()->getRole(), $errorLayout))
						$errorLayout = $errorLayout[$this->userIdentity()->getUser()->getRole()];
				else $errorLayout = $errorLayout['guest'];
			}

			ob_start();
			$this->loadLayout($errorLayout, array_merge(array('content' => $content), $this->view->getVariables()));
			$content = ob_get_clean();
		}
		echo($content);
	}

	/**
	 * Fetches the absolute path to a file
	 * @param string $file
	 * @param boolean $fromTheme Indicates whether to get file from theme assets and not modules' assets
	 * @return string
	 */
	private function parseFile($file, $fromTheme) {
		return engineGet('serverPath') . str_replace(array(ROOT, DIRECTORY_SEPARATOR), array('', '/'), $fromTheme ? THEMES . $file : MODULES . $file);
	}

	/**
	 * Fetches the path to file
	 * @param string $file
	 * @param boolean $fromTheme Indicates whether to get file from theme assets and not modules' assets
	 * @return string
	 */
	private function getFile($file, $fromTheme) {
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		$end = $fromTheme ?
				'assets' . DIRECTORY_SEPARATOR . $file :
				'View' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $file;
		if ($fromTheme)
				return $this->parseFile(engineGet('config', 'defaults', 'theme') . DIRECTORY_SEPARATOR
							. $end, $fromTheme);
		else
				return $this->parseFile($this->view->getModule() . DIRECTORY_SEPARATOR
							. $end, $fromTheme);
	}

	/**
	 * Loads a style sheet file
	 * @param string $css Filename without the extension with base as ./assets
	 * @param boolean $fromTheme Indicates whether to load the stylesheet from the theme or current module
	 * @param boolean $once Indicates whether to load the stylesheet only once
	 * @return string
	 */
	final public function loadCss($css, $fromTheme = false, $once = false) {
		if ($once && !$this->canLoadAsset($css, 'css')) return null;
		return '<link rel="stylesheet" type="text/css" href="' . $this->getFile($css . '.css', $fromTheme) . '" />' . "\n";
	}

	/**
	 * Loads a javascript file
	 * @param string $src Filename without the extension with base as ./assets
	 * @param boolean $fromTheme Indicates whether to load the javascript from the theme or current module
	 * @param boolean $once Indicates whether to load the stylesheet only once
	 * @return string|null
	 */
	final public function loadJs($src, $fromTheme = false, $once = false) {
		if ($once && !$this->canLoadAsset($src, 'js')) return null;
		return '<script type="text/javascript" src="' . $this->getFile($src . '.js', $fromTheme) . '"></script>' . "\n";
	}

	/**
	 * Loads the icon for the page
	 * @param string $src Filename with extension and base as ./assets in theme or module
	 * @param boolean $fromTheme Indicates whether to load the icon from the theme or current module
	 * @param boolean $once Indicates whether to load the stylesheet only once
	 * @return string
	 */
	final public function loadIcon($src, $fromTheme = false, $once = true) {
		if ($once && !$this->canLoadAsset($src, 'icon')) return null;
		return '<link rel="shortcut icon" href="' . $this->getFile($src, $fromTheme) . '"/>' . "\n";
	}

	/**
	 * Checks if the asset has not been loaded before
	 * @param string $file
	 * @param stirng $type css|js|icon|misc|update
	 * @return boolean
	 */
	private function canLoadAsset($file, $type) {
		$filename = basename($file);
		if (in_array($filename, $this->loadedAssets[$type])) return false;

		$this->loadedAssets[$type][] = $filename;
		return true;
	}

	/**
	 * Returns the link to an asset
	 * @param type $src Filename with extension and base as ./assets in theme or module
	 * @param string $fromTheme Indicates whether to load the icon from the theme or current module
	 * @return string
	 */
	final public function getAsset($src, $fromTheme = false) {
		return $this->getFile($src, $fromTheme);
	}

	/**
	 * Fetches the url path the a resource
	 * @param string|null $module
	 * @param string|null $controller
	 * @param string|null $action
	 * @param array $params
	 * @param string $hash
	 * @return string
	 */
	final public function url($module, $controller = null, $action = null, array $params = array(),
						   $hash = null) {
		return $this->view->url($module, $controller, $action, $params, $hash);
	}

	/**
	 * Fetches the absolute path to the current module's directory
	 * @return string
	 */
	final public function getModulePath() {
		return MODULES . ucfirst(Util::hyphenToCamel($this->module())) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Fetches the absolute path to the current module's View directory
	 * @return string
	 */
	final public function getModuleViewPath() {
		return $this->getModulePath() . 'View' . DIRECTORY_SEPARATOR;
	}

	final public function getClassName() {
		return 'Renderer';
	}

	/**
	 * Fetches the link of the home page
	 * @return string The link for the home page
	 */
	final public function home() {
		return $this->view->url();
	}

	/**
	 * Cleans up after rendering
	 */
	public function __destruct() {
		$this->flash()->reset();
	}

	final protected function inject() {
		return $this->getConfigInject('views');
	}

}
