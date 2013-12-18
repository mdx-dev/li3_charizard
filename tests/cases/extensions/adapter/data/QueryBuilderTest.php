<?php

namespace li3_charizard\tests\cases\extensions\adapter\data;

use lithium\test\Unit;
use li3_charizard\extensions\adapter\data\QueryBuilder;

class QueryBuilderTest extends Unit {

	/**
	 * Returns a representation of the $queryString in an array like:
	 *  array(
	 *    array('a_param', 'something'),
	 *    array('another_param', 'another'),
	 *    array('a_param', 'completely different'),
	 *  )
	 * This preserves both the existence of multiple same-named parameters and
	 * the ordering of all parameters.
	 *
	 * @param string $queryString
	 * @param array $options
	 */
	protected function parseQuery($queryString, array $options = array()) {
		$parsed = array();
		foreach (explode('&', $queryString) as $param) {
			list($name, $value) = explode('=', $param);
			$parsed[] = array(urldecode($name), urldecode($value));
		}
		return $parsed;
	}

	/**
	 * opposite of #parseQuery()
	 *
	 * @param array $params
	 * @param array $options
	 * @return mixed query string or false on error.
	 */
	protected function buildQuery(array $params, array $options = array()) {
		$queryParts = array();
		foreach ($params as $param) {
			if (!is_array($param) || 2 !== count($param)) return false;
			if (!isset($param[0]) || !isset($param[1])) return false;
			if (!strlen($param[0])) return false;
			$queryParts[] = urlencode($param[0]) . '=' . urlencode($param[1]);
		}
		return implode('&', $queryParts);
	}

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
				'name',
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
			'q=' . urlencode('name:Urgent') . '&' .
			'fl=' . urlencode('facility_id,name') . '&' .
			'sort=' . urlencode('geodist(geo,36.1537,-95.9926) asc, score desc') . '&' .
			'fq=' . urlencode('{!bbox pt=36.1537,-95.9926 sfield=geo d=10000}') . '&' .
			'rows=' . urlencode('10') .
			'&defType=edismax';
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
					'field' => array('disorder'),
				),
				'rows' => 15,
				'offset' => 0,
			),
			'source' => array(
				'config' => array(
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
			),
		);
		$expected = 'select?wt=json&' .
			'q=' . urlencode('( disorder_autosuggest:Gas^0.1 OR (disorder_id:Gas^1 OR related_disorder:Gas^2 OR field_specialty:Gas^2 OR specialist:Gas^2) OR disorder_autosuggest:Gas)') . '&' .
			'sort=' . urlencode('score desc') . '&' .
			'group=' . urlencode('true') . '&' .
			'group.field=' . urlencode('disorder') . '&' .
			'group.limit=' . urlencode('1') . '&' .
			'group.ngroups=' . urlencode('true') . '&' .
			'group.cache.percent=' . urlencode('0') . '&' .
			'group.truncate=' . urlencode('true') . '&' .
			'group.facet=' . urlencode('false') . '&' .
			'rows=' . urlencode('15') .
			'&defType=edismax';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	public function testLocationAcSearch() {
		$data = array(
			'suggestions' => array(
				'typeahead_field' => 'geo_zip_combo',
				'typeahead_phrase' => 'Tul',
			),
			'fields' => array(
				'geo_zip_combo',
				'state',
				'city',
				'zip',
				'state_full',
				'geo',
				'geo_cc',
				'geo_zip_autosuggest',
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
				'field' => array('state_city'),
			),
			'rows' => 10,
			'offset' => 0,
			'source' => array(
				'config' => array(
					'str_fields' => array(
						"geo_zip_combo" => array(
							"id_field" => "id",
							"related" => array(
								array(
									"field" => "state",
									"boost" => 10,
									"append" => true
								),
								array(
									"field" => "city",
									"boost" => 10,
									"append" => true
								),
								array(
									"field" => "zip",
									"boost" => 10,
									"append" => true
								),
								array(
									"field" => "state_full",
									"boost" => 10,
									"append" => true
								),
								array(
									"field" => "geo",
									"boost" => 10,
									"append" => false
								),
								array(
									"field" => "geo_cc",
									"boost" => 10,
									"append" => false
								)
							),
							"infix" => array(
								"field" => "geo_zip_autosuggest",
								"boost" => 0.5,
								"append" => true
							)
						),
					),
				),
			),
		);
		$expected = 'select?wt=json&' .
			'q=' . urlencode('((state:Tul^10 OR city:Tul^10 OR zip:Tul^10 OR state_full:Tul^10) OR geo_zip_autosuggest:Tul^0.5)') . '&' .
			'fl=' . urlencode('geo_zip_combo,state,city,zip,state_full,geo,geo_cc,geo_zip_autosuggest') . '&' .
			'sort=' . urlencode('pop desc, score desc') . '&' .
			'group=' . urlencode('true') . '&' .
			'group.field=' . urlencode('state_city') . '&' .
			'group.limit=' . urlencode('1') . '&' .
			'group.ngroups=' . urlencode('true') . '&' .
			'group.cache.percent=' . urlencode('0') . '&' .
			'group.truncate=' . urlencode('true') . '&' .
			'group.facet=' . urlencode('false') . '&' .
			'rows=' . urlencode('10') .
			'&defType=edismax';
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
				'name_combo',
				'first_name',
				'middle_name',
				'last_name',
				'alias_first_name',
				'alias_middle_name',
				'alias_last_name',
				'alias_suffix',
				'display_name',
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
				'mincount' => 1,
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
				'field' => array('master_id'),
			),
			'geo' => array(
				'_distance_sort' => 1,
				'field' => 'geo',
				'latlong' => '40.694599,-73.990638',
				'radius' => 10000,
			),
			'rows' => 10,
			'offset' => 0,
			'source' => array(
				'config' => array(
					"str_fields" => array(
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
				),
			),
		);

		$expected = 'select?wt=json&' .
			'q=' . urlencode('( name_autosuggest:Todd^0.1 OR (name_combo:Todd^2 OR first_name:Todd^5 OR middle_name:Todd^3 OR last_name:Todd^7 OR alias_first_name:Todd^1 OR alias_middle_name:Todd^2 OR alias_last_name:Todd^3 OR alias_suffix:Todd^1) OR name_autosuggest:Todd^0.1)') . '&' .
			'fl=' . urlencode('score,provider_id,master_id,geo,provider_practice_id,name_combo,first_name,middle_name,last_name,alias_first_name,alias_middle_name,alias_last_name,alias_suffix,display_name') . '&' .
			'fq=' . urlencode('{!tag=provider_type_id}provider_type_id:1') . '&' .
			'sort=' . urlencode('geodist(geo,40.694599,-73.990638) asc, score desc') . '&' .
			'group=' . urlencode('true') . '&' .
			'group.field=' . urlencode('master_id') . '&' .
			'group.limit=' . urlencode('1') . '&' .
			'group.ngroups=' . urlencode('true') . '&' .
			'group.cache.percent=' . urlencode('0') . '&' .
			'group.truncate=' . urlencode('true') . '&' .
			'group.facet=' . urlencode('false') . '&' .
			'facet=' . urlencode('true') . '&' .
			'facet.field=' . urlencode('{!key=us_educated}us_educated') . '&' .
			'facet.field=' . urlencode('{!key=is_abms_certified}is_abms_certified') . '&' .
			'facet.field=' . urlencode('{!key=is_top_doctor}is_top_doctor') . '&' .
			'facet.field=' . urlencode('{!key=is_patients_choice}is_patients_choice') . '&' .
			'facet.field=' . urlencode('{!key=degree}degree') . '&' .
			'facet.field=' . urlencode('{!key=gender}gender') . '&' .
			'facet.field=' . urlencode('{!key=language_id}language_id') . '&' .
			'facet.range=' . urlencode('{!key=experience}experience') . '&' .
			'f.experience.facet.range.start=' . urlencode('5') . '&' .
			'f.experience.facet.range.end=' . urlencode('1000') . '&' .
			'f.experience.facet.range.gap=' . urlencode('5') . '&' .
			'f.experience.facet.range.upper=&' .
			'facet.range=' . urlencode('{!key=avg_wait_time}avg_wait_time') . '&' .
			'f.avg_wait_time.facet.range.start=' . urlencode('0') . '&' .
			'f.avg_wait_time.facet.range.end=' . urlencode('1000') . '&' .
			'f.avg_wait_time.facet.range.gap=' . urlencode('10') . '&' .
			'f.avg_wait_time.facet.range.upper=' . urlencode('1') . '&' .
			'f.avg_wait_time.facet.range.lower=&' .
			'facet.mincount=' . urlencode('1') . '&' .
			'fq=' . urlencode('{!bbox pt=40.694599,-73.990638 sfield=geo d=10000}') . '' .
			'&rows=' . urlencode('10') .
			'&defType=edismax';

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
				'field' => array('master_id'),
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
			'q=' . urlencode('specialist_id:124 AND standing_code:P AND -provider_id:13539396') . '&' .
			'fl=' . urlencode('score,provider_id,master_id,geo,provider_practice_id') . '&' .
			'fq=' . urlencode('{!tag=provider_type_id}provider_type_id:1') . '&' .
			'sort=' . urlencode('random_1383842845 asc') . '&' .
			'group=' . urlencode('true') . '&' .
			'group.field=' . urlencode('master_id') . '&' .
			'group.limit=' . urlencode('1') . '&' .
			'group.ngroups=' . urlencode('true') . '&' .
			'group.cache.percent=' . urlencode('0') . '&' .
			'group.truncate=' . urlencode('true') . '&' .
			'group.facet=' . urlencode('false') . '&' .
			'fq=' . urlencode('{!bbox pt=40.6689264,-73.9797357 sfield=geo d=48.28032}') . '&' .
			'rows=' . urlencode('6') .
			'&defType=edismax';
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
					'field' => array('master_id'),
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
			'source' => array(
				'config' => array(
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
			),
		);

		$expected = 'select?wt=json&' .
			'q=' . urlencode('{!geofilt score=distance filter=true pt=40.694599,-73.990638 sfield=geo d=10000}') . '&' .
			'fl=' . urlencode('display_name,specialist,gender,city,state,provider_type_id,degree,provider_id,average_ratings,master_name') . '&' .
			'fq=' . urlencode('{!tag=provider_type_id}provider_type_id:1') . '&' .
			'sort=' . urlencode('score asc') . '&' .
			'group=' . urlencode('true') . '&' .
			'group.field=' . urlencode('master_id') . '&' .
			'group.limit=' . urlencode('1') . '&' .
			'group.ngroups=' . urlencode('true') . '&' .
			'group.cache.percent=' . urlencode('0') . '&' .
			'group.truncate=' . urlencode('true') . '&' .
			'group.facet=' . urlencode('false') . '&' .
			'fq=' . urlencode('( name_combo_autosuggest:Todd^0.1 OR (display_name:Todd^2) OR name_combo_autosuggest:Todd^0.1)') . '&' .
			'rows=' . urlencode('7') .
			'&defType=edismax';
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
				'specialist_id',
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
				'mincount' => 1,
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
				'field' => array('master_id'),
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


		$expected = 'select?wt=json&' .
			'q=' . urlencode('specialist_id:137 AND standing_code:P') . '&' .
			'fl=' . urlencode('score,provider_id,master_id,geo,specialist_id,provider_practice_id') . '&' .
			'fq=' . urlencode('{!tag=provider_type_id}provider_type_id:1') . '&' .
			'sort=' . urlencode('physician_algorithm_quality desc, geodist(geo,36.1537,-95.9926) asc') . '&' .
			'group=' . urlencode('true') . '&' .
			'group.field=' . urlencode('master_id') . '&' .
			'group.limit=' . urlencode('1') . '&' .
			'group.ngroups=' . urlencode('true') . '&' .
			'group.cache.percent=' . urlencode('0') . '&' .
			'group.truncate=' . urlencode('true') . '&' .
			'group.facet=' . urlencode('false') . '&' .
			'facet=' . urlencode('true') . '&' .
			'facet.field=' . urlencode('{!key=us_educated}us_educated') . '&' .
			'facet.field=' . urlencode('{!key=is_abms_certified}is_abms_certified') . '&' .
			'facet.field=' . urlencode('{!key=is_top_doctor}is_top_doctor') . '&' .
			'facet.field=' . urlencode('{!key=is_patients_choice}is_patients_choice') . '&' .
			'facet.field=' . urlencode('{!key=degree}degree') . '&' .
			'facet.field=' . urlencode('{!key=gender}gender') . '&' .
			'facet.field=' . urlencode('{!key=language_id}language_id') . '&' .
			'facet.range=' . urlencode('{!key=experience}experience') . '&' .
			'f.experience.facet.range.start=' . urlencode('5') . '&' .
			'f.experience.facet.range.end=' . urlencode('1000') . '&' .
			'f.experience.facet.range.gap=' . urlencode('5') . '&' .
			'f.experience.facet.range.upper=&' .
			'facet.range=' . urlencode('{!key=avg_wait_time}avg_wait_time') . '&' .
			'f.avg_wait_time.facet.range.start=' . urlencode('0') . '&' .
			'f.avg_wait_time.facet.range.end=' . urlencode('1000') . '&' .
			'f.avg_wait_time.facet.range.gap=' . urlencode('10') . '&' .
			'f.avg_wait_time.facet.range.upper=' . urlencode('1') . '&' .
			'f.avg_wait_time.facet.range.lower=&' .
			'facet.mincount=' . urlencode('1') . '&' .
			'fq=' . urlencode('{!bbox pt=36.1537,-95.9926 sfield=geo d=16.09344}') . '&' .
			'rows=' . urlencode('10') .
			'&defType=edismax';

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
				'mincount' => 1,
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
				'field' => array('master_id'),
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
		$expected = 'select?wt=json&' .
			'q=' . urlencode('disorder_id:2483 AND standing_code:P') . '&' .
			'fl=' . urlencode('score,provider_id,master_id,geo,provider_practice_id') . '&' .
			'fq=' . urlencode('{!tag=provider_type_id}provider_type_id:1') . '&' .
			'sort=' . urlencode('score desc, physician_algorithm_quality desc, geodist(geo,36.1537,-95.9926) asc') . '&' .
			'group=' . urlencode('true') . '&' .
			'group.field=' . urlencode('master_id') . '&' .
			'group.limit=' . urlencode('1') . '&' .
			'group.ngroups=' . urlencode('true') . '&' .
			'group.cache.percent=' . urlencode('0') . '&' .
			'group.truncate=' . urlencode('true') . '&' .
			'group.facet=' . urlencode('false') . '&' .
			'facet=' . urlencode('true') . '&' .
			'facet.field=' . urlencode('{!key=us_educated}us_educated') . '&' .
			'facet.field=' . urlencode('{!key=is_abms_certified}is_abms_certified') . '&' .
			'facet.field=' . urlencode('{!key=is_top_doctor}is_top_doctor') . '&' .
			'facet.field=' . urlencode('{!key=is_patients_choice}is_patients_choice') . '&' .
			'facet.field=' . urlencode('{!key=degree}degree') . '&' .
			'facet.field=' . urlencode('{!key=gender}gender') . '&' .
			'facet.field=' . urlencode('{!key=language_id}language_id') . '&' .
			'facet.range=' . urlencode('{!key=experience}experience') . '&' .
			'f.experience.facet.range.start=' . urlencode('5') . '&' .
			'f.experience.facet.range.end=' . urlencode('1000') . '&' .
			'f.experience.facet.range.gap=' . urlencode('5') . '&' .
			'f.experience.facet.range.upper=&' .
			'facet.range=' . urlencode('{!key=avg_wait_time}avg_wait_time') . '&' .
			'f.avg_wait_time.facet.range.start=' . urlencode('0') . '&' .
			'f.avg_wait_time.facet.range.end=' . urlencode('1000') . '&' .
			'f.avg_wait_time.facet.range.gap=' . urlencode('10') . '&' .
			'f.avg_wait_time.facet.range.upper=' . urlencode('1') . '&' .
			'f.avg_wait_time.facet.range.lower=&' .
			'facet.mincount=' . urlencode('1') . '&' .
			'fq=' . urlencode('{!bbox pt=36.1537,-95.9926 sfield=geo d=16.09344}') . '' .
			'&rows=' . urlencode('10') .
			'&defType=edismax';
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	function testProviderSpecialtySearchPage2() {
		$data = array(
			'select' => array(
				'specialist' => '',
				'specialist_id' => 110,
				'standing_code' => 'P',
			),
			'fields' => array(
				'score',
				'provider_id',
				'master_id',
				'geo',
				'specialist_id',
				'standing_code',
				'provider_practice_id',
				'disorder_id',
				'name_combo',
				'first_name',
				'middle_name',
				'last_name',
				'alias_first_name',
				'alias_middle_name',
				'alias_last_name',
				'alias_suffix',
				'display_name',
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
				'mincount' => 1,
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
				'geodist(geo,40.694599,-73.990638)' => 'asc',
			),
			'boost' => array(
				'display_name' => 0.9,
				'expertise' => 0.8,
			),
			'groupby' => array(
				'field' => array('master_id'),
			),
			'geo' => array(
				'_distance_sort' => 1,
				'field' => 'geo',
				'latlong' => '40.694599,-73.990638',
				'radius' => 16.09344,
			),
			'rows' => 10,
			'offset' => 10,
		);

		$expectedParams = array(
			array('wt', 'json'),
			//array('q', 'specialist_id:110 AND  standing_code:P'),
			array('q', 'specialist_id:110 AND standing_code:P'), // remove extra space
			array('fl', 'score,provider_id,master_id,geo,specialist_id,standing_code,provider_practice_id,disorder_id,name_combo,first_name,middle_name,last_name,alias_first_name,alias_middle_name,alias_last_name,alias_suffix,display_name'),
			array('start', '10'),
			array('fq', '{!tag=provider_type_id}provider_type_id:1'),
			//array('sort', 'physician_algorithm_quality desc,geodist(geo,40.694599,-73.990638) asc'),
			array('sort', 'physician_algorithm_quality desc, geodist(geo,40.694599,-73.990638) asc'), // add space after ","
			array('group', 'true'),
			array('group.field', 'master_id'),
			array('group.limit', '1'),
			array('group.ngroups', 'true'),
			array('group.cache.percent', '0'),
			array('group.truncate', 'true'),
			array('group.facet', 'false'),
			array('facet', 'true'),
			//array('facet.missing', 'false'),
			array('facet.field', '{!key=us_educated}us_educated'),
			array('facet.field', '{!key=is_abms_certified}is_abms_certified'),
			array('facet.field', '{!key=is_top_doctor}is_top_doctor'),
			array('facet.field', '{!key=is_patients_choice}is_patients_choice'),
			array('facet.field', '{!key=degree}degree'),
			array('facet.field', '{!key=gender}gender'),
			array('facet.field', '{!key=language_id}language_id'),
			array('facet.range', '{!key=experience}experience'),
			array('f.experience.facet.range.start', '5'),
			array('f.experience.facet.range.end', '1000'),
			array('f.experience.facet.range.gap', '5'),
			array('f.experience.facet.range.upper', ''), // added
			//array('f.experience.facet.range.other', 'none'),
			//array('f.experience.facet.range.include', 'lower'),
			array('facet.range', '{!key=avg_wait_time}avg_wait_time'),
			array('f.avg_wait_time.facet.range.start', '0'),
			array('f.avg_wait_time.facet.range.end', '1000'),
			array('f.avg_wait_time.facet.range.gap', '10'),
			array('f.avg_wait_time.facet.range.upper', '1'), // added
			array('f.avg_wait_time.facet.range.lower', ''), // added
			//array('f.avg_wait_time.facet.range.other', 'none'),
			//array('f.avg_wait_time.facet.range.include', 'upper'),
			array('facet.mincount', '1'),
			array('fq', '{!bbox pt=40.694599,-73.990638 sfield=geo d=16.09344}'),
			array('rows', '10'),
			array('defType', 'edismax'),
		);
		$expected = 'select?' . $this->buildQuery($expectedParams);
		$this->assertIdentical($expected, $this->query->import($data)->to('string'));
	}

	function testCleanMultipleQ(){
		$queryString =  'select?wt=json&' .
			'q=' . urlencode('foo:aaa') . '&' .
			'fq=' . urlencode('bar:bbc') . '&' .
			'sort=' .urlencode('score asc') . '&' .
			'q=' .urlencode('baz:[1 TO *]'). '&' .
			'rows=' . urlencode('17');
		$expected =  'select?wt=json&' .
			'q=' . urlencode('foo:aaa') . '&' .
			'fq=' . urlencode('bar:bbc') . '&' .
			'sort=' .urlencode('score asc') . '&' .
			'fq=' .urlencode('baz:[1 TO *]'). '&' .
			'rows=' . urlencode('17');
		$this->assertIdentical($expected, QueryBuilder::cleanse($queryString));
	}

	function testCleanMultipleQWithGeoHash(){
		$queryString =  'select?wt=json&' .
			'q=' . urlencode('( name_combo_autosuggest:Todd^0.1 OR (display_name:Todd^2) OR name_combo_autosuggest:Todd^0.1)') . '&' .
			'fq=' . urlencode('{!tag=provider_type_id}provider_type_id:1'). '&' .
			'sort=' .urlencode('score asc') . '&' .
			'q=' .urlencode('{!geofilt score=distance filter=true pt=40.694599,-73.990638 sfield=geo d=10000}'). '&' .
			'rows=' . urlencode('7');
		$expected =  'select?wt=json&' .
			'q=' .urlencode('{!geofilt score=distance filter=true pt=40.694599,-73.990638 sfield=geo d=10000}'). '&' .
			'fq=' . urlencode('{!tag=provider_type_id}provider_type_id:1'). '&' .
			'sort=' .urlencode('score asc') . '&' .
			'fq=' . urlencode('( name_combo_autosuggest:Todd^0.1 OR (display_name:Todd^2) OR name_combo_autosuggest:Todd^0.1)') . '&' .
			'rows=' . urlencode('7');
		$this->assertIdentical($expected, QueryBuilder::cleanse($queryString));
	}

	function testCleanFilterExclusions(){
		$queryString = 'select?wt=json&' .
			'q=' . urlencode('foo:aaa') . '&' .
			'fq=' . urlencode('{!tag=bar}bar:bbc') . '&' .
			'fq=' . urlencode('{!tag=gender}gender:1') . '&' .
			'facet=' . urlencode('true') . '&' .
			'facet.field=' . urlencode('{!key=gender}gender') . '&' .
			'facet.field=' . urlencode('{!key=is_patients_choice}is_patients_choice');

		$expected = 'select?wt=json&' .
			'q=' . urlencode('foo:aaa') . '&' .
			'fq=' . urlencode('{!tag=bar}bar:bbc') . '&' .
			'fq=' . urlencode('{!tag=gender}gender:1') . '&' .
			'facet=' . urlencode('true') . '&' .
			'facet.field=' . urlencode('{!key=gender ex=gender}gender'). '&' .
			'facet.field=' . urlencode('{!key=is_patients_choice}is_patients_choice');

			$this->assertIdentical($expected, QueryBuilder::cleanse($queryString));
	}


}

?>
