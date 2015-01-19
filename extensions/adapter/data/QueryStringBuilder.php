<?php

namespace li3_charizard\extensions\adapter\data;

use lithium\core\StaticObject;
use BadMethodCallException;
use InvalidArgumentException;

class QueryStringBuilder extends StaticObject {

	public static function solrJoinToString($value) {
		$value += array(
			'from' => null,
			'fromIndex' => null,
			'to' => null,
		);
		foreach (array('from', 'to') as $key) {
			if (!is_string($value[$key]) || !strlen($value[$key])) {
				$msg = "Charizard join expects a non-empty string for the `$key` param.";
				throw new InvalidArgumentException($msg);
			}
		}
		if (!is_null($value['fromIndex'])) {
			if (!is_string($value['fromIndex']) || !strlen($value['fromIndex'])) {
				$msg = 'Charizard join expects a non-empty string for the optional'
					. ' `fromIndex` param.';
				throw new InvalidArgumentException($msg);
			}
		}

		$params = array(
			"from={$value['from']}",
			"to={$value['to']}",
		);
		if (!is_null($value['fromIndex'])) {
			$params[] = "fromIndex={$value['fromIndex']}";
		}
		$localParam = '{!join ' . implode(' ', $params) . '}';

		return array(
			'key' => 'fq',
			'value' => $localParam . '*:*',
		);
	}

	public static function startToString($value) {
		if ($value > 0) {
			return array(
				'key' => 'start',
				'value' => $value,
			);
		}
	}

	public static function offsetToString($value) {
		return static::startToString($value);
	}

	public static function rowsToString($value) {
		return array(
			'key' => 'rows',
			'value' => $value,
		);
	}

	public static function selectToString($values, array $config = array()) {
		if(empty($values)){
			return array(
				'key' => 'q',
				'value' => '*:*',
			);
		}
		foreach ($values as $key => &$value) {
			if ($key === 'display_name') {
				$original_value = $value;
				$value = static::comboKeyValue($key, $value, $config);
				if($value == null){
					$value = $key . ':' . $original_value;
				}
			} elseif ($key == 'FUNCTION' && !is_null($value)) {
				return(array(
					'key' => 'q',
					'value' => $value,
					));
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
		if(isset($values) && is_array($values)){
			$items = array(
				array('key' => 'group', 'value' => 'true')
			);
			//set defaults
			$groupbyItems = array('group.limit' => 1,
														'group.ngroups' => 'true',
														'group.cache.percent' => '0',
														'group.truncate' => 'true',
														'group.facet' => 'false'
			);
			foreach($values as $key => $value){
				if($key == 'field' && is_array($value)){
					//handle group.fields
					foreach ($value as $field) {
						$items[] = array(
							'key' => 'group.field',
							'value' => $field,
						);
					}
				}elseif($key == 'sort' && is_array($value)){
					//handle group.sort
					$vals=array();
					foreach ($value as $field => $order) {
						$vals[] ="{$field} {$order}";
					}
					$items[] = array(
						'key' => 'group.sort',
						'value' => implode(',', $vals)
					);
				}else{
					//everything else (replace defaults)
					$groupbyItems[$key] = $value;
				}
			}

			foreach ($groupbyItems as $key => $value) {
				$items[] = array('key' => $key, 'value' => $value);
			}
			return $items;
		}
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

	public static function filterToString($values){
		if (empty($values['field']) &&
				empty($values['facet']) &&
				empty($values['query']) &&
				empty($values['facet_query'])) {
			return;
		}
		$filterFields = array();
		if(isset($values['field'])){
			foreach ($values['field'] as $key => $value) {
				if (!isset($value) || $value === '') {
					continue;
				}
				$filterFields[] = array(
					'key' => 'fq',
					'value' => "{!tag={$key}}{$key}:{$value}",
				);
			}
		}
		if(isset($values['facet'])){
			foreach ($values['facet'] as $key => $value) {
				if (!$value) {
					continue;
				}
				$filterFields[] = array(
					'key' => 'fq',
					'value' => "{!tag={$key}}{$key}:{$value}",
				);
			}
		}
		if(isset($values['query'])){
			foreach ($values['query'] as $key => $value) {
				$filterFields[] = array(
					'key' => 'fq',
					'value' => "{!tag={$key}}$value",
				);
			}
		}
		if(isset($values['facet_query'])){
			foreach ($values['facet_query'] as $key => $value) {
				if(isset($value['query'])){
					$filterFields[] = array(
						'key' => 'fq',
						'value' => "{!tag={$key}}{$value['query']}",
					);
				}
				if(isset($value['facet']['range'])){
					$filterFields[] = array(
						'key' => 'facet.range',
						'value' => "{!tag={$key} ex={$key}}{$key}",
					);
					$filterFields[] = static::facetToString($value['facet']);
				}
			}
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
		$facetOptions = array('field', 'range', 'query', 'threads', 'pivot', 'mincount', 'sort', 'limit');
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

			/*Facet Pivot Variables*/
			if(isset($values['pivot'])){
				$facetPivots = array();
				foreach($values['pivot'] as $tag => $fields){
					$facetPivots[] =array(
						'key' => 'facet.pivot',
						'value' => '{!key=' . $tag . '}:' . implode(',', $fields),
					);
				}
				$facetParameters['facetPivots'] = $facetPivots;
			}

			if(isset($values['mincount'])){
				$facetParameters['mincount'] = array(
																				'key' => 'facet.mincount',
																				'value' => $values['mincount'],
																			);
			}
			if(isset($values['sort'])){
				$facetParameters['sort'] = array(
																				'key' => 'facet.sort',
																				'value' => $values['sort'],
																			);
			}
			if(isset($values['limit'])){
				$facetParameters['limit'] = array(
																				'key' => 'facet.limit',
																				'value' => $values['limit'],
																			);
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

		//if the value has a space in it, convert to a solr friendly value
		if(is_string($value) && substr_count($value, ' ')){
			$value = '(+' . str_replace(' ', ' +', $value) . ')';
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

		if(isset($config['str_fields'][$key]['infix']) ||
				isset($config['str_fields'][$key]['autosuggest'])){

			$autosuggestData = $infixData = "";

			if(isset($config['str_fields'][$key]['infix'])){
				$infix = $config['str_fields'][$key]['infix'];
				$infixData = ' OR ' . $infix['field'] . ':' . $value;
				if (!empty($infix['boost'])) {
					$infixData .= '^' . $infix['boost'];
				}
			}

			if(isset($config['str_fields'][$key]['autosuggest'])){
				$autosuggest = $config['str_fields'][$key]['autosuggest'];
				$autosuggestData = ' ' . $autosuggest['field'] . ':' . $value;
				if (!empty($autosuggest['boost'])) {
					$autosuggestData .= '^' . $autosuggest['boost'];
				}
				$autosuggestData .= ' OR ';
			}
			return '(' . $autosuggestData. '(' . $relatedData . ')' . $infixData . ')';
		}else{
			return '(' . $relatedData . ')';
		}
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
