<?php

namespace li3_charizard\tests\cases\extensions\data;

use lithium\test\Unit;
use li3_charizard\extensions\data\QueryStringBuilder;

class QueryStringBuilderTest extends Unit {

	public function testStart() {
		$this->assertIdentical('start=10', QueryStringBuilder::startToString(10));
	}

	public function testEmptyStartToString() {
		$this->assertEmpty(QueryStringBuilder::startToString(0));
	}

	public function testRows() {
		$this->assertIdentical('rows=10', QueryStringBuilder::rowsToString(10));
	}

	public function testSelect() {
		$value = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);
		$expected = 'q=foo:bar AND baz:qux';
		$this->assertIdentical($expected, QueryStringBuilder::selectToString($value));
	}

	public function testSelectWithEmptyStringValue() {
		$value = array(
			'foo' => 'bar',
			'baz' => '',
		);
		$expected = 'q=foo:bar';
		$this->assertIdentical($expected, QueryStringBuilder::selectToString($value));
	}

	public function testSelectWithNullValue() {
		$value = array(
			'foo' => 'bar',
			'baz' => null,
		);
		$expected = 'q=foo:bar';
		$this->assertIdentical($expected, QueryStringBuilder::selectToString($value));
	}

	public function testSortToStringSimple() {
		$value = array(
			'foo' => 'asc',
			'baz' => 'desc',
		);
		$expected = 'sort=foo asc, baz desc';
		$this->assertIdentical($expected, QueryStringBuilder::sortToString($value));
	}

	public function testGroupBy() {
		$value = array(
			'foo',
			'bar',
		);
		$expected = 'group=true&' .
			'group.limit=1&' .
			'group.ngroups=true&' .
			'group.cache.percent=0&' .
			'group.truncate=true&' .
			'group.facet=false&' .
			'group.field=foo&' .
			'group.field=bar';
		$this->assertIdentical($expected, QueryStringBuilder::groupByToString($value));
	}

	public function testFieldToStringSimple() {
		$value = array(
			'foo',
			'bar',
			'baz',
		);
		$expected = 'fl=foo,bar,baz';
		$this->assertIdentical($expected, QueryStringBuilder::fieldsToString($value));
	}

	public function testGeoToStringBBoxSimple() {
		$value = array(
			'_distance_sort' => 1,
			'field' => 'foo',
			'latlong' => '36.1537,-95.9926',
			'radius' => 19,
		);
		$expected = 'fq={!bbox pt=36.1537,-95.9926 sfield=foo d=19}';
		$this->assertIdentical($expected, QueryStringBuilder::geoToString($value));
	}

	public function testSuggestions() {
		$value = array(
			'typeahead_field' => 'display_name',
			'typeahead_phrase' => 'foo',
		);
		$config = array(
			'str_fields' => array(
				"display_name" => array(
					"id_field" => "master_id",
					"related" => array(
						array(
							"field" => "name_combo",
							"boost" => 2,
							"append" => true
						),
						array(
							"field" => "first_name",
							"boost" => 5,
							"append" => true
						),
						array(
							"field" => "middle_name",
							"boost" => 3,
							"append" => true
						),
						array(
							"field" => "last_name",
							"boost" => 7,
							"append" => true
						),
						array(
							"field" => "alias_first_name",
							"boost" => 1,
							"append" => true
						),
						array(
							"field" => "alias_middle_name",
							"boost" => 2,
							"append" => true
						),
						array(
							"field" => "alias_last_name",
							"boost" => 3,
							"append" => true
						),
						array(
							"field" => "alias_suffix",
							"boost" => 1,
							"append" => true
						)
					),
					"spell" => array(
						"field" => "name_spell",
						"boost" => 0.1,
						"append" => true,
						"dictionary" => "namespellcheck"
					),
					"autosuggest" => array(
						"dictionary" => "nametypeahead",
						"field" => "name_autosuggest",
						"boost" => 0.1,
						"append" => true
					),
					"infix" => array(
						"field" => "name_autosuggest",
						"boost" => 0.1,
						"append" => true
					)
				),
			),
		);
		$expected = 'q=((name_combo:foo^2 OR first_name:foo^5 OR middle_name:foo^3'.
			' OR last_name:foo^7 OR alias_first_name:foo^1 OR alias_middle_name:foo^2 OR alias_last_name:foo^3'.
			' OR alias_suffix:foo^1) OR name_autosuggest:foo^0.1)';
		$this->assertIdentical($expected, QueryStringBuilder::suggestionsToString($value, $config));
	}

	/**
	 * TODO I added a boost to 'geo_zip_autosuggest' even though nothing was returned.
	 * This did not appear to change results and was different from the test above here.
	 */
	public function testGeoSuggestions() {
		$value = array(
			'typeahead_field' => 'geo_zip_combo',
			'typeahead_phrase' => 'Tul',
		);
		$config = array(
			'str_fields' => array(
				"geo_zip_combo" => array(
					"id_field" => "id",
					"related" => array(
						array(
							"field" => "state",
							"boost" => 4,
							"append" => true,
						),
						array(
							"field" => "city",
							"boost" => 5,
							"append" => true,
						),
						array(
							"field" => "zip",
							"boost" => 3,
							"append" => true,
						),
						array(
							"field" => "state_full",
							"boost" => 3,
							"append" => true,
						),
						array(
							"field" => "geo",
							"boost" => 1,
							"append" => false,
						),
						array(
							"field" => "geo_cc",
							"boost" => 1,
							"append" => false,
						),
					),
					"infix" => array(
						"field" => "geo_zip_autosuggest",
						"boost" => 0.5,
						"append" => true,
					),
				),
			),
		);
		$expected = 'q=((state:Tul^4 OR city:Tul^5 OR zip:Tul^3 OR state_full:Tul^3) OR geo_zip_autosuggest:Tul^0.5)';
		$this->assertIdentical($expected, QueryStringBuilder::suggestionsToString($value, $config));
	}

	public function testSuggestionsWIthoutInfix() {
		$value = array(
			'typeahead_field' => 'geo_zip_combo',
			'typeahead_phrase' => 'Tul',
		);
		$config = array(
			'str_fields' => array(
				"geo_zip_combo" => array(
					"id_field" => "id",
					"related" => array(
						array(
							"field" => "state",
							"boost" => 4,
							"append" => true,
						),
						array(
							"field" => "city",
							"boost" => 5,
							"append" => true,
						),
						array(
							"field" => "zip",
							"boost" => 3,
							"append" => true,
						),
						array(
							"field" => "state_full",
							"boost" => 3,
							"append" => true,
						),
						array(
							"field" => "geo",
							"boost" => 1,
							"append" => false,
						),
						array(
							"field" => "geo_cc",
							"boost" => 1,
							"append" => false,
						),
					),
				),
			),
		);
		$expected = 'q=(state:Tul^4 OR city:Tul^5 OR zip:Tul^3 OR state_full:Tul^3)';
		$this->assertIdentical($expected, QueryStringBuilder::suggestionsToString($value, $config));
	}

	public function testSortToStringWithMultipleArrayValues() {
		$value = array(
			array('foo' => 'asc'),
			array('baz' => 'desc'),
		);
		$expected = 'sort=foo asc, baz desc';
		$this->assertIdentical($expected, QueryStringBuilder::sortToString($value));
	}

	public function testRelatedToString() {
		$this->skipIf(true, 'We have not discovered what this field does.');
	}

	public function testGeoToString() {
		$this->skipIf(true, 'Method has hardcoded values.');
	}

	public function testFilterToString() {
		$this->skipIf(true, 'Method has not yet been implemented');
	}

	public function testFieldsToStringSimple() {
		$expected = 'fl=foo,bar';
		$result = QueryStringBuilder::fieldsToString(array('foo', 'bar'));
		$this->assertIdentical($expected, $result);
	}

	public function testEmptyFieldsToString() {
		$expected = 'fl=';
		$result = QueryStringBuilder::fieldsToString(array());
		$this->assertIdentical($expected, $result);
	}

	public function testSortByGeo() {
		$values = array(
			'geodist(geo,36.1537,-95.9926)' => 'asc',
			'score' => 'desc',
		);
		$expected = 'sort=geodist(geo,36.1537,-95.9926) asc, score desc';
		$result = QueryStringBuilder::sortToString($values);
		$this->assertIdentical($expected, $result);
	}

	public function testBasicFieldFilter() {
		$values = array(
			'field' => array(
				'provider_type_id' => 1,
			),
		);
		$expected = 'fq={!tag=provider_type_id}provider_type_id:1';
		$result = QueryStringBuilder::filterToString($values);
		$this->assertIdentical($expected, $result);
	}

	public function testDoubleFieldFilter() {
		$values = array(
			'field' => array(
				'provider_type_id' => 1,
				'practice_id' => 2,
			),
		);
		$expected = 'fq={!tag=provider_type_id}provider_type_id:1&' .
			'fq={!tag=practice_id}practice_id:2';
		$result = QueryStringBuilder::filterToString($values);
		$this->assertIdentical($expected, $result);
	}
}

?>