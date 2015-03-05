<?php
/**
 * Request.php
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
use Nette\Http;
use Nette\Utils;

use IPub;
use IPub\OAuth;
use IPub\OAuth\Signature;

/**
 * @package		iPublikuj:OAuth!
 * @subpackage	Api
 *
 * @author Filip Procházka <filip@prochazka.su>
 * @author Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Request extends Nette\Object
{
	const GET = 'GET';
	const HEAD = 'HEAD';
	const POST = 'POST';
	const PATCH = 'PATCH';
	const PUT = 'PUT';
	const DELETE = 'DELETE';

	/**
	 * @var Http\Url
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var array
	 */
	protected $post = [];

	/**
	 * @var array
	 */
	protected $headers = [];

	/**
	 * @var OAuth\Consumer
	 */
	protected $consumer;

	/**
	 * @var OAuth\Token
	 */
	protected $token;

	public function __construct(OAuth\Consumer $consumer, Http\Url $url, $method = self::GET, array $post = [], array $headers = [], OAuth\Token $token = NULL)
	{
		$this->consumer = $consumer;
		$this->token = $token;

		$this->url = $url;
		$this->method = strtoupper($method);
		$this->headers = $headers;

		if (is_array($post) && !empty($post)) {
			$this->post = array_map(function($value) {
				if ($value instanceof Http\UrlScript) {
					return (string) $value;

				} elseif ($value instanceof \CURLFile) {
					return $value;
				}

				return !is_string($value) ? Utils\Json::encode($value) : $value;
			}, $post);
		}

		$parameters = $this->getParameters();
		$defaults = [
			'oauth_version' => OAuth\DI\OAuthExtension::VERSION,
			'oauth_nonce' => $this->generateNonce(),
			'oauth_timestamp' => $this->generateTimestamp(),
			'oauth_consumer_key' => $this->consumer->getKey(),
		];

		if ($token && $token->getToken()) {
			$defaults['oauth_token'] = $this->token->getToken();
		}

		// Update query parameters
		$this->url->setQuery(array_merge($defaults, $parameters));
	}

	/**
	 * @return Http\Url
	 */
	public function getUrl()
	{
		return clone $this->url;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		parse_str($this->url->getQuery(), $params);

		return $params;
	}

	/**
	 * @return bool
	 */
	public function isPaginated()
	{
		$params = $this->getParameters();

		return $this->isGet() && (isset($params['per_page']) || isset($params['page']));
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @return bool
	 */
	public function isGet()
	{
		return $this->method === self::GET;
	}

	/**
	 * @return bool
	 */
	public function isHead()
	{
		return $this->method === self::HEAD;
	}

	/**
	 * @return bool
	 */
	public function isPost()
	{
		return $this->method === self::POST;
	}

	/**
	 * @return bool
	 */
	public function isPut()
	{
		return $this->method === self::PUT;
	}

	/**
	 * @return bool
	 */
	public function isPatch()
	{
		return $this->method === self::PATCH;
	}

	/**
	 * @return bool
	 */
	public function isDelete()
	{
		return $this->method === self::DELETE;
	}

	/**
	 * @return array|string|NULL
	 */
	public function getPost()
	{
		return $this->post;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		if ($this->url->getQueryParameter('oauth_token', FALSE) && !$this->url->getQueryParameter('oauth_verifier', FALSE)) {
			$parameters = $this->getParameters();
			ksort($parameters, SORT_STRING);
			$authHeader = NULL;

			foreach ($parameters as $key => $value) {
				if (strpos($key, 'oauth_') !== FALSE) {
					$authHeader .= ' ' . $key . '="' . $value . '",';
				}
			}

			if ($authHeader) {
				$this->headers['Authorization'] = 'OAuth ' . trim(rtrim($authHeader, ','));
			}
		}

		return $this->headers;
	}

	/**
	 * @param array|string $post
	 *
	 * @return $this
	 */
	public function setPost($post)
	{
		$this->post = $post;

		return $this;
	}

	/**
	 * @param array $headers
	 *
	 * @return $this
	 */
	public function setHeaders(array $headers)
	{
		$this->headers = $headers;

		return $this;
	}

	/**
	 * @param string $header
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setHeader($header, $value)
	{
		$this->headers[$header] = $value;

		return $this;
	}

	/**
	 * Returns the base string of this request
	 *
	 * The base string defined as the method, the url
	 * and the parameters (normalized), each urlencoded
	 * and the concated with &
	 */
	public function getSignatureBaseString()
	{
		$parts = [
			$this->method,
			$this->url->hostUrl . $this->url->path,
			$this->getSignableParameters()
		];

		return implode('&', OAuth\Utils\Url::urlEncodeRFC3986($parts));
	}

	/**
	 * The request parameters, sorted and
	 * concatenated into a normalized string
	 *
	 * @return string
	 */
	abstract public function getSignableParameters();

	/**
	 * @param Http\UrlScript $url
	 *
	 * @return $this
	 */
	public function copyWithUrl($url)
	{
		$headers = $this->headers;
		array_shift($headers); // drop info about HTTP version

		return new static($this->consumer, new Http\Url($url), $this->getMethod(), $this->post, $headers);
	}

	/**
	 * Current timestamp
	 *
	 * @return int
	 */
	private function generateTimestamp()
	{
		return time();
	}

	/**
	 * Current nonce
	 *
	 * @return string
	 */
	private function generateNonce()
	{
		$mt = microtime();
		$rand = mt_rand();

		return md5($mt . $rand); // md5s look nicer than numbers
	}

	/**
	 * Sign current request
	 *
	 * @param Signature\SignatureMethod $method
	 *
	 * @return $this
	 */
	public function signRequest(Signature\SignatureMethod $method)
	{
		$this->url->setQueryParameter('oauth_signature_method', $method->getName());

		$signature = $method->buildSignature($this->getSignatureBaseString(), $this->consumer, $this->token);

		$this->url->setQueryParameter('oauth_signature', $signature);

		return $this;
	}
}