<?php

namespace dScribe\Core;

class ControllerException extends Exception {

	/**
	 * thrown when a controller is not found
	 */
	public static function notFound($controller = null) {
		new self('The required controller [' . $controller . '] was not found');
	}

	/**
	 * thrown when the required action is not found
	 */
	public static function invalidAction($action) {
		new self('The required action [' . $action . '] was not found');
	}

	/**
	 * thrown when the number of passed parameters are inadequate
	 */
	public static function invalidParamCount() {
		new self('Invalid parameters passed into action');
	}

}
