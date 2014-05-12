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

}
