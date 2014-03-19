<?php

namespace DScribe\Annotation;

/**
 * Description of Annotation
 *
 * @author topman
 */
class Annotation {

    /**
     * Annotations found
     * @var array
     */
    protected $annotations;

    /**
     * Reflection Class object
     * @var \ReflectionClass
     */
    protected $reflector;

    public function __construct($class) {
        if (is_string($class) && !class_exists($class)) {
            throw new \Exception('Class "' . $class . '" does not exist');
        }
        else if (!is_string($class) && !is_object($class))
            throw new \Exception('Constructor expects param $class to be a class name string or an object. ' . gettype($class) . ' given.');

        $this->reflector = new \ReflectionClass($class);
    }

    /**
     * parses annotations for methods only
     */
    private function parseMethods($docType = '') {
        foreach ($this->reflector->getMethods() as $refMeth) {
            $this->annotations['methods'][$refMeth->name] = $this->parseDocComment($refMeth->getDocComment(), $docType);
        }
    }

    /**
     * parses annotations for properties only
     */
    private function parseProperties($docType = '') {
        foreach ($this->reflector->getProperties() as $refProp) {
            $this->annotations['properties'][$refProp->name] = $this->parseDocComment($refProp->getDocComment(), $docType);
        }
    }

    /**
     * Parses the given docComment
     * @param string $docComment The documentation to parse
     * @param string $docType The type of documentation to parse e.g. param, see, link
     * @return string|array
     */
    final protected function parseDocComment($docComment, $docType = '') {
        if (empty($docComment) || !is_string($docComment))
            return '';

        $annotations = array();
        $match = '#@' . stripslashes($docType) . '(.*?)\n#s';
        preg_match_all($match, $docComment, $annotations);
        return $annotations[1];
    }

    /**
     * Fetches the class annotations
     * @param string $docType The type of documentation to fetch e.g. param, see, link
     * @param boolean $forceNew Indicates whether to fetch fresh annotations and not cached ones
     * @return array
     */
    final public function getClass($docType = '', $forceNew = false) {
        if (!isset($this->annotations['class']) || $forceNew)
            $this->annotations['class'] = $this->parseDocComment($this->reflector->getDocComment(), $docType);

        return ($this->annotations['class']) ? $this->annotations['class'] : array();
    }

    /**
     * Checks whether class|properties|methods 's annotations have been retrieved
     * @param string $type
     * @param string $docType The type of documentation to fetch e.g. param, see, link
     * @param boolean $forceNew Indicates whether to fetch fresh annotations and not cached ones
     * @return boolean
     */
    private function checkExists($type, $docType, $forceNew) {
        if (!isset($this->annotations[strtolower($type)]) || $forceNew)
            $this->{'parse' . ucfirst($type)}($docType);
        return isset($this->annotations[strtolower($type)]);
    }

    /**
     * Fetches all annotations
     * @param string $docType The type of documentation to fetch e.g. param, see, link
     * @param boolean $forceNew Indicates whether to fetch fresh annotations and not cached ones
     * @return array
     */
    final public function getAll($docType = '', $forceNew = '') {
        $this->getClass($docType, $forceNew);
        $this->getProperties($docType, $forceNew);
        $this->getMethods($docType, $forceNew);

        return $this->annotations;
    }

    /**
     * Fetches all methods' annotations
     * @param string $docType The type of documentation to fetch e.g. param, see, link
     * @param boolean $forceNew Indicates whether to fetch fresh annotations and not cached ones
     * @return array
     */
    final public function getMethods($docType = '', $forceNew = false) {
        $this->checkExists('methods', $docType, $forceNew);
        return ($this->annotations['methods']) ? $this->annotations['methods'] : array();
    }

    /**
     * Fetches annotations for a particular method
     * @param string $method
     * @param string $docType The type of documentation to fetch e.g. param, see, link
     * @param boolean $forceNew Indicates whether to fetch fresh annotations and not cached ones
     * @return array
     * @throws \Exception
     */
    final public function getMethod($method, $docType = '', $forceNew = false) {
        if (!in_array($method, $this->annotations['methods']))
            throw new \Exception('Method "' . $method . '" not found');
        $this->checkExists('methods', $docType, $forceNew);
        return $this->annotations['methods'][$method];
    }

    /**
     * Fetches all properties' annotations
     * @param string $docType The type of documentation to fetch e.g. param, see, link
     * @param boolean $forceNew Indicates whether to fetch fresh annotations and not cached ones
     * @return array
     */
    final public function getProperties($docType = '', $forceNew = false) {
        $this->checkExists('properties', $docType, $forceNew);
        return ($this->annotations['properties']) ? $this->annotations['properties'] : array();
    }

    /**
     * Fetches annotations for a particular property
     * @param string $method
     * @param string $docType The type of documentation to fetch e.g. param, see, link
     * @param boolean $forceNew Indicates whether to fetch fresh annotations and not cached ones
     * @return array
     * @throws \Exception
     */
    final public function getProperty($property, $docType = '', $forceNew = false) {
        if (!in_array($property, $this->annotations['properties']))
            throw new \Exception('Property "' . $property . '" not found');
        $this->checkExists('properties', $docType, $forceNew);
        return ($this->annotations['properties'][$property]) ? $this->annotations['properties'][$property] : array();
    }

}
