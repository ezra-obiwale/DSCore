<?php

namespace DScribe\Core;

use Session;

class Flash {

	/**
	 * Flash message
	 * @var string
	 */
	protected $message;

	/**
	 * Flash success message
	 * @var string
	 */
	protected $successMessage;

	/**
	 * Flash error message
	 * @var string
	 */
	protected $errorMessage;

	/**
	 * Class constructor
	 */
	final public function __construct() {
		$flash = Session::fetch('Flash');
		if ($flash !== null) {
			$this->message = $flash->getMessage();
			$this->successMessage = $flash->getSuccessMessage();
			$this->errorMessage = $flash->getErrorMessage();
		}
	}

	/**
	 * Sets the flash message
	 * @param string $message
	 * @return \DScribe\Core\Flash
	 */
	final public function setMessage($message) {
		$this->message = $message;
		return $this;
	}

	/**
	 * Fetches the flash message
	 * @return string
	 */
	final public function getMessage() {
		return $this->message;
	}

	/**
	 * Sets the flash success message
	 * @param string $message
	 * @return \DScribe\Core\Flash
	 */
	final public function setSuccessMessage($message) {
		$this->successMessage = $message;
		return $this;
	}

	/**
	 * Fetches the flash success message
	 * @return string
	 */
	final public function getSuccessMessage() {
		return $this->successMessage;
	}

	/**
	 * Sets the flash error message
	 * @param string $message
	 * @return \DScribe\Core\Flash
	 */
	final public function setErrorMessage($message) {
		$this->errorMessage = $message;
		return $this;
	}

	/**
	 * Fetches the flash error message
	 * @return string
	 */
	final public function getErrorMessage() {
		return $this->errorMessage;
	}

	/**
	 * Checks if error message exists
	 * @return boolean
	 */
	final public function hasErrorMessage() {
		return ($this->errorMessage !== null);
	}

	/**
	 * Checks if success message exists
	 * @return boolean
	 */
	final public function hasSuccessMessage() {
		return ($this->successMessage !== null);
	}

	/**
	 * Checks if message exists
	 * @return boolean
	 */
	final public function hasMessage() {
		return ($this->message !== null || $this->hasErrorMessage() || $this->HasSuccessMessage());
	}

	/**
	 * Resets the messages
	 * @return \DScribe\Core\Flash
	 */
	final public function reset() {
		$this->message =
			$this->successMessage =
			$this->errorMessage = null;

		return $this;
	}

	/**
	 * Clean up and save
	 */
	final public function __destruct() {
		Session::save('Flash', $this);
	}

}
