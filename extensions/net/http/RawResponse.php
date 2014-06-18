<?php

namespace li3_charizard\extensions\net\http;

class RawResponse extends \lithium\net\http\Response {

	// Per RFC 2397 data urls should be url-escaped or base64 encoded.
	// The base li3 implementation resulted in body's having url-escaped
	// values replaced with their normal character equivalents.
	protected function _httpChunkedDecode($body) {
		if (stripos($this->headers['Transfer-Encoding'], 'chunked') === false) {
			return $body;
		}
		$stream = fopen('data://text/plain,' . urlencode($body), 'r');
		stream_filter_append($stream, 'dechunk');
		return trim(stream_get_contents($stream));
	}

}

?>
