<?php

/**
 * Description of Client
 *
 * @author Ezra
 */
class Client {

	const RESPONSE_RAW = 1;
	const RESPONSE_JSON = 2;

	/**
	 * Sends a GET request
	 * @param string $url
	 * @param array $data
	 * @param string $responseType const RESPONSE_* type
	 * @return mixed
	 */
	public static function get($url, array $data, $responseType = self::RESPONSE_HTML) {
		return self::send($url, $data, 'GET', $responseType);
	}

	/**
	 * Sends a POST request
	 * @param string $url
	 * @param array $data
	 * @param string $responseType const RESPONSE_* type
	 * @return mixed
	 */
	public static function post($url, array $data, $responseType = self::RESPONSE_HTML) {
		return self::send($url, $data, 'POST', $responseType);
	}

	/**
	 * Sends a PUT request
	 * @param string $url
	 * @param array $data
	 * @param string $responseType const RESPONSE_* type
	 * @return mixed
	 */
	public static function put($url, array $data, $responseType = self::RESPONSE_HTML) {
		return self::send($url, $data, 'PUT', $responseType);
	}

	/**
	 * Sends a DELETE request
	 * @param string $url
	 * @param array $data
	 * @param string $responseType const RESPONSE_* type
	 * @return mixed
	 */
	public static function delete($url, array $data, $responseType = self::RESPONSE_HTML) {
		return self::send($url, $data, 'DELETE', $responseType);
	}

	/**
	 * Send a request
	 * @param string $url
	 * @param array $data
	 * @param string $method GET | POST | PUT | DELETE
	 * @return mixed
	 */
	private static function send($url, array $data = array(), $method = 'GET',
							  $responseType = self::RESPONSE_HTML) {
		$options = array(
			'http' => array(
				'method' => strtoupper($method),
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'content' => http_build_query($data),
			)
		);
		$context = stream_context_create($options);
		$response = file_get_contents($url, false, $context);
		switch ($responseType) {
			case 1:
				return $response;
			case 2:
				return json_decode($response, true);
		}
	}

}
