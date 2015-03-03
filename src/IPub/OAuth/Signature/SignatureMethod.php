<?php
/**
 * SignatureMethod.php
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

abstract class SignatureMethod
{
	/**
	 * Needs to return the name of the Signature Method (ie HMAC-SHA1)
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Build up the signature
	 * NOTE: The output of this function MUST NOT be urlencoded.
	 * the encoding is handled in OAuthRequest when the final
	 * request is serialized
	 *
	 * @param string $baseString
	 * @param OAuth\Consumer $consumer
	 * @param OAuth\Token|null $token
	 *
	 * @return string
	 */
	abstract public function buildSignature($baseString, OAuth\Consumer $consumer, OAuth\Token $token = NULL);

	/**
	 * Verifies that a given signature is correct
	 *
	 * @param string $baseString
	 * @param OAuth\Consumer $consumer
	 * @param OAuth\Token $token
	 * @param string $signature
	 *
	 * @return bool
	 */
	public function checkSignature($baseString, OAuth\Consumer $consumer, OAuth\Token $token, $signature)
	{
		$built = $this->buildSignature($baseString, $consumer, $token);

		return $built == $signature;
	}

}