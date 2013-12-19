<?php

namespace li3_charizard\extensions\net\http;

class RawRequest extends \lithium\net\http\Request {

	public function body($data = null, $options = array()) {
		$defaults = array('encode' => false);
		return parent::body($data, $options + $defaults);
	}

}

?>
