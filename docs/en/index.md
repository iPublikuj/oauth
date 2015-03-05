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
