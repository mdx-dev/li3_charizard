<?php

namespace li3_charizard\extensions\data;

use lithium\core\Object;
use InvalidArgumentException;

class QueryBuilder extends Object {

	protected $_data = array();

	protected $_config = array();

	protected $_classes = array(
		'builder' => 'li3_charizard\extensions\data\QueryStringBuilder',
	);

	protected $_autoConfig = array('config', 'data', 'classes');

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
		$builder = $this->_classes['builder'];
		$raw = array('select?wt=json');
		foreach ($this->_data as $key => $value) {
			$method = "{$key}ToString";
			$raw[] = $builder::$method($value);
		}
		//$queryString = implode('&', $raw);
		$queryString = self::validate(implode('&', $raw));

		return $queryString;
	}


	public function validate($queryString){
		$list = explode("&", $queryString);
		$query_parts = array(
			'base' => $list[0],  //handler and output engine
			'q' => array(),  //multiple qs
			'fq' => array(),  //multiple fqs
			'fl' => '',  //string of fields to be returned
			'sort' => array(),
			'start' => array(),
			'rows' => array(),
			'facet' => array(),
			'group' => array(),
		);
		if(substr_count($queryString, "&q=") > 1){
			//there an not be more than 1 q
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
		while(substr_count($queryString, '&&') > 0){
			$queryString = str_replace('&&', '&', $queryString); // replace && with &
		}
		$queryString = trim($queryString, '&'); //remove trailing or opening &
		return $queryString;
	}

}

?>