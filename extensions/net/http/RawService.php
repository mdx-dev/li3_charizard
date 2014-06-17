<?php

namespace li3_charizard\extensions\net\http;

class RawService extends \lithium\net\http\Service {

	protected $_classes = array(
		'media'    => 'lithium\net\http\Media',
		'request'  => 'li3_charizard\extensions\net\http\RawRequest',
		'response' => 'li3_charizard\extensions\net\http\RawResponse',
	);

}

?>
