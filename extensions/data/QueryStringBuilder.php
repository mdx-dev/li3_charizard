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
		return static::startToString($value);
	}

	public static function rowsToString($value) {
		return 'rows=' . $value;
	}

	public static function selectToString($values) {
		foreach ($values as $key => &$value) {
			if ($key === 'display_name') {
				$value = static::comboKeyValue($key, $value);
			} else {
				$value = $key . ':' . $value;
			}
		}
		return 'q=' . implode(' OR ', $values);
	}

	public static function suggestionsToString($values, array $config = array()) {
		$field = $values['typeahead_field'];
		$phrase = $values['typeahead_phrase'];
		return 'q=' . static::comboKeyValue($field, $phrase, $config);
	}

	public static function sortToString($values) {
		foreach ($values as $key => &$value) {
			if (is_numeric($key) && is_array($value)) {
				$key = key($value);
				$value = $value[$key];
			}
			$value = $key . ' ' . $value;
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

	/**
	 * Determines the field alias and builds the query based on it.
	 *
	 * @param string $key
	 * @param array  $value
	 * @param string $config
	 */
	public static function comboKeyValue($key, $value, array $config = array()) {
		if (empty($config['str_fields'][$key]['related'])) {
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
		if (empty($config['str_fields'][$key]['ifnix'])) {
			return '(' . $relatedDate . ')';
		}
		$infix = $config['str_fields'][$key]['infix'];
		$infixData = ' OR ' . $infix['field'] . ':' . $value;
		return '((' . $relatedData . ')' . $infixData . ')';
	}

}

?>