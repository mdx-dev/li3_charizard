<?php

namespace li3_charizard\extensions\data\source\http;

use lithium\data\Source;

class Charizard extends Source {

	protected $_config = array();

	protected $_classes = array(
		'service' => 'lithium\net\http\Service',
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
		'array' => 'lithium\data\collection\DocumentArray',
		'relationship' => 'lithium\data\model\Relationship',
	);

	public function __construct(array $config = array()) {
		$defaults = array(
			'host' => 'localhost',
			'port' => 8080,
		);
		parent::__construct($config + $defaults);
	}

	public function read($query, array $options = array()) {
		$_config = $this->_config;
		$params = compact('query', 'options', '_config');
		return $this->_filter(__METHOD__, $params, function($self, $params) {
			// stuff here!
			// $self->connection->get($path, $querystring, $options);
			// $self->connection->post($path, $querystring, $options);
			return $self->item($query->model(), $data, array('class' => 'set'));
		}
	}

}

?>