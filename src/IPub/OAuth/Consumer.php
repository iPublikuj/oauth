<?php
/**
 * Consumer.php
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
use Nette\Http;

class Consumer extends Nette\Object
{
	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $secret;

	/**
	 * @var Http\Url
	 */
	protected $callbackUrl;

	/**
	 * @param string $key
	 * @param string $secret
	 * @param Http\Url $callbackUrl
	 */
	public function __construct($key, $secret, Http\Url $callbackUrl = NULL)
	{
		$this->key = $key;
		$this->secret = $secret;
		$this->callbackUrl = $callbackUrl;
	}

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function setKey($key)
	{
		$this->key = (string) $key;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
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
	 * @return string
	 */
	public function __toString()
	{
		return "OAuthConsumer[key=$this->key,secret=$this->secret]";
	}
}