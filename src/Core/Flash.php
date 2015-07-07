<?php

namespace DScribe\Core;

use Session;

class Flash {

    /**
     * Flash message
     * @var string
     */
    protected $messages;

    /**
     * Flash success message
     * @var string
     */
    protected $successMessages;

    /**
     * Flash error message
     * @var string
     */
    protected $errorMessages;

    /**
     * Class constructor
     */
    final public function __construct() {
        $flash = Session::fetch('Flash');
        if ($flash !== null) {
            $this->messages = $flash->getMessage(false);
            $this->successMessages = $flash->getSuccessMessage(false);
            $this->errorMessages = $flash->getErrorMessage(false);
        }
        else {
            $this->reset();
        }
    }

    /**
     * Sets the flash message
     * @param string $message
     * @return \DScribe\Core\Flash
     */
    final public function setMessage($message) {
        $this->messages = array($message);
        return $this;
    }

    /**
     * Fetches the flash message
     * @return string
     */
    final public function getMessage($parsed = true) {
        return ($parsed) ? $this->parseMessages($this->messages) :
                $this->messages;
    }

    /**
     * Adds a(n array of) message(s)
     * @param string|array $message
     * @return \DScribe\Core\Flash
     */
    final public function addMessage($message) {
        return $this->addMsg('messages', $message);
    }

    /**
     * Sets the flash success message
     * @param string $message
     * @return \DScribe\Core\Flash
     */
    final public function setSuccessMessage($message) {
        $this->successMessages = array($message);
        return $this;
    }

    /**
     * Fetches the flash success message
     * @return string
     */
    final public function getSuccessMessage($parsed = true) {
        return ($parsed) ? $this->parseMessages($this->successMessages) :
                $this->successMessages;
    }

    /**
     * Adds a(n array of) success message(s)
     * @param string|array $message
     * @return \DScribe\Core\Flash
     */
    final public function addSuccessMessage($message) {
        return $this->addMsg('successMessages', $message);
    }

    /**
     * Sets the flash error message
     * @param string $message
     * @return \DScribe\Core\Flash
     */
    final public function setErrorMessage($message) {
        $this->errorMessages = array($message);
        return $this;
    }

    /**
     * Fetches the flash error message
     * @return string
     */
    final public function getErrorMessage($parsed = true) {
        return ($parsed) ? $this->parseMessages($this->errorMessages) :
                $this->errorMessages;
    }

    /**
     * Adds a(n array of) error message(s)
     * @param string|array $message
     * @return \DScribe\Core\Flash
     */
    final public function addErrorMessage($message) {
        return $this->addMsg('errorMessages', $message);
    }

    /**
     * Checks if error message exists
     * @return boolean
     */
    final public function hasErrorMessage() {
        return !empty($this->errorMessages);
    }

    /**
     * Checks if success message exists
     * @return boolean
     */
    final public function hasSuccessMessage() {
        return !empty($this->successMessages);
    }

    /**
     * Checks if message exists
     * @return boolean
     */
    final public function hasMessage() {
        return !empty($this->messages);
    }

    /**
     * Resets the messages
     * @return \DScribe\Core\Flash
     */
    final public function reset() {
        $this->messages = $this->successMessages = $this->errorMessages = array();

        return $this;
    }

    /**
     * Clean up and save
     */
    final public function __destruct() {
        Session::save('Flash', $this);
    }

    public function parseMessages($messages) {
        return (count($messages) > 1) ?
                '<ul class="error-messages"><li>'
                . join('</li><li>', $messages) .
                '</li></ul>' :
                $messages[0];
    }

    private function addMsg($type, $message) {
        if (is_array($message)) {
            $this->$type = array_merge($this->$type, $message);
        }
        else {
            $this->{$type}[] = $message;
        }
        return $this;
    }

}
