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
			$value = $key . ':' . $value;
		}
		return 'q=' . implode(' OR ', $values);
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

	public static function __callStatic($method, $values) {
		// Not yet implemented for debugging
		// throw new BadMethodCallException("Method #{$method} not defined");
		return '__FIXME__';
	}

}

?>