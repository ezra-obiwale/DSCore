<?php

namespace DScribe\Core;

/**
 * Description of IRepository
 *
 * @author topman
 */
interface IRepository {

	public function fetchAll();

	public function find($id);
}
