<?php

namespace li3_charizard\extensions\adapter\data;

use lithium\core\Object;
use lithium\data\model\Query;
use InvalidArgumentException;

class QueryBuilder extends Object {

	protected $_query = array();

	protected $_config = array();

	protected $_classes = array(
		'builder' => 'li3_charizard\extensions\adapter\data\QueryStringBuilder',
	);

	protected $_fields = array(
		'suggestions',
		'select',
		'fields',
		'start',
		'offset',
		'filter',
		'sort',
		'groupby',
		'related',
		'facet',
		'geo',
		'rows',
	);

	protected $_autoConfig = array(
		'query',
		'config',
		'classes',
		'fields',
	);

	/**
	 * TODO SHOULD BE REMOVED ONLY USED IN TESTS
	 */
	public function import($fields) {
		$this->_query = new Query($fields);
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
		$source = $this->_query->source();
		foreach ($this->_fields as $key) {
			$method = "{$key}ToString";
			$data = $this->_query->$key();
			if (empty($data)) { continue; }
			$raw[] = $builder::$method($data, (array) $source['config']);
		}
		return static::validate($builder::compile($raw));
	}

	public function validate($queryString){
		//there can not be more than 1 q
		if(substr_count($queryString, "&q=") > 1){
			$qStrings = $qStrings[0];
			for($x=0; $x<count($qStrings); $x++){
				$queryString = preg_replace("/&q=(.*?)&/", "__STRING{$x}__", $queryString, 1);
			}
			$i=1;
			foreach ($qStrings as $string) {
				if(substr_count($string, "{!geofilt") > 0)   {
					$queryString = str_replace('__STRING0__', $string, $queryString);
				}else{
					$queryString = str_replace('__STRING' . $i++ . '__', str_replace('&q=', '&fq=', $string), $queryString);
				}
			}
		}
		// replace && with &
		while(substr_count($queryString, '&&') > 0){
			$queryString = str_replace('&&', '&', $queryString);
		}
		//remove trailing or opening &
		$queryString = trim($queryString, '&');

		return $queryString;
	}

}

?>