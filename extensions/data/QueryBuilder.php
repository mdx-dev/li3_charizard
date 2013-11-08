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
		return static::validate(implode('&', $raw));
	}

	public function validate($queryString){
		//there can not be more than 1 q
		if(substr_count($queryString, "&q=") > 1){
			preg_match_all("/&q=(.*?)&/", $queryString, $qStrings);
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