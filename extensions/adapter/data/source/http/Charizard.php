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

	//TODO I imagine this logic is something the model/query should be driving--
	// depending on how varied solr responses are.
	// Or, perhaps this is something that belongs entirely within cast/item?
	public function _parseResponse($response = null) {
		$parsed = array(
			'data'    => array(),
			'options' => array(),
		);
		$response = json_decode($response, true);
		//XXX Is there anything useful we can do with non-array responses?
		if (!is_array($response)) return $parsed;

		//TODO some mechanism for handling other response types.

		// Initial master id grouped response type.
		$parsed['data'] = $response['grouped']['master_id']['groups'];
		$parsed['options']['stats'] = array(
			'count'        => $response['grouped']['master_id']['matches'],
			'facets'       => array(),
			'facet_counts' => array(),
			'matches'      => $response['grouped']['master_id']['matches'],
			'ngroups'      => $response['grouped']['master_id']['ngroups'],
		);

		return $parsed;
	}

	/**
	 * Gets the data from the query builder and returns it.
	 *
	 * @param mixed $query
	 * @param array $options
	 * @return DocumentSet
	 */
	public function read($query, array $options = array()) {
		$raw = $this->connection->get($this->path($query));
		$parsed = $this->_parseResponse($raw);
		$entityOptions = $parsed['options'] + array(
			'class' => 'set',
		);
		return $this->item($query->model(), $parsed['data'], $entityOptions);
	}

	// No exposed query formatting methods.
	public function methods() {
		return array();
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
