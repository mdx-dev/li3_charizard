<?php

namespace li3_charizard\extensions\adapter\data\source\http;

use lithium\data\source\Http;
use lithium\core\Libraries;

class Charizard extends Http {

	protected $_config = array();

	protected $_queryBuilder = null;

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
		$this->_queryBuilder = $this->_instance('query');
	}

	/**
	 * Gets the data from the query builder and returns it.
	 *
	 * @param mixed $query
	 * @param array $options
	 * @return DocumentSet
	 */
	public function read($query, array $options = array()) {
		$data = json_decode($this->connection->get($this->path($query)), true);
		$response = $data['grouped']['master_id']['groups'];
		return $this->item($query->model(), $response, array(
			'class' => 'set',
			'stats' => array(
				'count' => $data['grouped']['master_id']['matches'],
				'matches' => $data['grouped']['master_id']['matches'],
				'ngroups' => $data['grouped']['master_id']['ngroups'],
				'facet_counts' => array(),
				'facets' => array(),
			),
		));
	}

	/**
	 * Generates the http path to query based on the query object.
	 *
	 * @param mixed $query
	 * @return string
	 */
	public function path($query) {
		$source = $query->source();
		$queryBuilder = $this->_queryBuilder->import($query);
		return "{$this->_config['instance']}/{$source['core']}/{$queryBuilder->to('string')}";
	}

	/**
	 * The cast() method is used by the data source to recursively inspect and
	 * transform data as it's placed into a collection. In this case, we'll use
	 * cast() to transform arrays into Document objects.
	 *
	 * @param the query model
	 * @param the request results
	 * @param options ie(set, service, entity)
	 */
	public function cast($entity, array $data, array $options = array()) {
		$model = $entity->model();
		foreach ($data as $key => $val) {
			if (!is_array($val) || empty($val['doclist']['docs'])) { continue; }
			foreach ($val['doclist']['docs'] as $doc) {
				$data[$key] = $this->item($model, $doc, array('class' => 'entity'));
			}
		}
		return parent::cast($entity, $data, $options);
	}

}

?>