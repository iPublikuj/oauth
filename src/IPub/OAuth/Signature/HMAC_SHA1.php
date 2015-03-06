<?php
/**
 * HMAC_SHA1.php
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
 * The HMAC-SHA1 signature method uses the HMAC-SHA1 signature algorithm as defined in [RFC2104]
 * where the Signature Base String is the text and the key is the concatenated values (each first
 * encoded per parameter Encoding) of the Consumer Secret and Token Secret, separated by an '&'
 * character (ASCII code 38) even if empty.
 *
 * @package		iPublikuj:OAuth!
 * @subpackage	Signature
 *
 * @author Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HMAC_SHA1 extends SignatureMethod
{
	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return "HMAC-SHA1";
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildSignature($baseString, OAuth\Consumer $consumer, OAuth\Token $token = NULL)
	{
		$keyParts = [
			Utils\Url::urlEncodeRFC3986($consumer->getSecret()),
			Utils\Url::urlEncodeRFC3986(($token) ? $token->getSecret() : '')
		];

		$key = implode('&', Utils\Url::urlEncodeRFC3986($keyParts));

		return base64_encode(hash_hmac('sha1', $baseString, $key, TRUE));
	}
}