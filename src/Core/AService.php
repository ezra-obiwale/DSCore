<?php

namespace dScribe\Core;

use dbScribe\Repository,
	dScribe\Core\AService,
	dScribe\Core\IModel;

class AService extends ACore {

	/**
	 * Model to operate on
	 * @var IModel
	 */
	protected $model;

	/**
	 *
	 * @var \dScribe\Form\Form
	 */
	protected $form;

	/**
	 * Repository of models
	 * @var Repository
	 */
	protected $repository;

	/**
	 * Repository className to use instead of the default
	 * @var string
	 */
	protected $repositoryClass;

	/**
	 * class constructor
	 */
	final public function __construct() {
		if ($model = $this->getModule() . '\Models\\' . $this->getClassName())
				if (class_exists($model)) $this->setModel(new $model);
		$this->init();
		if ($this->repository === null) $this->initRepository();
	}

	/**
	 * Sets the model to class
	 * @param IModel $model
	 */
	public function setModel(IModel $model, $initRepo = true) {
		$this->model = $model;
		if ($initRepo) $this->initRepository();
		return $this;
	}

	/**
	 * Fetches the model of the service
	 * @return IModel
	 */
	public function getModel() {
		return $this->model;
	}

	/**
	 * Sets the repository class to use instead of the default
	 * @param string $repo
	 * @return AService
	 */
	public function setRepositoryClass($repo) {
		$this->repositoryClass = $repo;
		return $this;
	}

	/**
	 * Initializes the repository
	 * @return boolean
	 */
	protected function initRepository() {
		if ($this->model === null || ($this->model !== null && !in_array('dScribe\Core\IModel', class_implements($this->model))))
				return false;

		if ($this->repository !== null && $this->model->getTableName() === $this->repository->getTableName())
				return true;

		$repository = ($this->repositoryClass !== null) ? $this->repositoryClass : 'dbScribe\Repository';
		$this->repository = new $repository($this->model, engineGet('DB'), true);
		return true;
	}

	/**
	 * Fetches the repository of the current model
	 * @return Repository
	 */
	public function getRepository() {
		return $this->repository;
	}

	/**
	 * Replaces the constructor method for children classes
	 */
	protected function init() {
		
	}

	/**
	 * Fetches the class name
	 * @return string
	 */
	public function getClassName() {
		return parent::className('Service');
	}

	/**
	 * Commits all database transactions
	 * @return boolean
	 */
	public function flush() {
		return engineGet('db')->flush();
	}

	/**
	 * Cancels all database transactions
	 * @return boolean
	 */
	protected function cancel() {
		return engineGet('db')->cancel();
	}

	/**
	 * Creates the correct form name for the service
	 * @return string|null
	 */
	private function getDefaultFormName() {
		return (class_exists($this->getModule() . '\Forms\\' . $this->getClassName() . 'Form')) ?
				$this->getModule() . '\Forms\\' . $this->getClassName() . 'Form' : null;
	}

	/**
	 * Allows public access to form
	 * @return \DScibe\Form\Form
	 */
	public function getForm() {
		if (!$this->form) {
			if ($defaultFormName = $this->getDefaultFormName()) $this->form = new $defaultFormName;
		}

		return $this->form;
	}

}
