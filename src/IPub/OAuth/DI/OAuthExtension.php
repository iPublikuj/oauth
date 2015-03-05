<?php
/**
 * OAuthExtension.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:OAuth!
 * @subpackage	DI
 * @since		5.0
 *
 * @date		02.03.14
 */

namespace IPub\OAuth\DI;

use Nette;
use Nette\DI;

use IPub;
use IPub\OAuth;

class OAuthExtension extends DI\CompilerExtension
{
	// Define tag string for filters
	const TAG_SIGNATURE_METHOD = 'ipub.oauth.signature';

	/**
	 * OAuth version
	 */
	const VERSION = '1.0';

	/**
	 * Extension default configuration
	 *
	 * @var array
	 */
	protected $defaults = [
		'debugger' => '%debugMode%',
		'curlOptions' => [],
	];

	public function __construct()
	{
		// Apply default curl options from api
		$this->defaults['curlOptions'] = OAuth\Api\CurlClient::$defaultCurlOptions;
	}

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		foreach ($config['curlOptions'] as $option => $value) {
			if (defined($option)) {
				unset($config['curlOptions'][$option]);
				$config['curlOptions'][constant($option)] = $value;
			}
		}

		$httpClient = $builder->addDefinition($this->prefix('httpClient'))
			->setClass('IPub\OAuth\Api\CurlClient')
			->addSetup('$service->curlOptions = ?;', [$config['curlOptions']]);

		$builder->addDefinition($this->prefix('signature.plaintext'))
			->setClass('IPub\OAuth\Signature\Plaintext')
			->addTag('oauth.signature');

		$builder->addDefinition($this->prefix('signature.hmacsha1'))
			->setClass('IPub\OAuth\Signature\HMAC_SHA1')
			->addTag(self::TAG_SIGNATURE_METHOD);

		if ($config['debugger']) {
			$builder->addDefinition($this->prefix('panel'))
				->setClass('IPub\OAuth\Diagnostics\Panel');

			$httpClient
				->addSetup($this->prefix('@panel') . '::register', array('@self'));
		}
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		// Get web loader factory
		$httpClient = $builder->getDefinition($this->prefix('httpClient'));

		// Get all registered signature methods
		foreach (array_keys($builder->findByTag(self::TAG_SIGNATURE_METHOD)) as $serviceName) {
			// Register signature method to http client
			$httpClient->addSetup('registerSignatureMethod', ['@' .$serviceName]);
		}
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 */
	public static function register(Nette\Configurator $config, $extensionName = 'oauth')
	{
		$config->onCompile[] = function (Nette\Configurator $config, Nette\DI\Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new OAuthExtension());
		};
	}
}