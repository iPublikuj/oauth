<?php
/**
 * Plaintext.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:OAuth!
 * @subpackage	Signature
 * @since		5.0
 *
 * @date		02.03.15
 */

namespace IPub\OAuth\Signature;

use IPub;
use IPub\OAuth;
use IPub\OAuth\Api;
use IPub\OAuth\Utils;

/**
 * The PLAINTEXT method does not provide any security protection and SHOULD only be used
 * over a secure channel such as HTTPS. It does not use the Signature Base String.
 *
 * @package		iPublikuj:OAuth!
 * @subpackage	Signature
 */
class Plaintext extends SignatureMethod
{
	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return "PLAINTEXT";
	}

	/**
	 * oauth_signature is set to the concatenated encoded values of the Consumer Secret and
	 * Token Secret, separated by a '&' character (ASCII code 38), even if either secret is
	 * empty. The result MUST be encoded again.
	 *
	 * Please note that the second encoding MUST NOT happen in the SignatureMethod, as
	 * OAuthRequest handles this!
	 * 
	 * {@inheritdoc}
	 */
	public function buildSignature($baseString, OAuth\Consumer $consumer, OAuth\Token $token = NULL)
	{
		$keyParts = [
			$consumer->getSecret(),
			($token) ? $token->getSecret() : ""
		];

		$keyParts = Utils\Url::urlEncodeRFC3986($keyParts);
		$key = implode('&', $keyParts);

		return $key;
	}
}