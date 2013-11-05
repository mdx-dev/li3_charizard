<?php

namespace li3_charizard\test\cases\extensions\data;

use lithium\test\Unit;
use li3_charizard\extensions\data\QueryBuilder;

class QueryBuilderTest extends Unit {

	public function setUp() {
		$this->query = new QueryBuilder;
	}

	public function testToDoesntRespondToJson() {
		$query = $this->query;
		$this->assertException('InvalidArgumentException', function() use($query) {
			$query->to('json');
		});
	}

	public function testToDoesRespondToString() {
		$query = $this->query;
		$this->assertNotException('InvalidArgumentException', function() use($query) {
			$query->to('string');
		}
	}

	public function testImportReturnsThis() {
		$expected = 'li3_charizard\extensions\data\QueryBuilder';
		$this->assertInstanceOf($expected, $query->import(array()));
	}

	public function testUrgentCareFacilitySearch() {
		$data = array(
			'select' => array(
				'name' => 'Urgent',
			),
			'fields' => array(
				'facility_id',
			),
			'filter' => array(
				'field' => array(),
				'facet_query' => array(),
			),
			'sort' => array(
				'geodist(geo,36.1537,-95.9926)' => 'asc',
				'score' => 'desc',
			),
			'geo' => array(
				'_distance_sort' => 1,
				'field' => 'geo',
				'latlong' => '36.1537,-95.9926',
				'radius' => 10000,
			),
			'rows' => 10,
			'offset' => 0,
		);
		$expected = 'select?wt=json&' +
			'q=name:Urgent&' +
			'start=0&' +
			'rows=10&' +
			'fl=facility_id,name&sort=geodist(geo,40.694599,-73.990638) asc,score desc&' +
			'fq={!bbox pt=40.694599,-73.990638 sfield=geo d=10000}&' +
			'defType=edismax';
		$this->assertIdentical($expected, $query->import($data)->to('string'));
	}


}

?>