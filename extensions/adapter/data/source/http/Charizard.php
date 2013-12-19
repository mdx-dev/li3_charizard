<?php

namespace li3_charizard\extensions\adapter\data\source\http;

use lithium\data\model\QueryException;
use lithium\data\source\Http;
use lithium\core\Libraries;

class Charizard extends Http {

	protected $_config = array();

	protected $_queryBuilder = null;

	protected $_classes = array(
		'service' => 'li3_charizard\extensions\net\http\RawService',
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

	public function create($query, array $options = array()) {
		return $this->update($query, $options);
	}

	public function delete($query, array $options = array()) {
		throw new QueryException("Delete operations are not supported by this adapter.");
	}

	protected function _normalizeFacets(&$stats) {
		$facets = array();
		if (isset($stats->facet_counts)) {
			foreach ($stats->facet_counts->facet_fields as $field => $counts) {
				$facets[$field] = array();
				for ($i = 0; $i < sizeof($counts); $i += 2) {
					$facets[$field][$counts[$i]] = $counts[$i+1];
				}
			}
		}
		$stats->facets = $facets;
	}

	public static function objectToArray($obj) {
		if (is_object($obj)) $obj = get_object_vars($obj);
		return is_array($obj) ? array_map(__METHOD__, $obj) : $obj;
	}

	public function _parseResponse($model, $response = null) {
		$parsed = array(
			'data'    => array(),
			'options' => array(),
		);
		//XXX Decoding this as an object to maintain compatibility with any existing
		//    model _normalizeFacet methods.
		$response = json_decode($response);

		if (
			!is_object($response)
			|| empty($response->responseHeader)
		) {
			throw new QueryException('Failed to read Solr response: `' . var_export($response, true) . '`.');
		}

		$responseHeader = $response->responseHeader;
		if (
			!isset($responseHeader->status)
			|| $responseHeader->status !== 0
		) {
			$msg = isset($response->error->msg) ? $response->error->msg : '';
			$code = isset($response->error->code) ? $response->error->code : '';
			throw new QueryException("Solr error: code=`{$code}` msg=`{$msg}`.");
		}

		if (isset($response->grouped)) {
			$names = array_keys(get_object_vars($response->grouped));
			$name = $names[0];
			$stats = $response->grouped->$name;
			$parsed['data'] = $stats->groups;
			unset($stats->groups);
			$stats->count = $stats->ngroups;
		} else {
			$stats = $response->response;
			$parsed['data'] = $stats->docs;
			unset($stats->docs);
			$stats->count = $stats->numFound;
		}

		if (isset($response->facet_counts)) {
			$stats->facet_counts = $response->facet_counts;
			$this->_normalizeFacets($stats);
			// callback on the model
			if (method_exists($model, '_normalizeFacets')) {
				$model::_normalizeFacets($stats);
			}
		} else {
			$stats->facet_counts = array();
			$stats->facets = array();
		}

		$parsed['options']['stats'] = $stats;
		return static::objectToArray($parsed);
	}

	/**
	 * Gets the data from the query builder and returns it.
	 *
	 * @param mixed $query
	 * @param array $options
	 * @return DocumentSet
	 */
	public function read($query, array $options = array()) {
		$response = $this->connection->get($this->path($query));
		$parsed = $this->_parseResponse($query->model(), $response);
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
			if (!is_array($val)) continue;
			if (!empty($val['doclist']['docs'])) {
				//XXX The builder is hard-coded to limit groups to one element,
				//    which is why we only use one doc here.
				//TODO This seems like something that belongs in model-config / queries.
				$data[$key] = $this->item($model, $val['doclist']['docs'][0], array('class' => 'entity'));
			} else {
				$data[$key] = $this->item($model, $val, array('class' => 'entity'));
			}
		}
		return parent::cast($entity, $data, $options);
	}

	public function update($query, array $options = array()) {
		$params = $query->export($this, array('keys'=> array('source', 'data')));
		$data = $params['data'];
		if ($query->entity()) $data = $data['update'];
		if (!array_key_exists('payload', $data)) {
			throw new \InvalidArgumentException('Update missing required `payload`.');
		}
		$path = $this->_config['instance'] . '/' . $params['source'] . '/update';
		$service_opts = array(
			'type' => 'application/json',
		);
		$response = $this->connection->post($path, $data['payload'], $service_opts);
		if (!isset($response['responseHeader']['status'])) return false;
		if (0 !== $response['responseHeader']['status']) return false;
		return true;
	}

}

?>
