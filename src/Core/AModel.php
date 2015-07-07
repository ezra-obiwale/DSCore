<?php

namespace DScribe\Core;

use DBScribe\Mapper;

class AModel extends Mapper implements IModel {

    public function preSave() {
        return parent::preSave();
    }

    public function postSave($operation, $result, $lastInsertId) {
        return parent::postSave($operation, $result, $lastInsertId);
    }

}
