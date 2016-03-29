<?php

namespace dScribe\Core;

use dbScribe\Mapper;

class AModel extends Mapper implements IModel {

    public function preSave() {
        return parent::preSave();
    }

    public function postSave($operation, $result, $lastInsertId) {
        return parent::postSave($operation, $result, $lastInsertId);
    }

}
