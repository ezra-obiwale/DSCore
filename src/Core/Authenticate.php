<?php

namespace dScribe\Core;

class Authenticate {

	/**
	 * Current user identity object
	 * @var \dScribe\Core\UserIdentity
	 */
	protected $userId;

	/**
	 * Current controller object
	 * @var \dScribe\Core\AController
	 */
	protected $controller;

	/**
	 * Current action
	 * @var string
	 */
	protected $action;

	/**
	 * Class constructor
	 * @param \dScribe\Core\UserIdentity $userId
	 * @param \dScribe\Core\AController $controller
	 * @param string $action
	 */
	final public function __construct(UserIdentity $userId, AController $controller, $action) {
		$this->userId = $userId;
		$this->controller = $controller;
		$this->action = $action;
	}

	/**
	 * Checks each permission
	 * @param array $properties
	 * @return null|boolean
	 * @throws \Exception
	 */
	private function checkPermission(array $properties) {
		if (empty($properties))
			return true;

		if (isset($properties['actions']) && !is_array($properties['actions']))
			$properties['actions'] = array($properties['actions']);

		if (isset($properties['actions']) && !empty($properties['actions']) &&
			!in_array($this->action, $properties['actions']) &&
			!in_array(\Util::camelToHyphen($this->action), $properties['actions']))
			return null;

		foreach ($properties as $ppt => $values) {
			if (!is_array($values))
				$values = array($values);

			if (strtolower($ppt) === 'role' && in_array('@', $values) && !$this->userId->isGuest()) {
				return true;
			}

			if (strtolower($ppt) !== 'actions') {
				if ($this->userId->getUser()->$ppt === null)
					return false;

				if (empty($values))
					return true;

				if (!in_array($this->userId->getUser()->$ppt, $values))
					return false;
			}
		}

		return true;
	}

	/**
	 * Executes the authentication
	 * @return boolean
	 * @throws \Exception
	 */
	final public function execute() {
		if (!is_array($this->controller->accessRules()))
			throw new \Exception('Authentication failed: Method "accessRules" in "' .
				$this->controller->getClass() . '" must return an array');
		foreach ($this->controller->accessRules() as $rule) {
			if (!is_array($rule))
				throw new \Exception('Authentication error: Each element of the array returned by accessRules() must be an array');

			if (!isset($rule[0]))
				throw new \Exception('Authentication error: Each element of the array returned by accessRules() must indicate permission as its first element');

			$permission = $rule[0];
			$rules = (isset($rule[1])) ? $rule[1] : array();

			if (!is_string($permission) || (is_string($permission) && !in_array($permission, array('allow', 'deny'))))
				throw new \Exception('Authentication error: First element of each rules array can only be either "allow" or "deny"');
			if (isset($rules) && !is_array($rules))
				throw new \Exception('Authentication error: The second element of each rules array must return an array');

			if (strtolower($permission) === 'allow') {
				if ($this->checkPermission($rules) === TRUE)
					return true;
			}
			elseif (strtolower($permission) === 'deny') {
				if ($this->checkPermission($rules) === TRUE)
					return false;
			}
		}

		return true;
	}

}
