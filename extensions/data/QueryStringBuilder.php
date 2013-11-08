<?php

namespace li3_charizard\extensions\data;

use lithium\core\StaticObject;
use BadMethodCallException;

class QueryStringBuilder extends StaticObject {

	public static function startToString($value) {
		if ($value > 0) {
			return 'start=' . $value;
		}
	}

	public static function offsetToString($value) {
		if ($value > 0) {
			return 'group.offset='. $value;
		}
	}

	public static function rowsToString($value) {
		return 'rows=' . $value;
	}

	public static function selectToString($values) {
		foreach ($values as $key => &$value) {
			if ($key === 'display_name') {
				$value = self::comboKeyValue($key, $value);
			} else {
				$value = $key . ':' . $value;
			}
		}
		return 'q=' . implode(' OR ', $values);
	}

	public static function suggestionsToString($values, array $config = array()) {
		$field = $values['typeahead_field'];
		$phrase = $values['typeahead_phrase'];
		return 'q=' . self::comboKeyValue($field, $phrase, $config);
	}

	public static function sortToString($values) {
		foreach ($values as $key => &$value) {
			if (is_numeric($key) && is_array($value)) {
				$key = key($value);
				$value = $value[$key];
			}
			if($key == '_distance_sort'){  
				//I think this value should be changed to 'score' in the model not here
				$value = 'score ' . $value;
			}else{
				$value = $key . ' ' . $value;
			}
		}
		return 'sort=' . implode(', ', $values);
	}

	public static function groupByToString($values) {
		$items = array(
			'group=true',
			'group.limit=1',
			'group.ngroups=true',
			'group.cache.percent=0',
			'group.truncate=true',
			'group.facet=false',
		);
		foreach ($values as $value) {
			$items[] = 'group.field=' . $value;
		}
		return implode('&', $items);
	}

	/**
	 * TODO not yet implemeneted.
	 *
	 * @param mixed $method
	 * @param mixed $values
	 */
	public static function __callStatic($method, $values) {
		return '__FIXME__';
		throw new BadMethodCallException("Method #{$method} not defined");
	}

	/**
	 * TODO seems to have nothing to do with final query string
	 *
	 * @param mixed $values
	 */
	public static function relatedToString($values){
		return '';
	}

	public static function geoToString($values){
		if(array_key_exists('_distance_sort', $values) && $values['_distance_sort'] == 1){
			return "fq={!bbox pt={$values['latlong']} sfield={$values['field']} d={$values['radius']}}";
		}elseif(array_key_exists('_distance_sort', $values) && $values['_distance_sort'] == 'hash'){
			return "q={!geofilt score=distance filter=true pt={$values['latlong']} sfield={$values['field']} d={$values['radius']}}";
		}
	}

	public static function filterToString($values){
		if($values){
			foreach($values as $key => $value){
				if($value){

				}
			}
		}
	}

	public static function fieldsToString($values){
		return 'fl=' . implode(',', $values);
	}


	public static function facetToString($values){
		return '';
	}

	/**
	 * Determines the field alias and builds the query based on it.
	 *
	 * @param string $key
	 * @param array  $value
	 * @param string $config
	 */
	public static function comboKeyValue($key, $value, array $config = array()) {
		if (empty($config['str_fields'][$key])) {
			return '';
		}
		$related = $config['str_fields'][$key]['related'];
		$relatedArray = array();
		foreach ($related as $relField) {
			if (!$relField['append']) {
				continue;
			}
			$relatedArray[] = $relField['field'] . ':' . $value . '^' . $relField['boost'];
		}
		$relatedData = implode(' OR ' , $relatedArray);
		$infix = $config['str_fields'][$key]['infix'];
		$infixData = ' OR ' . $infix['field'] . ':' . $value;
		return '((' . $relatedData . ')' . $infixData . ')';
	}

}

?>