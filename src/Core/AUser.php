<?php

namespace dScribe\Core;

abstract class AUser extends AModel {

	/**
	 * Allows public access to properties
	 * @param string $name
	 * @return mixed
	 */
	final public function __get($name) {
		if (method_exists($this, 'get' . ucfirst($name))) return $this->{'get' . ucfirst($name)}();
		if (property_exists($this, $name)) return $this->$name;
	}

	/**
	 * Fetches the current user's role
	 * @return string
	 */
	abstract public function getRole();

	/**
	 * Fetches the current user's id
	 * @return mixed
	 */
	abstract public function getId();

	/**
	 * Checks if the role of the user tallies with the given
	 * @param string $role
	 * @return boolean
	 */
	final public function is($role) {
		return (strtolower($this->role) === strtolower($role));
	}

	/**
	 * Checks if properties exist and their values are as required
	 * @param array $options
	 * @return boolean
	 */
	final public function check(array $options) {
		foreach ($options as $property => $value) {
			if ((is_array($value) && !in_array($this->$property, $value)) ||
					(!is_array($value) && $this->$property !== $value)) return false;
		}

		return true;
	}

}
