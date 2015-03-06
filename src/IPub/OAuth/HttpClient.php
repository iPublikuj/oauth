<?php
/**
 * HttpClient.php
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
use IPub\OAuth\Exceptions;

/**
 * OAuth http call interface
 *
 * @package		iPublikuj:OAuth!
 * @subpackage	common
 *
 * @author Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface HttpClient
{
	/**
	 * @param Api\Request $request
	 * @param string $signatureMethodName
	 *
	 * @return Api\Response
	 *
	 * @throws Exceptions\ApiException
	 */
	function makeRequest(Api\Request $request, $signatureMethodName = 'PLAINTEXT');
}
