<?php

namespace li3_charizard\tests\cases\extensions\adapter\data;

use lithium\test\Unit;
use li3_charizard\extensions\adapter\data\QueryBuilder;

class QueryBuilderTest extends Unit {

	public function setUp() {
		$this->query = $this->createQueryBuilder();
	}

	public function createQueryBuilder($config = array()) {
		$query = new QueryBuilder;
		$query->import($config);
		return $query;
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
		});
	}

	public function testImportReturnsThis() {
		$expected = 'li3_charizard\extensions\adapter\data\QueryBuilder';
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
			'data' => array(
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
			),
			'modelConfig' => array(
				'str_fields' => array(
					"disorder" => array(
						"id_field" => "disorder_id",
						"related" => array(
							array(
								"field" => "disorder_id",
								"boost" => 1,
								"append" => true,
							),
							array(
								"field" => "field_specialty_id",
								"boost" => 0,
								"append" => false,
							),
							array(
								"field" => "specialist_id",
								"boost" => 0,
								"append" => false,
							),
							array(
								"field" => "related_disorder_id",
								"boost" => 2,
								"append" => false,
							),
							array(
								"field" => "related_disorder",
								"boost" => 2,
								"append" => true,
							),
							array(
								"field" => "field_specialty",
								"boost" => 2,
								"append" => true,
							),
							array(
								"field" => "specialist",
								"boost" => 2,
								"append" => true,
							)
						),
						"spell" => array(
							"field" => "disorder_spell",
							"boost" => 0.1,
							"append" => true,
							"dictionary" => "disorderspellcheck",
						),
						"autosuggest" => array(
							"dictionary" => "disordertypeahead",
							"field" => "disorder_autosuggest",
							"boost" => 0.1,
							"append" => true,
						),
						"infix" => array(
							"field" => "disorder_autosuggest",
						),
					),
				),
			),
		);
		$expected = 'select?wt=json&' .
			'q=((disorder_id:Gas^1 OR related_disorder:Gas^2 OR field_specialty:Gas^2 OR specialist:Gas^2) OR disorder_autosuggest:Gas)&' .
			'sort=score desc&' .
			'group=true&' .
			'group.limit=1&' .
			'group.ngroups=true&' .
			'group.cache.percent=0&' .
			'group.truncate=true&' .
			'group.facet=false&' .
			'group.field=disorder&' .
			'rows=15';
		$this->assertIdentical($expected, $this->createQueryBuilder($data)->to('string'));
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

	public function testProviderNameSearch() {
		$data = array(
			'select' => array(
				'display_name' => 'Todd',
			),
			'fields' => array(
				'score',
				'provider_id',
				'master_id',
				'geo',
				'provider_practice_id',
			),
			'facet' => array(
				'field' => array(
					'us_educated' => 'us_educated',
					'is_abms_certified' => 'is_abms_certified',
					'is_top_doctor' => 'is_top_doctor',
					'is_patients_choice' => 'is_patients_choice',
					'degree' => 'degree',
					'gender' => 'gender',
					'language_id' => 'language_id',
				),
				'range' => array(
					array(
						'field' => 'experience',
						'start' => 5,
						'end' => 1000,
						'gap' => 5,
						'upper' => null,
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
			),
			'filter' => array(
				'facet' => array(),
				'field' => array(
					'provider_type_id' => 1,
				),
				'query' => array(),
			),
			'sort' => array(
				'geodist(geo,40.694599,-73.990638)' => 'asc',
				'score' => 'desc',
			),
			'boost' => array(
				'display_name' => 0.9,
				'expertise' => 0.8,
			),
			'groupby' => array(
				'master_id',
			),
			'geo' => array(
				'_distance_sort' => 1,
				'field' => 'geo',
				'latlong' => '40.694599,-73.990638',
				'radius' => 10000,
			),
			'rows' => 10,
			'offset' => 0,
		);
		$expected = 'select?wt=json' .
			'q=(  name_autosuggest:Todd^0.1 OR ( (  name_combo:Todd^2 OR  first_name:Todd^5 OR  middle_name:Todd^3 OR  last_name:Todd^7 OR  alias_first_name:Todd^1 OR  alias_middle_name:Todd^2 OR  alias_last_name:Todd^3 OR  alias_suffix:Todd^1)) OR  display_name:Todd)' .
			'start=0' .
			'rows=10' .
			'fl=score,provider_id,master_id,geo,provider_practice_id,name_combo,first_name,middle_name,last_name,alias_first_name,alias_middle_name,alias_last_name,alias_suffix,display_name' .
			'sort=geodist(geo,40.694599,-73.990638) asc,score desc' .
			'fq={!bbox pt=40.694599,-73.990638 sfield=geo d=10000}' .
			'fq={!tag=provider_type_id}provider_type_id:1' .
			'defType=edismax' .
			'spellcheck=true' .
			'spellcheck.q=Todd' .
			'spellcheck.build=false' .
			'spellcheck.dictionary=namespellcheck' .
			'spellcheck.count=10' .
			'spellcheck.extendedResults=true' .
			'spellcheck.collate=true' .
			'spellcheck.collateExtendedResults=true' .
			'facet=true' .
			'facet.missing=false' .
			'facet.mincount=1' .
			'facet.field={!key=us_educated}us_educated' .
			'facet.field={!key=is_abms_certified}is_abms_certified' .
			'facet.field={!key=is_top_doctor}is_top_doctor' .
			'facet.field={!key=is_patients_choice}is_patients_choice' .
			'facet.field={!key=degree}degree' .
			'facet.field={!key=gender}gender' .
			'facet.field={!key=language_id}language_id' .
			'facet.range={!key=experience}experience' .
			'facet.range={!key=avg_wait_time}avg_wait_time' .
			'f.experience.facet.range.start=5' .
			'f.experience.facet.range.end=1000' .
			'f.experience.facet.range.gap=5' .
			'f.experience.facet.range.other=none' .
			'f.experience.facet.range.include=lower' .
			'f.avg_wait_time.facet.range.start=0' .
			'f.avg_wait_time.facet.range.end=1000' .
			'f.avg_wait_time.facet.range.gap=10' .
			'f.avg_wait_time.facet.range.other=none' .
			'f.avg_wait_time.facet.range.include=upper' .
			'group=true' .
			'group.field=master_id' .
			'group.limit=1' .
			'group.ngroups=true' .
			'group.cache.percent=0' .
			'group.truncate=true' .
			'group.facet=false';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	public function testSimilarProviders() {
		$data = array(
			'select' => array(
				'specialist' => '',
				'specialist_id' => 124,
				'standing_code' => 'P',
				'-provider_id' => 13539396,
			),
			'fields' => array(
				'score',
				'provider_id',
				'master_id',
				'geo',
				'provider_practice_id',
			),
			'filter' => array(
				'facet' => array(
				),
				'field' => array(
					'provider_type_id' => 1,
				),
				'query' => array(
				),
			),
			'sort' => array(
				'random_1383842845' => 'asc',
			),
			'groupby' => array(
				'master_id',
			),
			'geo' => array(
				'_distance_sort' => 1,
				'field' => 'geo',
				'latlong' => '40.6689264,-73.9797357',
				'radius' => 48.28032,
			),
			'rows' => 6,
			'offset' => 0,
		);
		$expected = 'select?wt=json&' .
			'q=specialist_id:124 AND standing_code:P AND -provider_id:13539396&fl=score,provider_id,master_id,geo,provider_practice_id&' .
			'fq={!tag=provider_type_id}provider_type_id:1&' .
			'sort=random_1383842845 asc&' .
			'group=true&' .
			'group.limit=1&' .
			'group.ngroups=true&' .
			'group.cache.percent=0&' .
			'group.truncate=true&' .
			'group.facet=false&' .
			'group.field=master_id&' .
			'fq={!bbox pt=40.6689264,-73.9797357 sfield=geo d=48.28032}&' .
			'rows=6';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	public function testProviderAutoComplete() {
		$data = array(
			'data' => array(
				'suggestions' => array(
					'typeahead_field' => 'name_combo',
					'typeahead_phrase' => 'Todd',
					'related' => array(
						array(
							'field' => 'display_name',
						),
						array(
							'field' => 'specialist',
						),
						array(
							'field' => 'gender',
						),
						array(
							'field' => 'city',
						),
						array(
							'field' => 'state',
						),
						array(
							'field' => 'provider_type_id',
						),
						array(
							'field' => 'degree',
						),
						array(
							'field' => 'provider_id',
						),
						array(
							'field' => 'average_ratings',
						),
						array(
							'field' => 'master_name',
						),
					),
				),
				'fields' => array(
					'display_name',
					'specialist',
					'gender',
					'city',
					'state',
					'provider_type_id',
					'degree',
					'provider_id',
					'average_ratings',
					'master_name',
				),
				'filter' => array(
					'facet' => array(
					),
					'field' => array(
						'provider_type_id' => 1,
					),
					'query' => array(
					),
				),
				'sort' => array(
					'_distance_sort' => 'asc',
				),
				'groupby' => array(
					'master_id',
				),
				'geo' => array(
					'_distance_sort' => 'hash',
					'field' => 'geo',
					'latlong' => '40.694599,-73.990638',
					'radius' => 10000,
				),
				'rows' => 7,
				'offset' => 0,
			),
			'modelConfig' => array(
				"str_fields" => array(
					"display_name" => array(
						"id_field" => "master_id",
						"related" => array(
							array(
								"field" => "name_combo",
								"boost" => 2,
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
					"name_combo" => array(
						"id_field" => "master_id",
						"related" => array(
							array(
								"field" => "display_name",
								"boost" => 2,
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
							"field" => "name_combo_autosuggest",
							"boost" => 0.1,
							"append" => true
						),
						"infix" => array(
							"field" => "name_combo_autosuggest",
							"boost" => 0.1,
							"append" => true
						)
					)
				)
			),
		);


		$expected = 'select?wt=json' .
			'&q={!geofilt score=distance filter=true pt=40.694599,-73.990638 sfield=geo d=10000}' .
			'&fl=display_name,specialist,gender,city,state,provider_type_id,degree,provider_id,average_ratings,master_name' .
			'&fq={!tag=provider_type_id}provider_type_id:1' .
			'&sort=score asc' .
			'&group=true&group.limit=1&group.ngroups=true&group.cache.percent=0&group.truncate=true&group.facet=false&group.field=master_id' .
			'&fq=((display_name:Todd^2) OR name_combo_autosuggest:Todd^0.1)' .
			'&rows=7';
		$this->assertIdentical($expected, $this->createQueryBuilder($data)->to('string'));
	}

	public function testProviderSpecialtySearch() {
		$data = array(
			'select' => array(
				'specialist' => '',
				'specialist_id' => 137,
				'standing_code' => 'P',
			),
			'fields' => array(
				'score',
				'provider_id',
				'master_id',
				'geo',
				'provider_practice_id',
			),
			'facet' => array(
				'field' => array(
					'us_educated' => 'us_educated',
					'is_abms_certified' => 'is_abms_certified',
					'is_top_doctor' => 'is_top_doctor',
					'is_patients_choice' => 'is_patients_choice',
					'degree' => 'degree',
					'gender' => 'gender',
					'language_id' => 'language_id',
				),
				'range' => array(
					array(
						'field' => 'experience',
						'start' => 5,
						'end' => 1000,
						'gap' => 5,
						'upper' => null,
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
			),
			'filter' => array(
				'facet' => array(),
				'field' => array(
					'provider_type_id' => 1,
				),
				'query' => array(),
			),
			'sort' => array(
				'physician_algorithm_quality' => 'desc',
				'geodist(geo,36.1537,-95.9926)' => 'asc',
			),
			'boost' => array(
				'display_name' => 0.9,
				'expertise' => 0.8,
			),
			'groupby' => array(
				'master_id',
			),
			'geo' => array(
				'_distance_sort' => 1,
				'field' => 'geo',
				'latlong' => '36.1537,-95.9926',
				'radius' => 16.09344,
			),
			'rows' => 10,
			'offset' => 0,
		);
		$expected = 'select?wt=json' .
			'q=specialist_id:137 AND  standing_code:P' .
			'start=0' .
			'rows=10' .
			'fl=score,provider_id,master_id,geo,provider_practice_id,specialist_id,standing_code' .
			'sort=physician_algorithm_quality desc,geodist(geo,36.1537,-95.9926) asc' .
			'fq={!bbox pt=36.1537,-95.9926 sfield=geo d=16.09344}' .
			'fq={!tag=provider_type_id}provider_type_id:1' .
			'defType=edismax' .
			'facet=true' .
			'facet.missing=false' .
			'facet.mincount=1' .
			'facet.field={!key=us_educated}us_educated' .
			'facet.field={!key=is_abms_certified}is_abms_certified' .
			'facet.field={!key=is_top_doctor}is_top_doctor' .
			'facet.field={!key=is_patients_choice}is_patients_choice' .
			'facet.field={!key=degree}degree' .
			'facet.field={!key=gender}gender' .
			'facet.field={!key=language_id}language_id' .
			'facet.range={!key=experience}experience' .
			'facet.range={!key=avg_wait_time}avg_wait_time' .
			'f.experience.facet.range.start=5' .
			'f.experience.facet.range.end=1000' .
			'f.experience.facet.range.gap=5' .
			'f.experience.facet.range.other=none' .
			'f.experience.facet.range.include=lower' .
			'f.avg_wait_time.facet.range.start=0' .
			'f.avg_wait_time.facet.range.end=1000' .
			'f.avg_wait_time.facet.range.gap=10' .
			'f.avg_wait_time.facet.range.other=none' .
			'f.avg_wait_time.facet.range.include=upper' .
			'group=true' .
			'group.field=master_id' .
			'group.limit=1' .
			'group.ngroups=true' .
			'group.cache.percent=0' .
			'group.truncate=true' .
			'group.facet=false';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	public function testProviderDisorderSearch() {
		$data = array(
			'select' => array(
				'disorder_id' => 2483,
				'standing_code' => 'P',
			),
			'fields' => array(
				'score',
				'provider_id',
				'master_id',
				'geo',
				'provider_practice_id',
			),
			'facet' => array(
				'field' => array(
					'us_educated' => 'us_educated',
					'is_abms_certified' => 'is_abms_certified',
					'is_top_doctor' => 'is_top_doctor',
					'is_patients_choice' => 'is_patients_choice',
					'degree' => 'degree',
					'gender' => 'gender',
					'language_id' => 'language_id',
				),
				'range' => array(
					array(
						'field' => 'experience',
						'start' => 5,
						'end' => 1000,
						'gap' => 5,
						'upper' => null,
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
			),
			'filter' => array(
				'facet' => array(),
				'field' => array(
					'provider_type_id' => 1,
				),
				'query' => array(),
			),
			'sort' => array(
				'score' => 'desc',
				'physician_algorithm_quality' => 'desc',
				'geodist(geo,36.1537,-95.9926)' => 'asc',
			),
			'boost' => array(
				'display_name' => 0.9,
				'expertise' => 0.8,
			),
			'groupby' => array(
				'master_id',
			),
			'geo' => array(
				'_distance_sort' => 1,
				'field' => 'geo',
				'latlong' => '36.1537,-95.9926',
				'radius' => 16.09344,
			),
			'rows' => 10,
			'offset' => 0,
		);
		$expected = 'select?wt=json' .
			'q=disorder_id:2483 AND  standing_code:P' .
			'start=0' .
			'rows=10' .
			'fl=score,provider_id,master_id,geo,provider_practice_id,disorder_id,standing_code' .
			'sort=score desc,physician_algorithm_quality desc,geodist(geo,36.1537,-95.9926) asc' .
			'fq={!bbox pt=36.1537,-95.9926 sfield=geo d=16.09344}' .
			'fq={!tag=provider_type_id}provider_type_id:1' .
			'defType=edismax' .
			'facet=true' .
			'facet.missing=false' .
			'facet.mincount=1' .
			'facet.field={!key=us_educated}us_educated' .
			'facet.field={!key=is_abms_certified}is_abms_certified' .
			'facet.field={!key=is_top_doctor}is_top_doctor' .
			'facet.field={!key=is_patients_choice}is_patients_choice' .
			'facet.field={!key=degree}degree' .
			'facet.field={!key=gender}gender' .
			'facet.field={!key=language_id}language_id' .
			'facet.range={!key=experience}experience' .
			'facet.range={!key=avg_wait_time}avg_wait_time' .
			'f.experience.facet.range.start=5' .
			'f.experience.facet.range.end=1000' .
			'f.experience.facet.range.gap=5' .
			'f.experience.facet.range.other=none' .
			'f.experience.facet.range.include=lower' .
			'f.avg_wait_time.facet.range.start=0' .
			'f.avg_wait_time.facet.range.end=1000' .
			'f.avg_wait_time.facet.range.gap=10' .
			'f.avg_wait_time.facet.range.other=none' .
			'f.avg_wait_time.facet.range.include=upper' .
			'group=true' .
			'group.field=master_id' .
			'group.limit=1' .
			'group.ngroups=true' .
			'group.cache.percent=0' .
			'group.truncate=true' .
			'group.facet=false';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

}

?>