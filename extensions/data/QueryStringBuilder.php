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

	public static function suggestionsToString($values){
		$field = $values['typeahead_field'];
		$phrase = $values['typeahead_phrase'];
		return 'q=' . self::comboKeyValue($field, $phrase);
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
	 * TODO Hardcoded hack, I want to use custom select handlers in solr instead
	 * of using these combined fields.
	 */
	public static function comboKeyValue($key, $value){
		$geoZipCombo = '((state:__VAL__^10 OR city:__VAL__^10 OR zip:__VAL__^10 OR ' .
			'state_full:__VAL__^10) OR geo_zip_autosuggest:__VAL__)';
		$disorderCombo = '((disorder_id:__VAL__^1 OR related_disorder:__VAL__^2 OR ' .
			'field_specialty:__VAL__^2 OR specialist:__VAL__^2 OR disorder_id:__VAL__' .
			'^1 OR related_disorder:__VAL__^2 OR field_specialty:__VAL__^2 OR ' .
			'specialist:__VAL__^2) OR disorder_autosuggest:__VAL__)';
		$nameCombo = 'name_autosuggest:__VAL__^0.1 OR (name_combo:__VAL__^2 OR ' .
			'first_name:__VAL__^5 OR middle_name:__VAL__^3 OR last_name:__VAL__^7 ' .
			'OR alias_first_name:__VAL__^1 OR alias_middle_name:__VAL__^2 OR ' .
			'alias_last_name:__VAL__^3 OR alias_suffix:__VAL__^1)';
		$template = null;
		switch ($key) {
			case 'geo_zip_combo':
				$template = $geoZipCombo;
				break;
			case 'name_combo':
			case 'display_name':
				$template = $nameCombo;
				break;
			case 'disorder':
				$template = $disorderCombo;
				break;
		}
		if ($template) {
			return str_replace('__VAL__', $value, $template);
		}
	}

}

?>