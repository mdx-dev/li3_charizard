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

}

?>