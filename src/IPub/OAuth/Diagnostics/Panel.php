<?php
/**
 * Panel.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:OAuth!
 * @subpackage	Diagnostics
 * @since		5.0
 *
 * @date		20.02.15
 */

namespace IPub\OAuth\Diagnostics;

use Nette;
use Nette\Utils\Html;

use Tracy;
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\IBarPanel;

use IPub;
use IPub\OAuth;
use IPub\OAuth\Api;

if (!class_exists('Tracy\Debugger')) {
	class_alias('Nette\Diagnostics\Debugger', 'Tracy\Debugger');
}

if (!class_exists('Tracy\Bar')) {
	class_alias('Nette\Diagnostics\Bar', 'Tracy\Bar');
	class_alias('Nette\Diagnostics\BlueScreen', 'Tracy\BlueScreen');
	class_alias('Nette\Diagnostics\Helpers', 'Tracy\Helpers');
	class_alias('Nette\Diagnostics\IBarPanel', 'Tracy\IBarPanel');
}

if (!class_exists('Tracy\Dumper')) {
	class_alias('Nette\Diagnostics\Dumper', 'Tracy\Dumper');
}

/**
 * @package		iPublikuj:OAuth!
 * @subpackage	Diagnostics
 *
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property callable $begin
 * @property callable $failure
 * @property callable $success
 */
class Panel extends Nette\Object implements Tracy\IBarPanel
{
	/**
	 * @var int logged time
	 */
	private $totalTime = 0;

	/**
	 * @var array
	 */
	private $calls = [];

	/**
	 * @return string
	 */
	public function getTab()
	{
		$img = Html::el('img', array('height' => '16px'))
			->src('data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/OAuth-Mark-32px.png')));
		$tab = Html::el('span')->title('OAuth')->add($img);
		$title = Html::el()->setText('OAuth');
		if (!empty($this->calls)) {
			$title->setText(
				count($this->calls) . ' call' . (count($this->calls) > 1 ? 's' : '') .
				' / ' . sprintf('%0.2f', $this->totalTime) . ' s'
			);
		}
		return (string)$tab->add($title);
	}

	/**
	 * @return string
	 */
	public function getPanel()
	{
		if (empty($this->calls)) {
			return NULL;
		}

		ob_start();
		$esc = callback('Nette\Templating\Helpers::escapeHtml');
		$click = class_exists('\Tracy\Dumper')
			? function ($o, $c = FALSE) { return Tracy\Dumper::toHtml($o, array('collapse' => $c)); }
			: callback('\Tracy\Helpers::clickableDump');
		$totalTime = $this->totalTime ? sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : 'none';

		require __DIR__ . '/panel.phtml';
		return ob_get_clean();
	}

	/**
	 * @param Api\Request $request
	 * @param array $options
	 */
	public function begin(Api\Request $request, $options = array())
	{
		$url = $request->getUrl();
		$url->setQuery('');

		$this->calls[spl_object_hash($request)] = (object) array(
			'url' => (string) $url,
			'params' => $request->getParameters(),
			'options' => self::toConstantNames($options),
			'result' => NULL,
			'exception' => NULL,
			'info' => array(),
			'time' => 0,
		);
	}

	/**
	 * @param Api\Response $response
	 */
	public function success(Api\Response $response)
	{
		$this->processEvent($response);
	}

	/**
	 * @param \Exception $exception
	 * @param Api\Response $response
	 */
	public function failure(\Exception $exception, Api\Response $response)
	{
		$this->processEvent($response, $exception);
	}

	/**
	 * @param Api\Response $response
	 * @param \Exception $exception
	 */
	private function processEvent(Api\Response $response, \Exception $exception = NULL)
	{
		if (!isset($this->calls[$oid = spl_object_hash($response->getRequest())])) {
			return;
		}

		$debugInfo = $response->debugInfo;

		$current = $this->calls[$oid];
		$this->totalTime += $current->time = $debugInfo['total_time'];
		unset($debugInfo['total_time']);
		$current->info = $debugInfo;
		$current->info['method'] = $response->getRequest()->getMethod();
		if ($exception) {
			$current->exception = $exception;
		} else {
			$current->result = $response->toArray() ?: $response->getContent();
		}
	}

	/**
	 * @param Api\CurlClient $client
	 */
	public function register(Api\CurlClient $client)
	{
		$client->onRequest[] = $this->begin;
		$client->onError[] = $this->failure;
		$client->onSuccess[] = $this->success;

		if ($bar = self::getDebuggerBar()) {
			$bar->addPanel($this);
		}
		if ($blueScreen = self::getDebuggerBlueScreen()) {
			$blueScreen->addPanel(array($this, 'renderException'));
		}
	}

	public function renderException(\Exception $e = NULL)
	{
		if (!$e instanceof OAuth\Exceptions\ApiException || !$e->response) {
			return NULL;
		}

		$h = 'htmlSpecialChars';
		$serializeHeaders = function ($headers) use ($h) {
			$s = '';
			foreach ($headers as $header => $value) {
				if (!empty($header)) {
					$s .= $h($header) . ': ';
				}
				$s .= $h($value) . "<br>";
			}
			return $s;
		};

		$panel = '';

		$panel .= '<p><b>Request</b></p><div><pre><code>';
		$panel .= $serializeHeaders($e->request->getHeaders());
		if (!in_array($e->request->getMethod(), array('GET', 'HEAD'))) {
			$panel .= '<br>' . $h(is_array($e->request->getPost()) ? json_encode($e->request->getPost()) : $e->request->getPost());
		}
		$panel .= '</code></pre></div>';

		$panel .= '<p><b>Response</b></p><div><pre><code>';
		$panel .= $serializeHeaders($e->response->getHeaders());
		if ($e->response->getContent()) {
			$panel .= '<br>' . $e->response->toArray() ?: $e->response->getContent();
		}
		$panel .= '</code></pre></div>';

		return array(
			'tab' => 'OAuth',
			'panel' => $panel,
		);
	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	private function toConstantNames(array $options)
	{
		static $map;
		if (!$map) {
			$map = array();
			foreach (get_defined_constants() as $name => $value) {
				if (substr($name, 0, 8) !== 'CURLOPT_') {
					continue;
				}

				$map[$value] = $name;
			}
		}

		$renamed = array();
		foreach ($options as $int => $value) {
			$renamed[isset($map[$int]) ? $map[$int] : $int] = $value;
		}

		return $renamed;
	}

	/**
	 * @return Bar
	 */
	private static function getDebuggerBar()
	{
		if (method_exists('Tracy\Debugger', 'getBar')) {
			return Debugger::getBar();

		} else {
			$reflector = new \ReflectionClass(get_class(new Debugger()));

			$prop = $reflector->getProperty('bar');

			return $prop->isPublic() ? Debugger::$bar : FALSE;
		}
	}

	/**
	 * @return BlueScreen
	 */
	private static function getDebuggerBlueScreen()
	{
		if (method_exists('Tracy\Debugger', 'getBlueScreen')) {
			return Debugger::getBlueScreen();

		} else {
			$reflector = new \ReflectionClass(get_class(new Debugger()));

			$prop = $reflector->getProperty('blueScreen');

			return $prop->isPublic() ? Debugger::$blueScreen : FALSE;
		}
	}
}