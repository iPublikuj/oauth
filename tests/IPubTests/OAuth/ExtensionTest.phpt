<?php
/**
 * Test: IPub\OAuth\Extension
 * @testCase
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:OAuth!
 * @subpackage	Tests
 * @since		5.0
 *
 * @date		05.03.15
 */

namespace IPubTests\OAuth;

use Nette;

use Tester;
use Tester\Assert;

use IPub;
use IPub\OAuth;

require __DIR__ . '/../bootstrap.php';

class ExtensionTest extends Tester\TestCase
{
	/**
	 * @return \SystemContainer|\Nette\DI\Container
	 */
	protected function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		OAuth\DI\OAuthExtension::register($config);

		return $config->createContainer();
	}

	public function testCompilersServices()
	{
		$dic = $this->createContainer();

		Assert::true($dic->getService('oauth.httpClient') instanceof IPub\OAuth\Api\CurlClient);
		Assert::true($dic->getService('oauth.signature.plaintext') instanceof IPub\OAuth\Signature\Plaintext);
		Assert::true($dic->getService('oauth.signature.hmacsha1') instanceof IPub\OAuth\Signature\HMAC_SHA1);
	}
}

\run(new ExtensionTest());