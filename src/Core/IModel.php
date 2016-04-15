<?php

namespace dScribe\Core;

interface IModel {

	/**
	 * Fetches the name of the table
	 */
	public function getTableName();

	/**
	 * Populates the properties of the model from the passed data
	 * @param array $data
	 */
	public function populate(array $data);

	/**
	 * Returns array of properties and values
	 */
	public function toArray();
}
