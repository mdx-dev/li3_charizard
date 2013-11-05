<?php

namespace li3_charizard\extensions\data;

use lithium\core\Object;
use InvalidArgumentException;

class QueryBuilder extends Object {

	protected $_data = array();

	protected $_config = array();

	protected $_autoConfig = array('config', 'data');

	public function __construct() {
		parent::__construct();
	}

	public function import($fields) {
		$this->_data = $fields;
		return $this;
	}

	public function to($type) {
		if ($type === 'string') {
			return $this->__toString();
		}
		throw new InvalidArgumentException("Type {$type} not supported.");
	}

	public function __toString() {
		return '';
	}

}

?>