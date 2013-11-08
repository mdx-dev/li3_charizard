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
			$filterFields = array();
			if($values['field']){
				foreach($values['field'] as $key => $value){
					if($value){
						//why are we using {!tag=} here?
						$filterFields[] = "fq={!tag={$key}}{$key}:{$value}";
					}
				}
			}
			if($filterFields){
				return '&' . implode('&', $filterFields);
			}
		}
	}

	public static function fieldsToString($values){
		return 'fl=' . implode(',', $values);
	}

	public static function facetToString($values){
		if($values){
			if($values['field']){
				$facetFields = array();
				foreach($values['field'] as $key => $value){
					$facetFields[] = "{!key={$key}}{$value}";
				}
			}
			if($values['range']){
				$facetRanges = array();
				foreach($values['range'] as $range){
					$rangeName = $range['field'];
					$string = "facet.range={!key={$range['field']}}{$rangeName}";
					foreach ($range as $key => $value) {
						if($key != 'field' && isset($value)){
							$string .= "&f.{$rangeName}.facet.range.{$key}={$value}";
						}
					}
					$facetRanges[] = $string;
				}
			}
			if($facetFields || $facetRanges){
				return 'facet=true' . ($facetFields ? '&'. implode('&', $facetFields) : '').
					($facetRanges ? '&'. implode('&', $facetRanges) : '');
			}
		}
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
			$relData = $relField['field'] . ':' . $value;
			if (!empty($relField['boost'])) {
				$relData .= '^' . $relField['boost'];
			}
			$relatedArray[] = $relData;
		}
		$relatedData = implode(' OR ' , $relatedArray);
		if (empty($config['str_fields'][$key]['infix'])) {
			return '(' . $relatedData . ')';
		}
		$infix = $config['str_fields'][$key]['infix'];
		$infixData = ' OR ' . $infix['field'] . ':' . $value;
		if (!empty($infix['boost'])) {
			$infixData .= '^' . $infix['boost'];
		}
		return '((' . $relatedData . ')' . $infixData . ')';
	}

}

?>