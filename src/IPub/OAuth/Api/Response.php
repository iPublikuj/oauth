<?php
/**
 * Response.php
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

use IPub;
use IPub\OAuth;
use IPub\OAuth\Exceptions;

/**
 * @package		iPublikuj:OAuth!
 * @subpackage	Api
 *
 * @author Filip Procházka <filip@prochazka.su>
 * @author Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Request $request
 * @property-read string $content
 * @property-read int $httpCode
 * @property-read array $headers
 * @property-read array $debugInfo
 */
class Response extends Nette\Object
{
	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var string|array
	 */
	private $content;

	/**
	 * @var string|array
	 */
	private $arrayContent;

	/**
	 * @var int
	 */
	private $httpCode;

	/**
	 * @var array
	 */
	private $headers;

	/**
	 * @var array
	 */
	private $info;

	/**
	 * @param Request $request
	 * @param string $content
	 * @param string $httpCode
	 * @param array $headers
	 * @param array $info
	 */
	public function __construct(Request $request, $content, $httpCode, $headers = [], $info = [])
	{
		$this->request = $request;
		$this->content = $content;
		$this->httpCode = (int) $httpCode;
		$this->headers = $headers;
		$this->info = $info;
	}

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return array|string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @return bool
	 */
	public function isJson()
	{
		$contentType = $this->getHeaderContentType();

		return $contentType !== NULL && preg_match('~^application/json*~is', $contentType);
	}

	/**
	 * @return bool
	 */
	public function isXml()
	{
		$contentType = $this->getHeaderContentType();

		return $contentType !== NULL && preg_match('~^text/xml*~is', $contentType);
	}

	/**
	 * @return bool
	 */
	public function isQueryString()
	{
		$contentType = $this->getHeaderContentType();

		return $contentType !== NULL && (preg_match('~^text/plain*~is', $contentType) || preg_match('~^text/html*~is', $contentType));
	}

	/**
	 * @return string|null
	 */
	private function getHeaderContentType()
	{
		if (isset($this->headers['Content-Type'])) {
			return $this->headers['Content-Type'];

		} else if (isset($this->headers['content-type'])) {
			return $this->headers['content-type'];
		}

		return NULL;
	}

	/**
	 * @return array
	 *
	 * @throws Exceptions\ApiException
	 */
	public function toArray()
	{
		if (is_array($this->arrayContent)) {
			return $this->arrayContent;
		}

		if ($this->isJson()) {
			try {
				return $this->arrayContent = Utils\Json::decode($this->content, Utils\Json::FORCE_ARRAY);

			} catch (Utils\JsonException $jsonException) {
				$ex = new Exceptions\ApiException($jsonException->getMessage() . ($this->content ? "\n\n" . $this->content : ''), $this->httpCode, $jsonException);
				$ex->bindResponse($this->request, $this);
				throw $ex;
			}

		} else if ($this->isXml()) {
			try {
				$xml = simplexml_load_string($this->content);
				$json = json_encode($xml);
				return json_decode($json,TRUE);

			} catch (Utils\JsonException $jsonException) {
				$ex = new Exceptions\ApiException($jsonException->getMessage() . ($this->content ? "\n\n" . $this->content : ''), $this->httpCode, $jsonException);
				$ex->bindResponse($this->request, $this);
				throw $ex;
			}

		} else if ($this->isQueryString()) {
			parse_str($this->content, $result);
			return $this->arrayContent = $result;
		}

		return NULL;
	}

	/**
	 * @return bool
	 */
	public function isPaginated()
	{
		return $this->request->isPaginated();
	}

	/**
	 * @return int
	 */
	public function getHttpCode()
	{
		return $this->httpCode;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @return bool
	 */
	public function isOk()
	{
		return $this->httpCode >= 200 && $this->httpCode < 300;
	}

	/**
	 * @return Exceptions\ApiException|static
	 */
	public function toException()
	{
		$error = isset($this->info['error']) ? $this->info['error'] : NULL;
		$ex = new Exceptions\RequestFailedException(
			$error ? $error['message'] : $this->getContent(),
			$error ? (int) $error['code'] : 0
		);

		if ($this->content && $this->isJson()) {
			$response = $this->toArray();

			if (isset($response['message'])) {
				$ex = new Exceptions\ApiException($response['message'], $response['code'], $ex);
			}
		}

		return $ex->bindResponse($this->request, $this);
	}

	/**
	 * @internal
	 *
	 * @return array
	 */
	public function getDebugInfo()
	{
		return $this->info;
	}
}