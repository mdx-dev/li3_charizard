<?php

namespace li3_charizard\test\cases\extensions\data;

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
		$expected = 'q=foo:bar OR baz:qux';
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
		$expected = 'group=true&group.field=foo&group.field=bar&group.limit=1'.
			'&group.ngroups=true&group.cache.percent=0&group.truncate=true&group.facet=false';
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

		$expected = 'q=name_autosuggest:foo^0.1 OR (name_combo:foo^2 OR first_name:foo^5 OR middle_name:foo^3'.
			' OR last_name:foo^7 OR alias_first_name:foo^1 OR alias_middle_name:foo^2 OR alias_last_name:foo^3'.
			' OR alias_suffix:foo^1)';
		$this->assertIdentical($expected, QueryStringBuilder::suggestionsToString($value));
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

}

?>