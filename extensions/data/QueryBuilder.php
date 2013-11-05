<?php

namespace li3_charizard\extensions\data;

use lithium\core\Object;
use InvalidArgumentException;
use BadMethodCallException;

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
		$raw = array('select?wt=json');
		foreach ($this->_data as $key => $value) {
			$method = "_{$key}ToString";
			$raw[] = $this->$method($value);
		}
		return implode('&', $raw);
	}

	protected function _startToString($value) {
		return 'start=' . $value;
	}

	protected function _rowsToString($value) {
		return 'rows=' . $value;
	}

	protected function _selectToString($values) {
		foreach ($values as $key => &$value) {
			$value = $key . ':' . $value;
		}
		return 'q=' . implode(' OR ', $values);
	}

	protected function _sortToString($values) {
		foreach ($values as $key => &$value) {
			if (is_numeric($key) && is_array($value)) {
				$key = key($value);
				$value = $value[$key];
			}
			$value = $key . ' '. $value;
		}
		return 'sort=' . implode(', ', $values);
	}

	public function __call($method, $values) {
		// throw new BadMethodCallException("Method #{$method} not defined");
	}

}

?>