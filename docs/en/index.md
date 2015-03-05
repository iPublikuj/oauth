# Quickstart

OAuth is an open standard for authorization. OAuth provides client applications a 'secure delegated access' to server resources on behalf of a resource owner. With this extension you can easily create your extension which require OAuth authentication eg. [Twitter](https://github.com/iPublikuj/twitter) of [Flickr](https://github.com/iPublikuj/flickr)

## Installation

The best way to install ipub/oauth is using  [Composer](http://getcomposer.org/):

```json
{
	"require": {
		"ipub/oauth": "dev-master"
	}
}
```

or

```sh
$ composer require ipub/oauth:@dev
```

After that you have to register extension in config.neon.

```neon
extensions:
	oauth: IPub\Oauth\DI\OauthExtension
```

## Usage

### Basic configuration

This extension creates a special section for configuration for your NEON configuration file. All parts of the configuration are optional.

```neon
oauth:
	curlOptions : []
```

With *curlOptions* you can define your custom cUrl options which have to used during calls.

### Calling OAuth methods

The http client is automatically registered. All basic signature methods are registered too. All what you have to do is create you own custom *Request* class with one method:

```php
class Request extends \IPub\OAuth\Api\Request
{
	public function getSignableParameters()
	{
		// Grab all parameters
		$params = $this->getParameters();

		// Remove oauth_signature if present
		// Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
		if (isset($params['oauth_signature'])) {
			unset($params['oauth_signature']);
		}

		return \IPub\OAuth\Utils\Url::buildHttpQuery($params);
	}
}
```

With this method you can define which parameters have to be used to calculate signature of the request. You can calculate it only from GET parameters or combine it with [POST parameters](https://github.com/iPublikuj/flickr/blob/master/src/IPub/Flickr/Api/Request.php#L34)

Now you can create a OAuth call:

```php
// Create OAuth consumer
$consumer = new \IPub\OAuth\Consumer('appKey', 'appSecret');

// Create access token object
$accessToken = new \IPub\OAuth\Token('token', 'token_secret');

// Some post values to be send
$post = [
	'key1' => 'value1',
	'key2' => 'value2',
	'key3' => 'value3'
];

// Define signature method name
$signatureMethod = 'HMAC-SHA1'; // or could be PLAINTEXT or your custom method

$response = $httpClient->makeRequest(
	new Your\Namespace\Request($consumer, 'http://api.url-to-oauth.com', \IPub\OAuth\Api\Request::POST, $post, $headers, $accessToken),
	$signatureMethod
);
```

If the request is successful, in *$response* will be *\IPub\OAuth\Api\Response* object with all the information about the result.

```php
$response->isOk() // Will return true if the request was ok

$data = $response->toArray() // Will return response content as an array. This method support 3 types of responses - JSON|XML|Query string
```

## Best practices

Please keep in mind that the user can revoke the access to his account literary anytime he wants to or there could be some error on your request. Therefore you must wrap every OAuth calls with try catch.

```php
try {
	// ...
} catch (\IPub\OAuth\ApiException $ex) {
	// ...
}
```

and if it fails you can check thrown exception for error info.