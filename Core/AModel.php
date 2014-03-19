<?php

namespace DScribe\Core;

use DScribe\DB\Mapper;

class AModel extends Mapper {

    public function preSave() {
        return parent::preSave();
    }

    public function postSave($operation, $result, $lastInsertId) {
        return parent::postSave($operation, $result, $lastInsertId);
    }

    /**
     * Fetches an array of properties and their values
     * @param boolean $withNull Indicates whether to return properties with null values too
     * @param boolean $asIs Indicates whether to return properties as gotten from parent method.
     * This leaves them with underscores and not camel cases
     * @return array
     */
    public function toArray($withNull = false, $asIs = false) {
        $array = parent::toArray();
        unset($array['tableName']);

        if ($asIs) {
            return $array;
        }

        $return = array();
        foreach ($array as $name => $value) {
            if (($value === null && !$withNull)) {
                continue;
            }
            $return[\Util::camelTo_($name)] = $value;
        }
        return $return;
    }

}
