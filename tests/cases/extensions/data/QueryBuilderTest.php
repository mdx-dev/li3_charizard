<?php

namespace li3_charizard\tests\cases\extensions\data;

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
		$this->skipIf(true, 'Our lithium does not have the new "assertNotException"');
		$query = $this->query;
		$this->assertNotException('InvalidArgumentException', function() use($query) {
			$query->to('string');
		});
	}

	public function testImportReturnsThis() {
		$expected = 'li3_charizard\extensions\data\QueryBuilder';
		$this->assertInstanceOf($expected, $this->query->import(array()));
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
		$expected = 'select?wt=json&' .
			'q=name:Urgent&' .
			'start=0&' .
			'rows=10&' .
			'fl=facility_id,name&sort=geodist(geo,40.694599,-73.990638) asc,score desc&' .
			'fq={!bbox pt=40.694599,-73.990638 sfield=geo d=10000}&' .
			'defType=edismax';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	public function testDisorderAcSearch() {
		$data = array(
			'suggestions' => array(
				'typeahead_field' => 'disorder',
				'typeahead_phrase' => 'Gas',
			),
			'related' => array(
				array('field' => 'field_specialty_id'),
				array('field' => 'disorder_id'),
			),
			'sort' => array(
				array('score' => 'desc'),
			),
			'groupby' => array(
				'disorder',
			),
			'rows' => 15,
			'offset' => 0,
		);
		$expected = 'select?wt=json&' .
			'q=( ( ( disorder_id:Gas^1 OR related_disorder:Gas^2 OR field_specialty:Gas^2 OR specialist:Gas^2 OR disorder_id:Gas^1 OR related_disorder:Gas^2 OR field_specialty:Gas^2 OR specialist:Gas^2)) OR disorder_autosuggest:Gas)&' .
			'start=0&' .
			'rows=15&' .
			'fl=disorder,disorder_id,field_specialty_id,specialist_id,related_disorder_id,related_disorder,field_specialty,specialist,disorder_autosuggest&' .
			'sort=score desc&' .
			'defType=edismax&' .
			'spellcheck=true&' .
			'spellcheck.q=Gas&' .
			'spellcheck.build=false&' .
			'spellcheck.dictionary=disorderspellcheck&' .
			'spellcheck.count=10&' .
			'spellcheck.extendedResults=true&' .
			'spellcheck.collate=true&' .
			'spellcheck.collateExtendedResults=true&' .
			'group=true&' .
			'group.field=disorder&' .
			'group.limit=1&' .
			'group.ngroups=true&' .
			'group.cache.percent=0&' .
			'group.truncate=true&' .
			'group.facet=false';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	public function testLocationAcSearch() {
		$data = array(
			'suggestions' => array(
				'typeahead_field' => 'geo_zip_combo',
				'typeahead_phrase' => 'Tul',
			),
			'related' => array(
				array('field' => 'city'),
				array('field' => 'state'),
				array('field' => 'pop'),
				array('field' => 'score'),
			),
			'sort' => array(
				array('pop' => 'desc'),
				array('score' => 'desc'),
			),
			'groupby' => array(
				'city',
				'state',
			),
			'rows' => 10,
			'offset' => 0
		);
		$expected = 'select?wt=json&' .
			'q=( ( ( state:Tul^10 OR city:Tul^10 OR zip:Tul^10 OR state_full:Tul^10 OR state:Tul^10 OR city:Tul^10 OR zip:Tul^10 OR state_full:Tul^10)) OR geo_zip_autosuggest:Tul)&' .
			'start=0&' .
			'rows=10&' .
			'fl=geo_zip_combo,state,city,zip,state_full,geo,geo_cc,geo_zip_autosuggest&' .
			'sort=pop desc,score desc&' .
			'defType=edismax&' .
			'group=true&' .
			'group.field=city&' .
			'group.field=state&' .
			'group.limit=1&' .
			'group.ngroups=true&' .
			'group.cache.percent=0&' .
			'group.truncate=true&' .
			'group.facet=false';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

}

?>