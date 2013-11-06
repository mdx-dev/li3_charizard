<?php

namespace li3_charizard\test\cases\extensions\data;

use lithium\test\Unit;
use li3_charizard\extensions\data\QueryPartToString;

class QueryPartToStringTest extends Unit {

	public function testStart() {
		$this->assertIdentical('start=10', QueryPartToString::startToString(10));
	}

	public function testRows() {
		$this->assertIdentical('rows=10', QueryPartToString::rowsToString(10));
	}

	public function testSelect() {
		$value = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);
		$expected = 'q=foo:bar OR baz:qux';
		$this->assertIdentical($expected, QueryPartToString::selectToString($value));
	}

	public function testSortToStringSimple() {
		$value = array(
			'foo' => 'asc',
			'baz' => 'desc',
		);
		$expected = 'sort=foo asc, baz desc';
		$this->assertIdentical($expected, QueryPartToString::sortToString($value));
	}

	public function testSortToStringWithMultipleArrayValues() {
		$value = array(
			array('foo' => 'asc'),
			array('baz' => 'desc'),
		);
		$expected = 'sort=foo asc, baz desc';
		$this->assertIdentical($expected, QueryPartToString::sortToString($value));
	}
}

?>