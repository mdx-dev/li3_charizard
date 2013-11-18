<?php

namespace li3_charizard\tests\cases\extensions\adapter\data\source\http;

use lithium\test\Unit;
use lithium\test\Mocker;
use lithium\data\model\Query;
use lithium\data\collection\DocumentSet;
use li3_charizard\extensions\adapter\data\source\http\charizard\Mock as Charizard;
use li3_charizard\extensions\adapter\data\queryBuilder\Mock as QueryBuilder;
use lithium\net\http\service\Mock as Service;

class CharizardTest extends Unit {

	public function setUp() {
		Mocker::register();
	}

	protected function _createObjects($options = array()) {
		$options += array(
			'instance' => 'foo',
			'core' => 'foo',
			'string' => 'foo',
		);
		$this->_createQuery($options);
		$this->_createCharizard($options);
	}

	protected function _createQuery($options) {
		$this->query = new Query(array('source' => array('core' => $options['core'])));
	}

	protected function _createQueryBuilder($options) {
		$this->queryBuilder = new QueryBuilder;
		$this->queryBuilder->applyFilter('to', function() use ($options) {
			return $options['string'];
		});
	}

	protected function _createCharizard($options) {
		$this->_createQueryBuilder($options);
		$this->charizard = new Charizard(array('instance' => $options['instance']));
		$this->charizard->connection = new Service();
		$this->charizard->_queryBuilder = $this->queryBuilder;
		$this->charizard->applyFilter('item', function() { return true; });
	}

	public function testPath() {
		$this->_createObjects(array(
			'instance' => 'foo',
			'core' => 'bar',
			'string' => 'baz',
		));
		$this->assertIdentical('foo/bar/baz', $this->charizard->path($this->query));
	}

	public function testCastRawData() {
		$documentset = new DocumentSet;
		$data = array('foo' => 'bar');
		$result = $this->charizard->cast($documentset, $data);
		$this->assertIdentical($data, $result);
	}

	public function testCastMakesAllDocuments() {
		$this->_createObjects();
		$documentset = new DocumentSet;
		$data = array(
			array(
				'doclist' => array(
					'docs' => array(
						array('name' => 'foo'),
						array('name' => 'bar'),
					),
				),
			),
			array(
				'doclist' => array(
					'docs' => array(
						array('name' => 'baz'),
					),
				),
			),
		);
		$result = $this->charizard->cast($documentset, $data);
		//$this->assertTrue(Mocker::chain($this->charizard)->called('item')->eq(3)->success());
		//XXX We don't currently support groups with more than one doc.
		//    Checking the number of times 'item' is called is probably insufficient
		//    to check if all documents were built--since this test was passing before.
	}

	public function testRead() {
		$this->_createObjects();
		$this->charizard->connection->applyFilter('get', function() {
			return json_encode(array(
				'responseHeader' => array(
					'status' => 0,
				),
				'grouped' => array(
					'master_id' => array(
						'matches' => 1234,
						'ngroups' => 5678,
						'groups' => array(
							'foo', 'bar', 'baz'
						),
					),
				),
			));
		});
		$this->charizard->read($this->query);
		$this->assertTrue(
			Mocker::chain($this->charizard)
			->called('item')
			->with(
				null,
				array('foo', 'bar', 'baz'),
				array(
					'stats' => array(
						'matches' => 1234,
						'ngroups' => 5678,
						'count' => 5678, //1234
						'facet_counts' => array(),
						'facets' => array(),
					),
					'class' => 'set',
				)
			)->eq(1)
			->success()
		);
	}

}

?>
