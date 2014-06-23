<?php

namespace DScribe\Core;

use DScribe\Core\AService,
    DScribe\Core\IModel,
    DScribe\Core\Repository,
    Exception;

class AService extends AInjector {

    /**
     * Model to operate on
     * @var IModel
     */
    protected $model;

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
    final protected function construct() {
        $this->init();
        if ($this->repository === null)
            $this->initRepository();
    }

    /**
     * Sets the model to class
     * @param IModel $model
     */
    public function setModel(IModel $model, $initRepo = true) {
        $this->model = $model;
        if ($initRepo)
            $this->initRepository();
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
        if ($this->model === null ||
                ($this->model !== null && !in_array('DScribe\Core\IModel', class_implements($this->model))))
            return false;

        if ($this->repository !== null && $this->model->getTableName() === $this->repository->getTableName())
            return true;

        $repository = ($this->repositoryClass !== null) ? $this->repositoryClass : 'DScribe\Core\Repository';
        if (!in_array('DScribe\Core\IRepository', class_implements($repository)))
            throw new Exception('Repository class must implement "DScribe\Core\IRepository"');

        $this->repository = new $repository($this->model);
    }

    /**
     * Fetches the repository of the current model
     * @return mixed
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
     * prepares injection of classes
     * @return array
     */
    protected function prepareInject() {
        $model = ($this->model) ? $this->model : $this->getModule() . '\Models\\' . $this->getClassName();

        if (is_object($model))
            $model = get_class($model);

        if (!class_exists($model))
            return array_merge(parent::prepareInject(), $this->getConfigInject('services'));

        return array_merge(parent::prepareInject(), $this->getConfigInject('services'), array(
            'model' => array(
                'class' => $model
            ),
        ));
    }

    /**
     * Fetches the class name
     * @return string
     */
    public function getClassName() {
        return parent::className('Service');
    }

    /**
     * Returns an array of classes to inject
     * @return string
     */
    protected function inject() {
        return array();
    }

    /**
     * Commits all database transactions
     * @return boolean
     */
    protected function flush() {
        return Engine::getDB()->flush();
    }

    /**
     * Cancels all database transactions
     * @return boolean
     */
    protected function cancel() {
        return Engine::getDB()->cancel();
    }

}
