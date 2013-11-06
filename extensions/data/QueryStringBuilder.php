<?php

namespace li3_charizard\extensions\data;

use lithium\core\StaticObject;
use BadMethodCallException;

class QueryStringBuilder extends StaticObject {

	public static function startToString($value) {
		return 'start=' . $value;
	}

	public static function rowsToString($value) {
		return 'rows=' . $value;
	}

  public static function selectToString($values) {
    foreach ($values as $key => &$value) {
    	if($key ==  'display_name'){
    		$value = self::ComboKeyValue($key, $value);
    	}else{
       $value = $key . ':' . $value;
      }
    }
    return 'q=' . implode(' OR ', $values);
  }

	public static function suggestionsToString($values){
		return "q=". self::ComboKeyValue($values['typeahead_field'], $values['typeahead_phrase']);
	}

	public static function sortToString($values) {
		foreach ($values as $key => &$value) {
			if (is_numeric($key) && is_array($value)) {
				$key = key($value);
				$value = $value[$key];
			}
			$value = $key . ' '. $value;
		}
		return 'sort=' . implode(', ', $values);
	}

	public static function groupByToString($values) {
		return 'group=true&group.field='.implode('&group.field=', $values).
			'&group.limit=1&group.ngroups=true&group.cache.percent=0&'.
			'group.truncate=true&group.facet=false';
	}

	public static function __callStatic($method, $values) {
		// Not yet implemented for debugging
		// throw new BadMethodCallException("Method #{$method} not defined");
		return '__FIXME__';
	}

	public static function ComboKeyValue($key, $value){
		//I really dislike combining fields like this.
		//In the near future I want to create custom select handlers to handle
		//each type of query so we can combine these fields into 1 field name.
		$_geo_zip_combo = "((state:__VAL__^10 OR city:__VAL__^10 OR zip:__VAL__^10 OR state_full:__VAL__^10) OR geo_zip_autosuggest:__VAL__)";
		$_disorder_combo = "((disorder_id:__VAL__^1 OR related_disorder:__VAL__^2 OR field_specialty:__VAL__^2 OR ".
			"specialist:__VAL__^2 OR disorder_id:__VAL__^1 OR related_disorder:__VAL__^2 OR field_specialty:__VAL__^2 ".
			"OR specialist:__VAL__^2) OR disorder_autosuggest:__VAL__)";
		$_name_combo = "name_autosuggest:__VAL__^0.1 OR (name_combo:__VAL__^2 OR first_name:__VAL__^5 OR middle_name:__VAL__^3".
			" OR last_name:__VAL__^7 OR alias_first_name:__VAL__^1 OR alias_middle_name:__VAL__^2 OR alias_last_name:__VAL__^3 ".
			"OR alias_suffix:__VAL__^1)";


		$template = null;
		switch ($key) {
			case 'geo_zip_combo':  $template = $_geo_zip_combo;  break;
			case 'name_combo':
			case 'display_name':
						     						 $template = $_name_combo;     break;
			case 'disorder': 			 $template = $_disorder_combo; break;
		}

		if($template){
			return str_replace("__VAL__", $value, $template);
		}
	}

}

?>