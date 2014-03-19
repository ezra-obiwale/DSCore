<?php

namespace DScribe\DB;

use DBScribe\ArrayCollection,
    DBScribe\Row,
    DBScribe\Table,
    DBScribe\Util,
    DScribe\Annotation\Annotation,
    DScribe\Core\Engine,
    DScribe\Core\IModel,
    Exception,
    Object,
    Session;

/**
 * Description of Mapper
 *
 * @author topman
 */
abstract class Mapper extends Row implements IModel {

    private $settings;

    /**
     *
     * @var \DScribe\Annotation\Annotation
     */
    private $annotations;

    /**
     * Initialize the row
     * @param Table $table Live table connection
     */
    public function init(Table &$table) {
        if ($this->ignore()) {
            return true;
        }
        $this->_table = & $table;
        $className = str_replace('\\', '.', get_called_class());
        $path = DATA . 'mapper' . DIRECTORY_SEPARATOR . $className;
        $save = false;
        $this->getAnnotations();

        if (Session::fetch('mapperSave') && !is_readable($path)) {
            $this->save($path, $this->getAnnotations());
            Session::remove('mapperSave');
        }

        if (!$this->tableExists($table)) {
            $save = $this->createTable($table);
        }
        else {
            $this->checkModelRequirements($path);
            $save = !$this->isUpToDate($path, $table);
        }

        if ($save || !is_readable($path))
            $this->save($path, $this->getAnnotations());
    }

    private function checkModelRequirements($path) {
        $reload = false;
        $classAnnots = $this->annotations->getClass();

        if (!empty($classAnnots[1]) && is_array($classAnnots[1])) {
            foreach ($classAnnots[1] as $desc) {
                if (strtolower(substr($desc, 0, 4)) !== 'dbs\\')
                    continue;
                $this->performClassAnnots($desc);
                $reload = true;
            }
        }

        if ($reload) {
            Session::save('mapperSave', true);
            Engine::reloadPage();
        }
    }

    private function performClassAnnots($desc) {
        $setting = $this->parseSettings($desc);
        if (method_exists($this, $setting['type'])) {
            if (!$this->{$setting['type']}($setting['attrs']))
                throw new \Exception('DBScribe Class Upgrade "' . $setting['type'] . '" failed');
        }
    }

    /**
     * Auto generates the class from an existing table in the database
     * @param array $attrs
     * @return boolean
     */
    protected function Autogen(array $attrs) {
        $ignore = !empty($attrs['ignore']) ? explode('|', $attrs['ignore']) : array();
        ob_start();
        $classPath = $this->openClassForWriting();
        $methods = '';
        foreach ($this->_table->getColumns() as $name => $attrsRow) {
            if (in_array(Util::camelTo_($name), $ignore))
                continue;
            echo "\r\n";
            echo "\t/**\r\n";
            echo "\t * @DBS\\" . $this->fetchColumnProperties($attrsRow) . "\r\n";
            echo "\t */\r\n";
            echo "\tprotected $" . Util::_toCamel($name) . ";\r\n";

            $methods .= "\r\n";
            $methods .= "\tpublic function set" . ucfirst(Util::_toCamel($name)) . '($' . Util::_toCamel($name) . ") {\r\n";
            $methods .= "\t\t" . '$this->' . Util::_toCamel($name) . ' = $' . Util::_toCamel($name) . ";\r\n";
            $methods .= "\t\t" . 'return $this;' . "\r\n";
            $methods .= "\t}\r\n";

            $methods .= "\r\n";
            $methods .= "\tpublic function get" . ucfirst(Util::_toCamel($name)) . "() {\r\n";
            $methods .= "\t\t" . 'return $this->' . Util::_toCamel($name) . ";\r\n";
            $methods .= "\t}\r\n";
        }
        echo $methods . "\r\n";
        echo "}\r\n";
        return file_put_contents($classPath, ob_get_clean());
    }

    private function fetchColumnProperties($attrsRow) {
        $references = $this->_table->getReferences();
        if (array_key_exists($attrsRow['colName'], $references)) {
            $ref = $references[$attrsRow['colName']];
            $modelTable = $this->getModelTable($attrsRow['colName']);
            return 'Reference (model="' . @$modelTable[0] .
                    '", property="' . Util::_toCamel($ref['refColumn']) .
                    '", onUpdate="' . $ref->onUpdate .
                    '", onDelete="' . $ref->onDelete . '", nullable=' .
                    (($attrsRow['nullable'] === 'YES') ? 'true' : 'false') . ')';
        }
        else {
            $exp = explode('(', $attrsRow['colType']);
            if ($exp[0] === 'varchar') {
                $type = 'String';
            }
            else {
                $type = $exp[0];
            }

            $size = isset($exp[1]) ? 'size=' . (int) $exp[1] . ', ' : null;
            return ucfirst($type) . ' (' . $size . $this->fetchColumnAttrs($attrsRow);
        }
    }

    private function fetchColumnAttrs($row) {
        $return = 'nullable=' . (($row['nullable'] === 'YES') ? 'true' : 'false');
        if (!empty($row['colDefault'])) {
            $return .= ', default="' . $row['colDefault'] . '"';
        }
        if ($row['colKey'] === 'PRI') {
            $return .= ', primary=true';
        }
        return $return . ')';
    }

    private function openClassForWriting() {
        $classPath = MODULES . str_replace('\\', DIRECTORY_SEPARATOR, get_called_class()) . '.php';
        foreach (file($classPath) as $line) {
            if (trim($line) === '}' || stristr($line, 'DBS\Autogen'))
                continue;
            echo $line;
        }
        return $classPath;
    }

    private function ignore() {
        if (in_array($this->getTableName(), $this->getIgnore())) {
            return true;
        }
    }

    /**
     * Checks if the table exists
     * @param Table $table
     * @return boolean
     */
    private function tableExists(Table &$table) {
        return (count($table->getColumns()) > 0);
    }

    /**
     * Creates table in the database from the model's annotations
     * @param Table $table
     * @return mixed
     */
    private function createTable(Table &$table) {
        $annotations = $this->getAnnotations();
        $create = false;
        foreach ($annotations as $columnName => $descArray) {
            $create = true;
            $dbColumnName = Util::camelTo_($columnName);

            if (isset($descArray['attrs']['primary']) && $descArray['attrs']['primary']) {
                $table->setPrimaryKey($dbColumnName);
            }

            if (isset($descArray['attrs']['reference']) && $descArray['type'] !== 'ReferenceMany') {
                $onDelete = (isset($descArray['attrs']['reference']['onDelete'])) ?
                        $descArray['attrs']['reference']['onDelete'] : 'RESTRICT';
                $onUpdate = (isset($descArray['attrs']['reference']['onUpdate'])) ?
                        $descArray['attrs']['reference']['onUpdate'] : 'RESTRICT';
                $table->addReference($dbColumnName, $descArray['attrs']['reference']['table'], $descArray['attrs']['reference']['column'], $onDelete, $onUpdate);

                unset($descArray['attrs']['reference']['table']);
                unset($descArray['attrs']['reference']['column']);
                unset($descArray['attrs']['reference']['model']);

                if (isset($descArray['attrs']['reference']['onUpdate']))
                    unset($descArray['attrs']['reference']['onUpdate']);
                if (isset($descArray['attrs']['reference']['onDelete']))
                    unset($descArray['attrs']['reference']['onDelete']);

                if (!empty($descArray['attrs']['reference'])) {
                    $descArray['attrs'] = array_merge($descArray['attrs'], $descArray['attrs']['reference']);
                    unset($descArray['attrs']['reference']);
                }
            }

            $table->addColumn($dbColumnName, $this->parseAttributes($columnName, $descArray));
            $this->checkIndexes($dbColumnName, $descArray, $table);
        }

        return ($create) ? $this->getConnection()->createTable($table, false) : false;
    }

    /**
     * Prepares attributes of columns for use
     * @param string $columnName
     * @param array $attrs
     * @todo Parse more attributes e.g. unique, index, ... Check what connection->create() can do
     * @return string
     */
    private function parseAttributes($columnName, array $attrs) {
        $return = $this->checkType($columnName, $attrs); // type
        $attr = new Object($attrs['attrs']);

        if (isset($attr->size))
            $return .= '(' . $attr->size . ')'; // size

        $return .= (isset($attr->nullable) && strtolower($attr->nullable) == 'true') ? ' NULL' : ' NOT NULL'; // null

        if (isset($attr->default)) { // auto increment
            if (strtolower($attrs['type']) === 'boolean') {
                if ($attr->default === 'true') {
                    $attr->default = 1;
                }
                else {
                    $attr->default = 0;
                }
            }

            $return .= (trim(strtolower($attrs['type'])) == 'timestamp') ? ' DEFAULT ' . $attr->default : ' DEFAULT "' . $attr->default . '"';
        }
        if (isset($attr->autoIncrement) && $attr->autoIncrement) // auto increment
            $return .= ' AUTO_INCREMENT';

        if (isset($attr->onUpdate)) // auto increment
            $return .= ' ON UPDATE ' . $attr->onUpdate;

        return $return;
    }

    /**
     * Checks the column type to ensure it has required settings
     * @param string $columnName
     * @param array $attrs
     * @return string
     * @throws Exception
     */
    private function checkType($columnName, array $attrs) {
        if (in_array(strtolower($attrs['type']), array('string', 'referencemany'))) {
            return (isset($attrs['attrs']['size'])) ? 'VARCHAR' : 'TEXT';
        }
        return strtoupper($attrs['type']);
    }

    /**
     * Checks if the table is uptodate with the mapper settings
     * @param string $path Path to save the schema to
     * @param Table $table
     * @return boolean
     */
    private function isUpToDate($path, Table &$table) {
        $return = true;
        // update if any parent is changed
        foreach (class_parents(get_called_class()) as $parent) {
            if ($parent === 'DScribe\Core\AModel')
                break;

            $parentPath = (is_readable(MODULES . str_replace('\\', '/', $parent) . '.php')) ?
                    MODULES . str_replace('\\', '/', $parent) . '.php' :
                    VENDOR . str_replace('\\', '/', $parent) . '.php';

            if (!is_readable($path) ||
                    (is_readable($path) && filemtime($path) < filemtime($parentPath))) {
                $return = $this->prepareUpdate($path, $table);
                break;
            }
        }

        if (!is_readable($path) ||
                (is_readable($path) &&
                @filemtime($path) < @filemtime(MODULES . str_replace('\\', '/', get_called_class()) . '.php'))) {
            $return = $this->prepareUpdate($path, $table);
        }

        $this->updateReferences($table);

        return $return;
    }

    /**
     * Updates tables that reference current table
     * @param Table $table
     */
    private function updateReferences(Table &$table) {
        foreach ($table->getBackReferences() as $array) {
            foreach ($array as $ref) {
                if (in_array($ref['refTable'], $this->getIgnore()))
                    continue;

                $refTable = new Table($ref['refTable'], $this->getConnection());
                $modelArray = self::getModelTable($ref['refTable']);
                if (!empty($modelArray)) {
                    $mapper = new $modelArray[0];

                    $mapper->setConnection($this->getConnection());
                    $mapper->init($refTable, $this->getIgnore());
                }
            }
        }
    }

    /**
     * Prepares to update the table with the settings in the mapper class
     * @param string $path Pathh to save the schema to
     * @param Table $table
     * @return void
     */
    private function prepareUpdate($path, Table &$table) {
        $oldDesc = (is_readable($path)) ? include($path) : array();
        $newDesc = $this->getAnnotations();

        $toDo = array('new' => array(), 'update' => array(), 'remove' => array());
        foreach ($newDesc as $property => $annotArray) {
            $_property = Util::camelTo_($property);
            if (!isset($oldDesc[$property])) {
                $toDo['new'][$_property] = $annotArray;
                unset($oldDesc[$property]);
            }
            else if (count($annotArray) != count($oldDesc[$property])) {
                $toDo['update'][$_property] = $annotArray;
                unset($oldDesc[$property]);
            }
            elseif (count($annotArray) === count($oldDesc[$property])) {
                foreach ($annotArray as $attr => $value) {
                    if (!isset($oldDesc[$property][$attr]) ||
                            (isset($oldDesc[$property][$attr]) && $value !== $oldDesc[$property][$attr])) {
                        $toDo['update'][$_property] = $annotArray;
                        unset($oldDesc[$property]);
                        break;
                    }
                }
                unset($oldDesc[$property]);
            }
        }

        $toDo['remove'] = array_map(function($ppt) {
            return Util::camelTo_($ppt);
        }, array_keys($oldDesc));
        return $this->updateTable($toDo, $table);
    }

    /**
     * Updates the table
     * @param array $columns
     * @param Table $table
     * @return mixed
     */
    private function updateTable(array $columns, Table &$table) {
        $canUpdate = false;
        foreach ($columns['remove'] as $columnName) {
            $canUpdate = true;
            $table->dropColumn(Util::camelTo_($columnName));
        }

        foreach ($columns['update'] as $columnName => &$desc) {
            $canUpdate = true;
            $dbColumnName = Util::camelTo_($columnName);

            $this->checkIndexes($dbColumnName, $desc, $table);

            if (isset($desc['attrs']['reference'])) {
                $onDelete = (isset($desc['attrs']['reference']['onDelete'])) ?
                        $desc['attrs']['reference']['onDelete'] : 'RESTRICT';
                $onUpdate = (isset($desc['attrs']['reference']['onUpdate'])) ?
                        $desc['attrs']['reference']['onUpdate'] : 'RESTRICT';
                $table->alterReference($dbColumnName, $desc['attrs']['reference']['table'], $desc['attrs']['reference']['column'], $onDelete, $onUpdate);
                unset($desc['attrs']['reference']['table']);
                unset($desc['attrs']['reference']['column']);
                unset($desc['attrs']['reference']['model']);

                if (isset($desc['attrs']['reference']['onUpdate']))
                    unset($desc['attrs']['reference']['onUpdate']);
                if (isset($desc['attrs']['reference']['onDelete']))
                    unset($desc['attrs']['reference']['onDelete']);

                if (!empty($desc['attrs']['reference'])) {
                    $desc['attrs'] = array_merge($desc['attrs'], $desc['attrs']['reference']);
                    unset($desc['attrs']['reference']);
                }
            }

            $table->alterColumn($dbColumnName, $this->parseAttributes($columnName, $desc));

            if (isset($desc['attrs']['primary']) && $desc['attrs']['primary'])
                $table->setPrimaryKey($dbColumnName);
        }

        foreach ($columns['new'] as $columnName => $desc) {
            $canUpdate = true;
            $dbColumnName = Util::camelTo_($columnName);

            if (isset($desc['attrs']['reference'])) {
                $onDelete = (isset($desc['attrs']['reference']['onDelete'])) ?
                        $desc['attrs']['reference']['onDelete'] : 'RESTRICT';
                $onUpdate = (isset($desc['attrs']['reference']['onUpdate'])) ?
                        $desc['attrs']['reference']['onUpdate'] : 'RESTRICT';
                $table->addReference($dbColumnName, $desc['attrs']['reference']['table'], $desc['attrs']['reference']['column'], $onDelete, $onUpdate);
                unset($desc['attrs']['reference']['table']);
                unset($desc['attrs']['reference']['column']);
                unset($desc['attrs']['reference']['model']);

                if (isset($desc['attrs']['reference']['onUpdate']))
                    unset($desc['attrs']['reference']['onUpdate']);
                if (isset($desc['attrs']['reference']['onDelete']))
                    unset($desc['attrs']['reference']['onDelete']);

                if (!empty($desc['attrs']['reference'])) {
                    $desc['attrs'] = array_merge($desc['attrs'], $desc['attrs']['reference']);
                    unset($desc['attrs']['reference']);
                }
            }

            $table->addColumn($dbColumnName, $this->parseAttributes($columnName, $desc));

            $this->checkIndexes($dbColumnName, $desc, $table);

            if (isset($desc['attrs']['primary']) && $desc['attrs']['primary'])
                $table->setPrimaryKey($dbColumnName);
        }

        if ($canUpdate) {
            if ($this->getConnection()->alterTable($table)) {
                $this->getConnection()->flush();
                return false;
            }
        }

        return true;
    }

    private function checkIndexes($dbColumnName, $desc, Table &$table) {
        if (in_array($dbColumnName, $table->getIndexes()) && $table->getPrimaryKey() !== $dbColumnName) {
            $table->dropIndex($dbColumnName);
        }

        if ($table->getPrimaryKey() !== $dbColumnName) {
            if (isset($desc['attrs']['index'])) {
                $table->addIndex($dbColumnName, Table::INDEX_REGULAR);
            }
            else if (isset($desc['attrs']['unique'])) {
                $table->addIndex($dbColumnName, Table::INDEX_UNIQUE);
            }
            else if (isset($desc['attrs']['fulltext'])) {
                $table->addIndex($dbColumnName, Table::INDEX_FULLTEXT);
            }
            else {
                return null;
            }

            $ref = $table->getReferences();
            if (array_key_exists($dbColumnName, $ref)) {
                $table->addReference($ref[$dbColumnName]['columnName'], $ref[$dbColumnName]['refTable'], $ref[$dbColumnName]['refColumn'], $ref[$dbColumnName]['onDelete'], $ref[$dbColumnName]['onUpdate']);
            }
        }
    }

    /**
     * Replaces the magic method __call() for mapper classes
     * @param string $name
     * @param array $arguments
     */
    protected function _call(&$name, array &$args) {
        if (!method_exists($this, $name)) {
            $modelTable = self::getModelTable(Util::camelTo_($name));
            if (is_array($modelTable) && !empty($modelTable)) {
                if (!$this->getConnection())
                    $this->setConnection(Engine::getDB());
                $relTable = $this->getConnection()->table(Util::camelTo_($name));
                $model = new $modelTable[0];
                $model->setConnection($this->getConnection());

                $model->init($relTable);
                $args['model'] = $model;
            }
            $settings = $this->getSettings();
            if (@$settings[$name]['type'] === 'ReferenceMany') {
                $args['relateWhere'] = array();
                $nam = (!is_array($this->$name)) ? explode('__:DS:__', $this->$name) : $this->$name;
                foreach ($nam as $val) {
                    $args['relateWhere'][] = array(
                        $this->settings[$name]['attrs']['column'] => $val,
                    );
                }
            }
        }
    }

    /**
     * Parses the annotations to bring out the required
     * @param array $annotations
     * @return array
     */
    private function parseAnnotations(array $annotations, $createReference = true) {
        $return = $defer = $primary = array();
        foreach ($annotations as $property => $annotArray) {
            if (!is_array($annotArray))
                continue;

            foreach ($annotArray as &$desc) {
                $desc = $this->parseSettings(substr($desc, 1));

                if ((strtolower($desc['type']) === 'reference' || strtolower($desc['type']) === 'referencemany') && $createReference) {
                    $defer[$property] = $desc;
                    continue;
                }

                if (isset($desc['attrs']['primary']) && $desc['attrs']['primary']) {
                    $primary['column'] = $property;
                    $primary['desc'] = $desc;
                }

                $return[$property] = $desc;
                break;
            }
        }

        if ($createReference) {
            foreach ($defer as $property => $desc) {
                $desc = $this->createReference($property, $desc, $primary);

                if (strtolower($desc['type']) === 'referencemany') {
                    $desc['attrs'] = array_merge($desc['attrs'], $desc['attrs']['reference']);

                    unset($desc['attrs']['size']);
                    unset($desc['attrs']['reference']);
                    $desc['type'] = 'ReferenceMany';
                }

                $return[$property] = $desc;
            }
        }

        return $return;
    }

    /**
     * Parses the settings for all columns
     * @param string $annot
     * @return array
     */
    private function parseSettings($annot) {
        $annot = str_ireplace(array(' (', ', ', ', ', ')', '"', "'"), array('(', ', ', ', '), $annot);
        $exp = preg_split('[\(]', $annot);
        $return = array(
            'type' => $exp[0],
            'attrs' => array(),
        );
        if (isset($exp[1])) {
            parse_str(str_replace(array(','), array('&'), $exp[1]), $return['attrs']);
        }

        $_return = $return;
        foreach ($_return['attrs'] as $attr => $val) {
            $return['attrs'][$attr] = trim($val);
        }
        return $return;
    }

    /**
     * Fetches the settings for a property
     * @param string $property
     * @return mixed
     */
    public function getSettings($property = null) {
        if ($this->settings === null) {
            $path = DATA . 'mapper' . DIRECTORY_SEPARATOR . str_replace('\\', '.', get_called_class());
            $this->settings = include $path;
//            $annots = new Annotation(get_called_class());
//            $this->settings = $this->parseAnnotations($annots->getProperties(), false);
        }

        if ($property === null)
            return $this->settings;

        if (isset($this->settings[$property]))
            return $this->settings[$property];
    }

    private function checkModelExists($model) {
        if (class_exists($model))
            return $model;

        if (!strstr($model, '\\')) {
            $nm = $this->getNamespace();
            $model = $nm . '\\' . $model;
            if (class_exists($model))
                return $model;
        }

        return false;
    }

    final public function getNamespace() {
        $exp = explode('\\', get_called_class());
        unset($exp[count($exp) - 1]);
        return join('\\', $exp);
    }

    /**
     * Creates the reference settings for a reference column
     * @param string $property
     * @param array $annot
     * @param array $primary If available, will contain keys "column" and "desc"
     * indicating the primary column and it's description
     * @return array
     * @throws Exception
     * @todo allow referencing table with no model
     */
    private function createReference($property, array $annot, array $primary = array()) {
        if (!isset($annot['attrs']['model']))
            throw new Exception('Attribute "model" not set for reference property "' .
            $property . '" of class "' . get_called_class() . '"');

        if (!$annot['attrs']['model'] = $this->checkModelExists($annot['attrs']['model'])) {
            throw new Exception('Model "' . $annot['attrs']['model'] . '" of reference property "' . $property .
            '" does not exist in class "' . get_called_class() . '"');
        }
        elseif (!in_array('DScribe\Core\IModel', class_implements($annot['attrs']['model'])))
            throw new Exception('Model "' . $annot['attrs']['model'] . '" must implement "DScribe\Core\IModel"');

        $refTable = new $annot['attrs']['model'];
        $refTable->setConnection($this->getConnection());
        $conTable = $this->getConnection()->table($refTable->getTableName(), $refTable);

        if ($conTable->getName() === $this->_table->getName() && !empty($primary['column'])) {
            $annot['attrs']['property'] = Util::camelTo_($primary['column']);
        }
        else {

            $refTable->init($conTable);
            if (!isset($annot['attrs']['property'])) {
                if (!$conTable->getPrimaryKey())
                    throw new Exception('Property "' . $property . '" must have attribute "property" as model "' .
                    $annot['attrs']['model'] . '" does not have a primary key');

                $annot['attrs']['property'] = $conTable->getPrimaryKey();
            }

            $annot['attrs']['property'] = \Util::camelTo_($annot['attrs']['property']);

            if (!in_array($annot['attrs']['property'], $conTable->getIndexes())) {
                $conTable->addIndex($annot['attrs']['property']);
                if ($conTable->exists())
                    $this->getConnection()->alterTable($conTable);
            }
        }

        $attrs = ($conTable->getName() === $this->_table->getName()) ? $primary['desc'] : $refTable->getSettings(\Util::_toCamel($annot['attrs']['property']));

        if ($attrs === null)
            throw new Exception('Property "' . $annot['attrs']['property'] . '", set as attribute "property" for property "' .
            $property . '" in class "' . get_called_class() . '", does not exist');

        if (isset($attrs['attrs']['auto_increment']))
            unset($attrs['attrs']['auto_increment']);
        if (isset($attrs['attrs']['primary']))
            unset($attrs['attrs']['primary']);

        $column = $annot['attrs']['property'];
        unset($annot['attrs']['property']);
        unset($annot['attrs']['size']);

        $attrs['attrs']['reference'] = array_merge($annot['attrs'], array(
            'table' => $refTable->getTableName(),
            'column' => $column,
        ));

        return $attrs;
    }

    /**
     * Saves the annotations as table schema
     * @param string $path
     * @param array $annotations
     */
    private function save($path, $annotations) {
        if (!is_dir(DATA . 'mapper'))
            mkdir(DATA . 'mapper', 0755, TRUE);

        $content = var_export($annotations, true);
        file_put_contents($path, '<' . '?php' . "\n\t" . 'return ' . str_replace("=> \n", ' => ', $content) . ";");

        $this->saveModelTable($this->getTableName(), get_called_class());
    }

    /**
     * Saves table models
     * @param string $tableName
     * @param string $modelClass
     * @return boolean
     */
    private function saveModelTable($tableName, $modelClass) {
        $modelTables = array();
        $mt = DATA . 'mapper' . DIRECTORY_SEPARATOR . '__modelTables.php';
        if (is_readable($mt))
            $modelTables = include $mt;

        if (!isset($modelTables[$tableName]) ||
                (isset($modelTables[$tableName]) && !in_array($modelClass, $modelTables[$tableName]))) {
            $modelTables[$tableName][] = $modelClass;
            return file_put_contents($mt, '<' . '?php' . "\n\t" . 'return ' . stripslashes(var_export($modelTables, true)) . ";");
        }

        return true;
    }

    /**
     * Fetches the model class for the given table name
     * @param string $tableName
     * @return array
     */
    public static function getModelTable($tableName) {
        $mt = DATA . 'mapper' . DIRECTORY_SEPARATOR . '__modelTables.php';
        if (!is_readable($mt))
            return array();

        $modelTables = include $mt;
        if (isset($modelTables[$tableName]))
            return $modelTables[$tableName];

        return array();
    }

    /**
     * Function to call before saving the model
     *
     * Cleans up values of types date, time and timestamp
     *
     * Turns empty values to null
     */
    public function preSave() {
        foreach ($this->toArray() as $ppt => $val) {
            $settingKey = \Util::_toCamel($ppt);
            if ($this->settings[$settingKey]['type'] === 'ReferenceMany' && !empty($val)) {
                if (is_object($val) && is_a($val, 'DBScribe\ArrayCollection')) {
                    $val = $val->getArrayCopy();
                }

                $idSep = '__:DS:__';

                $ids = '';
                if (is_array($val)) {
                    foreach ($val as $vl) {
                        if (is_object($vl) && is_a($vl, 'DBScribe\Row')) {
                            if (!isset($vl->id)) {
                                throw \Exception('ReferenceMany requires that all objects must have an ID');
                            }
                            if (method_exists($vl, 'getId')) {
                                $vl = $vl->getId();
                            }
                            else {
                                $vl = $vl->id;
                            }
                        }

                        if (is_null($vl)) {
                            throw \Exception('ReferenceMany objects CANNOT have a null ID');
                        }

                        $ids .= (!empty($ids)) ? $idSep . $vl : $vl;
                    }

                    $this->$settingKey = $ids;
                    $val = $this->$settingKey;
                }
            }
            else if (strtolower($this->settings[$settingKey]['type']) === 'date' && !empty($val)) {
                $this->$settingKey = Util::createTimestamp(strtotime($val), 'Y-m-d');
                $val = $this->$settingKey;
            }
            elseif (strtolower($this->settings[$settingKey]['type']) === 'time' && !empty($val)) {
                $this->$settingKey = Util::createTimestamp(strtotime($val), 'H:i');
                $val = $this->$settingKey;
            }
            elseif (strtolower($this->settings[$settingKey]['type']) === 'timestamp' && !empty($val)) {
                $this->$settingKey = Util::createTimestamp(strtotime($val), 'Y-m-d H:i:s');
                $val = $this->$settingKey;
            }
            elseif (empty($val) && $val != 0) {
                if (!property_exists($this, $ppt))
                    $ppt = Util::_toCamel($ppt);
                $this->$ppt = null;
            }
        }

        $this->setConnection(Engine::getDB());
        $table = $this->getConnection()->table($this->getTableName());
        $this->init($table);

        parent::preSave();
    }

    /**
     * Function to call after fetching row
     * 
     * Straightens out ReferenceMany
     */
    public function postFetch() {
        if (!empty($this->settings)) {
            foreach ($this->settings as $column => $descArray) {
                if ($descArray['type'] === 'ReferenceMany') {
                    $this->$column = new ArrayCollection(explode('__:DS:__', $this->$column));
                }
            }
        }

        parent::postFetch();
    }

    private function getIgnore() {
        $ignore = Session::fetch('mapperIgnore');
        return is_array($ignore) ? $ignore : array();
    }

    /**
     * Retrieves the annotations in the mapper class
     * @param boolean $forceNew Indicates whether to regenerate settings ignore existing one
     * @return array
     */
    private function getAnnotations($forceNew = false) {
        $ignore = $this->getIgnore();
        if (!in_array($this->getTableName(), $ignore)) {
            $ignore[] = $this->getTableName();
            Session::save('mapperIgnore', $ignore);
        }

        if ($this->settings === null || $forceNew === true) {
            $this->annotations = new Annotation(get_called_class());
            $this->settings = $this->parseAnnotations($this->annotations->getProperties('DBS'));
        }

        return $this->settings;
    }

    /**
     * Replaces the magic method __set() for mapper classes
     * @param string $property
     * @param mixed $value
     */
    protected function _set($property, $value) {
        
    }

    /**
     * Serializes only the properties of the model
     * @return type
     */
    public function __sleep() {
        return array_keys($this->toArray(false, true));
    }

    /**
     * Returns the name of the class as the string
     * @return string
     */
    public function __toString() {
        return get_called_class();
    }

    final public function getTable() {
        return $this->_table;
    }

    final public function getRelationship($tableName) {
        $return = parent::getRelationship($tableName);
//        if (!$return && $settings = $this->getSettings(Util::_toCamel($tableName))) {
//            if ($settings['type'] == 'ReferenceMany') {
//                $return = array(
//                    array(
//                        'column' => $tableName,
//                        'refColumn' => $settings['attrs']['column'],
//                    )
//                );
//            }
//        }
        return $return;
    }

}
