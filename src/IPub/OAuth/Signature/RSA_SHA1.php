<?php
/**
 * RSA_SHA1.php
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
 * The RSA-SHA1 signature method uses the RSASSA-PKCS1-v1_5 signature algorithm as defined in
 * [RFC3447] section 8.2 (more simply known as PKCS#1), using SHA-1 as the hash function for
 * EMSA-PKCS1-v1_5. It is assumed that the Consumer has provided its RSA public key in a
 * verified way to the Service Provider, in a manner which is beyond the scope of this
 * specification.
 *
 * @package		iPublikuj:OAuth!
 * @subpackage	Signature
 *
 * @author Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class RSA_SHA1 extends SignatureMethod
{
	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return "RSA-SHA1";
	}

	/**
	 * Up to the SP to implement this lookup of keys. Possible ideas are:
	 * (1) do a lookup in a table of trusted certs keyed off of consumer
	 * (2) fetch via http using a url provided by the requester
	 * (3) some sort of specific discovery code based on request
	 *
	 * Either way should return a string representation of the certificate
	 */
	abstract function fetchPublicCert($baseString);

	/**
	 * Up to the SP to implement this lookup of keys. Possible ideas are:
	 * (1) do a lookup in a table of trusted certs keyed off of consumer
	 *
	 * Either way should return a string representation of the certificate
	 */
	 abstract function fetchPrivateCert($baseString);

	/**
	 * {@inheritdoc}
	 */
	public function buildSignature($baseString, OAuth\Consumer $consumer, OAuth\Token $token = NULL)
	{
		// Fetch the private key cert based on the request
		$cert = $this->fetchPrivateCert($baseString);

		// Pull the private key ID from the certificate
		$privateKeyId = openssl_get_privatekey($cert);

		// Sign using the key
		openssl_sign($baseString, $signature, $privateKeyId);

		// Release the key resource
		openssl_free_key($privateKeyId);

		return base64_encode($signature);
	}

	/**
	 * {@inheritdoc}
	 */
	public function checkSignature($baseString, OAuth\Consumer $consumer, OAuth\Token $token, $signature)
	{
		$decoded_sig = base64_decode($signature);

		// Fetch the public key cert based on the request
		$cert = $this->fetchPublicCert($baseString);

		// Pull the public key ID from the certificate
		$publicKeyId = openssl_get_publickey($cert);

		// Check the computed signature against the one passed in the query
		$ok = openssl_verify($baseString, $decoded_sig, $publicKeyId);

		// Release the key resource
		openssl_free_key($publicKeyId);

		return $ok == 1;
	}
}