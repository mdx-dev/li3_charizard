<?php

namespace li3_charizard\extensions\data;

use lithium\core\Object;
use InvalidArgumentException;

class QueryBuilder extends Object {

	protected $_data = array();

	protected $_modelConfig = array();

	protected $_config = array();

	protected $_classes = array(
		'builder' => 'li3_charizard\extensions\data\QueryStringBuilder',
	);

	protected $_autoConfig = array('config', 'data', 'classes', 'modelConfig');

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
		$builder = $this->_classes['builder'];
		$raw = array('select?wt=json');
		foreach ($this->_data as $key => $value) {
			$method = "{$key}ToString";
			$raw[] = $builder::$method($value, $this->_modelConfig);
		}
		$queryString = implode('&', $raw);
		$queryString = str_replace('&&', '&', $queryString); // replace && with &
		$queryString = trim($queryString, '&'); //remove trailing or opening &

		return $queryString;
	}

}

?>