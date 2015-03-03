<?php
/**
 * Token.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:OAuth!
 * @subpackage	common
 * @since		5.0
 *
 * @date		02.03.15
 */

namespace IPub\OAuth;

use Nette;

use IPub;
use IPub\OAuth\Utils;

class Token extends Nette\Object
{
	/**
	 * @var string
	 */
	protected $token;

	/**
	 * @var string
	 */
	protected $secret;

	/**
	 * @param string $token
	 * @param string $secret
	 */
	public function __construct($token, $secret)
	{
		$this->token = $token;
		$this->secret = $secret;
	}

	/**
	 * @param string $token
	 *
	 * @return $this
	 */
	public function setToken($token)
	{
		$this->token = (string) $token;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param string $secret
	 *
	 * @return $this
	 */
	public function setSecret($secret)
	{
		$this->secret = (string) $secret;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSecret()
	{
		return $this->secret;
	}

	/**
	 * Generates the basic string serialization of a token that a server
	 * would respond to request_token and access_token calls with
	 *
	 * @return string
	 */
	function __toString()
	{
		return "oauth_token=" .
			Utils\Url::urlEncodeRFC3986($this->token) .
			"&oauth_token_secret=" .
			Utils\Url::urlEncodeRFC3986($this->secret);
	}
}