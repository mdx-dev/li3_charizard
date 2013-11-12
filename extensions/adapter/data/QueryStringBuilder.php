<?php

namespace li3_charizard\extensions\adapter\data;

use lithium\core\StaticObject;
use BadMethodCallException;

class QueryStringBuilder extends StaticObject {

	public static function startToString($value) {
		if ($value > 0) {
			return array(
				'key' => 'start',
				'value' => $value,
			);
		}
	}

	public static function offsetToString($value) {
		if ($value > 0) {
			return array(
				'key' => 'group.offset',
				'value' => $value,
			);
		}
	}

	public static function rowsToString($value) {
		return array(
			'key' => 'rows',
			'value' => $value,
		);
	}

	public static function selectToString($values) {
		foreach ($values as $key => &$value) {
			if ($key === 'display_name') {
				$value = static::comboKeyValue($key, $value);
			} elseif ($value !== '' && !is_null($value)) {
				$value = $key . ':' . $value;
			}
		}
		return array(
			'key' => 'q',
			'value' => implode(' AND ', array_filter($values)),
		);
	}

	public static function suggestionsToString($values, array $config = array()) {
		$field = $values['typeahead_field'];
		$phrase = $values['typeahead_phrase'];
		return array(
			'key' => 'q',
			'value' => static::comboKeyValue($field, $phrase, $config),
		);
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
		return array(
			'key' => 'sort',
			'value' => implode(', ', $values),
		);
	}

	public static function groupbyToString($values) {
		$items = array(
			array('key' => 'group', 'value' => 'true'),
			array('key' => 'group.limit', 'value' => '1'),
			array('key' => 'group.ngroups', 'value' => 'true'),
			array('key' => 'group.cache.percent', 'value' => '0'),
			array('key' => 'group.truncate', 'value' => 'true'),
			array('key' => 'group.facet', 'value' => 'false'),
		);
		foreach ($values as $value) {
			$items[] = array(
				'key' => 'group.field',
				'value' => $value,
			);
		}
		return $items;
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
			return array(
				'key' => 'fq',
				'value' => "{!bbox pt={$values['latlong']} sfield={$values['field']} d={$values['radius']}}",
			);
		}elseif(array_key_exists('_distance_sort', $values) && $values['_distance_sort'] == 'hash'){
			return array(
				'key' => 'q',
				'value' => "{!geofilt score=distance filter=true pt={$values['latlong']} sfield={$values['field']} d={$values['radius']}}",
			);
		}
	}


	/**
	 * TODO why are we using `!tag=`?
	 */
	public static function filterToString($values){
		if (empty($values['field'])) {
			return;
		}
		$filterFields = array();
		foreach ($values['field'] as $key => $value) {
			if (!$value) {
				continue;
			}
			$filterFields[] = array(
				'key' => 'fq',
				'value' => "{!tag={$key}}{$key}:{$value}",
			);
		}
		return $filterFields;
	}

	public static function fieldsToString($values){
		return array(
			'key' => 'fl',
			'value' => implode(',', $values),
		);
	}


	/**
	 * Returns Faceting portion of query
	 *
	 * @param mixed $values
	 * @return array $facetParameters
	 */
	public static function facetToString($values){
		$facetOptions = array('field', 'range', 'query', 'threads');
		if($values && count(array_intersect_key(array_flip($facetOptions), $values))){
			$facetParameters = array('facet' => array('key' => 'facet', 'value' => 'true'));

			/*Facet Field Variables*/
			if(isset($values['field'])){
				$facetFields = array();
				foreach($values['field'] as $key => $value){
					$facetFields[] = array(
						'key' => 'facet.field',
						'value' => "{!key={$key}}{$value}"
					);
				}
				$facetParameters['facetFields'] = $facetFields;
			}

			/*Facet Range Variables*/
			if(isset($values['range'])){
				$facetRanges = array();
				foreach($values['range'] as $range){
					$rangeName = $range['field'];
					foreach ($range as $key => $value) {
						if($key == 'field'){
							$facetRanges[] = array(
								'key' => 'facet.range',
								'value' => '{!key=' .
								(isset($range['label'])? $range['label'] : $range['field']) .
								'}' . $value,
							);
						}elseif($key != 'label'){
							$facetRanges[] = array(
								'key' => "f.{$rangeName}.facet.range.{$key}",
								'value' => $value
							);
						}
					}
				}
				$facetParameters['faceRanges'] = $facetRanges;
			}

			/*Facet Query Variables*/
			if(isset($values['query'])){
				$facetQueries = array();
				foreach($values['query'] as $query){
					if(isset($query['field']) && isset($query['value'])){
						$facetQueries[] =array(
							'key' => 'facet.query',
							'value' => '{!key=' .
								(isset($query['label']) ? $query['label'] : $query['field']) .
								'}'. $query['field']. ':'. $query['value']
						);
					}
				}
				$facetParameters['facetQueries'] = $facetQueries;
			}
			return $facetParameters;
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


	/**
	 * Compiles the key values into a usable query string.
	 *
	 * @param array $query
	 * @return array
	 */
	public static function compile(array $query) {
		$segments = array();
		foreach ($query as $value) {
			if (empty($value)) {
				continue;
			}
			if (is_string($value)) {
				$segments[] = $value;
				continue;
			}
			if (is_array(current($value))) {
				$segments[] = static::compile($value);
				continue;
			}
			if(!array_key_exists('key', $value)){
				if (is_array(current($value))) {
					$segments[] = static::compile($value);
				}
			}else{
				$segments[] = $value['key'] . '=' . urlencode($value['value']);
			}
		}
		return implode('&', $segments);
	}

}

?>