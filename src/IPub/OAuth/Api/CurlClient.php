<?php
/**
 * CurlClient.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:OAuth!
 * @subpackage	Api
 * @since		5.0
 *
 * @date		02.03.15
 */

namespace IPub\OAuth\Api;

use Nette;
use Nette\Utils;

use Tracy\Debugger;

use Kdyby\CurlCaBundle;

use IPub;
use IPub\OAuth;
use IPub\OAuth\Signature;
use IPub\OAuth\Exceptions;

if (!defined('CURLE_SSL_CACERT_BADFILE')) {
	define('CURLE_SSL_CACERT_BADFILE', 77);
}

/**
 * @package		iPublikuj:OAuth!
 * @subpackage	Api
 *
 * @author Filip Procházka <filip@prochazka.su>
 * @author Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method onRequest(Request $request, $options)
 * @method onError(Exceptions\IException $ex, Response $response)
 * @method onSuccess(Response $response)
 */
class CurlClient extends Nette\Object implements OAuth\HttpClient
{
	/**
	 * Default options for curl
	 *
	 * @var array
	 */
	public static $defaultCurlOptions = [
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_USERAGENT => 'ipub-oauth-php',
		CURLOPT_HTTPHEADER => [
			'Accept' => '*/*',
		],
		CURLINFO_HEADER_OUT => TRUE,
		CURLOPT_HEADER => TRUE,
		CURLOPT_AUTOREFERER => TRUE,
		CURLOPT_SSL_VERIFYPEER => TRUE,
		CURLOPT_SSL_VERIFYHOST => 2,
	];

	/**
	 * Options for curl
	 *
	 * @var array
	 */
	public $curlOptions = [];

	/**
	 * @var array of function(Request $request, $options)
	 */
	public $onRequest = [];

	/**
	 * @var array of function(Exceptions\IException $ex, Response $response)
	 */
	public $onError = [];

	/**
	 * @var array of function(Response $response)
	 */
	public $onSuccess = [];

	/**
	 * @var array
	 */
	private $memoryCache = [];

	/**
	 * @var Signature\SignatureMethod[]
	 */
	private $signatureMethods = [];

	public function __construct()
	{
		$this->curlOptions = self::$defaultCurlOptions;
	}

	/**
	 * @param Signature\SignatureMethod $method
	 *
	 * @return $this
	 */
	public function registerSignatureMethod(Signature\SignatureMethod $method)
	{
		$this->signatureMethods[$method->getName()] = $method;

		return $this;
	}

	/**
	 * @param Signature\SignatureMethod $method
	 *
	 * @return $this
	 */
	public function setSignatureMethod(Signature\SignatureMethod $method)
	{
		$this->signatureMethods[$method->getName()] = $method;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return Signature\SignatureMethod|null
	 */
	public function getSignatureMethod($name)
	{
		$name = (string) $name;

		if (isset($this->signatureMethods[$name])) {
			return $this->signatureMethods[$name];
		}

		return NULL;
	}

	/**
	 * Makes an HTTP request. This method can be overridden by subclasses if
	 * developers want to do fancier things or use something other than curl to
	 * make the request.
	 *
	 * @param Request $request
	 * @param string $signatureMethodName
	 *
	 * @return Response
	 *
	 * @throws Exceptions\ApiException
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function makeRequest(Request $request, $signatureMethodName = 'PLAINTEXT')
	{
		if (!$signatureMethod = $this->getSignatureMethod($signatureMethodName)) {
			throw new Exceptions\InvalidArgumentException("Signature method '$signatureMethodName' was not found. Please provide valid signature method name");
		}

		if (isset($this->memoryCache[$cacheKey = md5(serialize($request))])) {
			return $this->memoryCache[$cacheKey];
		}

		// Sign request with selected method
		$request->signRequest($signatureMethod);

		$ch = $this->buildCurlResource($request);

		$result = curl_exec($ch);
		// provide certificate if needed
		if (curl_errno($ch) == CURLE_SSL_CACERT || curl_errno($ch) === CURLE_SSL_CACERT_BADFILE) {
			Debugger::log('Invalid or no certificate authority found, using bundled information', 'oauth');
			$this->curlOptions[CURLOPT_CAINFO] = CurlCaBundle\CertificateHelper::getCaInfoFile();
			curl_setopt($ch, CURLOPT_CAINFO, CurlCaBundle\CertificateHelper::getCaInfoFile());
			$result = curl_exec($ch);
		}

		// With dual stacked DNS responses, it's possible for a server to
		// have IPv6 enabled but not have IPv6 connectivity.  If this is
		// the case, curl will try IPv4 first and if that fails, then it will
		// fall back to IPv6 and the error EHOSTUNREACH is returned by the operating system.
		if ($result === FALSE && (!isset($this->curlOptions[CURLOPT_IPRESOLVE]) || empty($this->curlOptions[CURLOPT_IPRESOLVE]))) {
			$matches = [];
			if (preg_match('/Failed to connect to ([^:].*): Network is unreachable/', curl_error($ch), $matches)) {
				if (strlen(@inet_pton($matches[1])) === 16) {
					Debugger::log('Invalid IPv6 configuration on server, Please disable or get native IPv6 on your server.', 'oauth');
					$this->curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
					curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
					$result = curl_exec($ch);
				}
			}
		}

		$info = $this->getRequestInfo($ch, $request, $result);

		if (isset($info['request_header'])) {
			$request->setHeaders($info['request_header']);
		}

		$response = new Response($request, substr($result, $info['header_size']), $info['http_code'], end($info['headers']), $info);

		if (!$response->isOk()) {
			$e = $response->toException();
			curl_close($ch);
			$this->onError($e, $response);
			throw $e;
		}

		$this->onSuccess($response);
		curl_close($ch);

		return $this->memoryCache[$cacheKey] = $response;
	}

	/**
	 * @param Request $request
	 *
	 * @return resource
	 */
	protected function buildCurlResource(Request $request)
	{
		$ch = curl_init((string) $request->getUrl());

		$options = $this->curlOptions;
		$options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();

		// configuring a POST request
		if ($request->isPost() && $request->getPost()) {
			$options[CURLOPT_POSTFIELDS] = $request->getPost();
		}

		if ($request->isHead()) {
			$options[CURLOPT_NOBODY] = TRUE;

		} else if ($request->isGet()) {
			$options[CURLOPT_HTTPGET] = TRUE;
		}

		// disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
		// for 2 seconds if the server does not support this header.
		$options[CURLOPT_HTTPHEADER]['Expect'] = '';
		$tmp = [];
		foreach ($request->getHeaders() + $options[CURLOPT_HTTPHEADER] as $name => $value) {
			$tmp[] = trim("$name: $value");
		}
		$options[CURLOPT_HTTPHEADER] = $tmp;

		// execute request
		curl_setopt_array($ch, $options);
		$this->onRequest($request, $options);

		return $ch;
	}

	private static function parseHeaders($raw)
	{
		$headers = [];

		// Split the string on every "double" new line.
		foreach (explode("\r\n\r\n", $raw) as $index => $block) {
			// Loop of response headers. The "count() -1" is to
			//avoid an empty row for the extra line break before the body of the response.
			foreach (Utils\Strings::split(trim($block), '~[\r\n]+~') as $i => $line) {
				if (preg_match('~^([a-z-]+\\:)(.*)$~is', $line)) {
					list($key, $val) = explode(': ', $line, 2);
					$headers[$index][$key] = $val;

				} else if (!empty($line)) {
					$headers[$index][] = $line;
				}
			}
		}

		return $headers;
	}

	/**
	 * @param $ch
	 * @param Request $request
	 * @param $result
	 *
	 * @return array
	 */
	private function getRequestInfo($ch, Request $request, $result)
	{
		$info = curl_getinfo($ch);
		$info['http_code'] = (int) $info['http_code'];
		if (isset($info['request_header'])) {
			list($info['request_header']) = self::parseHeaders($info['request_header']);
		}
		$info['method'] = $request->getMethod() ? $request->getMethod() : 'GET';
		$info['headers'] = self::parseHeaders(substr($result, 0, $info['header_size']));
		$info['error'] = $result === FALSE ? ['message' => curl_error($ch), 'code' => curl_errno($ch)] : [];

		return $info;
	}
}
