<?php

namespace dScribe\Core;

class GuestUser extends AUser {

	public function getId() {
		return 0;
	}

	public function getRole() {
		return 'guest';
	}

}
