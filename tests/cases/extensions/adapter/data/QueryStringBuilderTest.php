<?php

namespace li3_charizard\tests\cases\extensions\adapter\data;

use lithium\test\Unit;
use li3_charizard\extensions\adapter\data\QueryStringBuilder;

class QueryStringBuilderTest extends Unit {

	public function testStart() {
		$expected = array(
			'key' => 'start',
			'value' => 10,
		);
		$this->assertIdentical($expected, QueryStringBuilder::startToString(10));
	}

	public function testEmptyStartToString() {
		$this->assertEmpty(QueryStringBuilder::startToString(0));
	}

	public function testRows() {
		$expected = array(
			'key' => 'rows',
			'value' => 10,
		);
		$this->assertIdentical($expected, QueryStringBuilder::rowsToString(10));
	}

	public function testSelect() {
		$value = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);
		$expected = array(
			'key' => 'q',
			'value' => 'foo:bar AND baz:qux',
		);
		$this->assertIdentical($expected, QueryStringBuilder::selectToString($value));
	}

	public function testSelectWithEmptyStringValue() {
		$value = array(
			'foo' => 'bar',
			'baz' => '',
		);
		$expected = array(
			'key' => 'q',
			'value' => 'foo:bar',
		);
		$this->assertIdentical($expected, QueryStringBuilder::selectToString($value));
	}

	public function testSelectWithNullValue() {
		$value = array(
			'foo' => 'bar',
			'baz' => null,
		);
		$expected = array(
			'key' => 'q',
			'value' => 'foo:bar',
		);
		$this->assertIdentical($expected, QueryStringBuilder::selectToString($value));
	}

	public function testSortToStringSimple() {
		$value = array(
			'foo' => 'asc',
			'baz' => 'desc',
		);
		$expected = array(
			'key' => 'sort',
			'value' => 'foo asc, baz desc',
		);
		$this->assertIdentical($expected, QueryStringBuilder::sortToString($value));
	}

	public function testGroupBy() {
		$value = array(
			'field' => array('foo', 'bar'),
		);
		$expected = array(
			array('key' => 'group', 'value' => 'true'),
			array('key' => 'group.field', 'value' => 'foo'),
			array('key' => 'group.field', 'value' => 'bar'),
			array('key' => 'group.limit', 'value' => 1),
			array('key' => 'group.ngroups', 'value' => 'true'),
			array('key' => 'group.cache.percent', 'value' => '0'),
			array('key' => 'group.truncate', 'value' => 'true'),
			array('key' => 'group.facet', 'value' => 'false')
		);
		$result = QueryStringBuilder::groupByToString($value);
		$this->assertIdentical(json_encode($expected), json_encode($result));
	}

	public function testFieldToStringSimple() {
		$value = array(
			'foo',
			'bar',
			'baz',
		);
		$expected = array(
			'key' => 'fl',
			'value' => 'foo,bar,baz',
		);
		$this->assertIdentical($expected, QueryStringBuilder::fieldsToString($value));
	}

	public function testGeoToStringBBoxSimple() {
		$value = array(
			'_distance_sort' => 1,
			'field' => 'foo',
			'latlong' => '36.1537,-95.9926',
			'radius' => 19,
		);
		$expected = array(
			'key' => 'fq',
			'value' => '{!bbox pt=36.1537,-95.9926 sfield=foo d=19}',
		);
		$this->assertIdentical($expected, QueryStringBuilder::geoToString($value));
	}

	public function testSuggestions() {
		$value = array(
			'typeahead_field' => 'display_name',
			'typeahead_phrase' => 'foo bar',
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
		$expected = array(
			'key' => 'q',
			'value' => '( name_autosuggest:(+foo +bar)^0.1 OR (name_combo:(+foo +bar)^2 OR first_name:(+foo +bar)^5 OR middle_name:(+foo +bar)^3'.
				' OR last_name:(+foo +bar)^7 OR alias_first_name:(+foo +bar)^1 OR alias_middle_name:(+foo +bar)^2 OR alias_last_name:(+foo +bar)^3'.
				' OR alias_suffix:(+foo +bar)^1) OR name_autosuggest:(+foo +bar)^0.1)',
		);
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
		$expected = array(
			'key' => 'q',
			'value' => '((state:Tul^4 OR city:Tul^5 OR zip:Tul^3 OR state_full:Tul^3) OR geo_zip_autosuggest:Tul^0.5)',
		);
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
		$expected = array(
			'key' => 'q',
			'value' => '(state:Tul^4 OR city:Tul^5 OR zip:Tul^3 OR state_full:Tul^3)',
		);
		$this->assertIdentical($expected, QueryStringBuilder::suggestionsToString($value, $config));
	}

	public function testSortToStringWithMultipleArrayValues() {
		$value = array(
			array('foo' => 'asc'),
			array('baz' => 'desc'),
		);
		$expected = array(
			'key' => 'sort',
			'value' => 'foo asc, baz desc',
		);
		$this->assertIdentical($expected, QueryStringBuilder::sortToString($value));
	}

	public function testFieldsToStringSimple() {
		$expected = array(
			'key' => 'fl',
			'value' => 'foo,bar',
		);
		$result = QueryStringBuilder::fieldsToString(array('foo', 'bar'));
		$this->assertIdentical($expected, $result);
	}

	public function testEmptyFieldsToString() {
		$expected = array(
			'key' => 'fl',
			'value' => '',
		);
		$result = QueryStringBuilder::fieldsToString(array());
		$this->assertIdentical($expected, $result);
	}

	public function testSortByGeo() {
		$values = array(
			'geodist(geo,36.1537,-95.9926)' => 'asc',
			'score' => 'desc',
		);
		$expected = array(
			'key' => 'sort',
			'value' => 'geodist(geo,36.1537,-95.9926) asc, score desc',
		);
		$result = QueryStringBuilder::sortToString($values);
		$this->assertIdentical($expected, $result);
	}

	public function testBasicFieldFilter() {
		$values = array(
			'field' => array(
				'provider_type_id' => 1,
			),
		);
		$expected = array(
			array(
				'key' => 'fq',
				'value' => '{!tag=provider_type_id}provider_type_id:1',
			),
		);
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
		$expected = array(
			array('key' => 'fq', 'value' => '{!tag=provider_type_id}provider_type_id:1'),
			array('key' => 'fq', 'value' => '{!tag=practice_id}practice_id:2'),
		);
		$result = QueryStringBuilder::filterToString($values);
		$this->assertIdentical($expected, $result);
	}

	public function testCompileFlat() {
		$values = array(
			array(
				'key' => 'foo',
				'value' => 'baz',
			),
			array(
				'key' => 'bar',
				'value' => 'quz',
			),
		);
		$this->assertIdentical('foo=baz&bar=quz', QueryStringBuilder::compile($values));
	}

	public function testCompileWithString() {
		$values = array(
			array(
				'key' => 'foo',
				'value' => 'baz',
			),
			'bar=quz',
		);
		$this->assertIdentical('foo=baz&bar=quz', QueryStringBuilder::compile($values));
	}

	public function testCompileMultiDimensional() {
		$values = array(
			array(
				array(
					'key' => 'foo',
					'value' => 'baz',
				),
				array(
					'key' => 'bar',
					'value' => 'quz',
				),
			),
		);
		$this->assertIdentical('foo=baz&bar=quz', QueryStringBuilder::compile($values));
	}

	public function testFacetFields() {
		$values = array(
			'field' => array(
				'foo' => 'foo',
				'label' => 'bar',
			),
		);

		$expected = array(
			'facet' => array(
										'key' => 'facet',
										'value' => 'true',
									),
			'facetFields' => array(
													array(
														'key' => 'facet.field',
														'value' => '{!key=foo}foo'
													),
											 array(
													'key' => 'facet.field',
													'value' => '{!key=label}bar'
												),
										 ),
		);
		$result = QueryStringBuilder::facetToString($values);
		$this->assertIdentical(json_encode($expected), json_encode($result));
	}

	public function testFacetRanges() {
		$values = array(
			'range' => array(
				array(
					'field' => 'experience',
					'start' => 5,
					'end' => 1000,
					'gap' => 5,
					'upper' => null,
					'label' => 'ASE',
				),
				array(
					'field' => 'avg_wait_time',
					'start' => 0,
					'end' => 1000,
					'gap' => 10,
					'upper' => 1,
					'lower' => null,
				),
			),
		);

		$expected = array(
		 'facet' => array(
				'key' => 'facet',
				'value' => 'true',
			 ),
			 'faceRanges' => array(
				array(
					'key' => 'facet.range',
					'value' => '{!key=ASE}experience',
				),
				array(
				 'key' => 'f.experience.facet.range.start',
				 'value' => 5,
				),
				array(
					'key' => 'f.experience.facet.range.end',
					'value' => 1000,
				),
				array(
					'key' => 'f.experience.facet.range.gap',
					'value' => 5,
				 ),
				array(
					'key' => 'f.experience.facet.range.upper',
					'value' => NULL,
				),
				array(
					'key' => 'facet.range', 'value' => '{!key=avg_wait_time}avg_wait_time', ),
				array(
					'key' => 'f.avg_wait_time.facet.range.start',
					'value' => 0,
				),
				array(
					'key' => 'f.avg_wait_time.facet.range.end',
					'value' => 1000,
				),
				array(
					'key' => 'f.avg_wait_time.facet.range.gap',
					'value' => 10,
				),
				array(
					'key' => 'f.avg_wait_time.facet.range.upper',
					'value' => 1,
				),
				array(
					'key' => 'f.avg_wait_time.facet.range.lower',
					'value' => NULL,
				),
			 ),
			);
		$result = QueryStringBuilder::facetToString($values);
		$this->assertIdentical(json_encode($expected), json_encode($result));
	}

	public function testFacetQueries(){
		$values = array(
			'query' => array(
					array(
						'field' => 'experience',
						'value' => 12,
						'label' => 'optional',
					),
					array(
						'field' => 'experience',
						'value' => '[1 TO 12]',
					),
				),
		);

		$expected = array(
			'facet' => array(
				'key' => 'facet',
				'value' => 'true',
			),
			'facetQueries' => array(
				array(
					'key' => 'facet.query',
					'value' => '{!key=optional}experience:12',
				),
				array(
					'key' => 'facet.query',
					'value' => '{!key=experience}experience:[1 TO 12]',
				),
			 ),
			);
		$result = QueryStringBuilder::facetToString($values);
		$this->assertIdentical(json_encode($expected), json_encode($result));
	}

}

?>