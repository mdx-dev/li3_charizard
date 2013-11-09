<?php

namespace li3_charizard\extensions\adapter\data\source\http;

use lithium\data\source\Http;

class Charizard extends Http {

	protected $_config = array();

	protected $_classes = array(
		'service' => 'lithium\net\http\Service',
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
		'array' => 'lithium\data\collection\DocumentArray',
		'relationship' => 'lithium\data\model\Relationship',
		'query' => 'li3_charizard\extensions\adapter\data\QueryBuilder',
	);

	public function __construct(array $config = array()) {
		$defaults = array(
			'host' => 'localhost',
			'port' => 8080,
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * TODO
	 *
	 * {{{
	 * $self->connection->post($path, $querystring, $options);
	 * }}}
	 *
	 * @param mixed $query
	 * @param array $options
	 */
	public function read($query, array $options = array()) {
		$_config = $this->_config;
		$queryBuilder = $this->_classes['query'];
		$params = compact('query', 'options', '_config', 'queryBuilder');
		return $this->_filter(__METHOD__, $params, function($self, $params) {
			$queryBuilder = $params['queryBuilder'];
			$config = $params['_config'];
			$source = $params['query']->source();
			$query = new $queryBuilder(array('query' => $params['query']));
			$path = '/' . $config['instance'] . '/' . $source['core'] . '/' . $query;
			$data = $self->connection->get($path);
			return $self->item($query->model(), $data, array('class' => 'set'));
		});
	}

}

?>