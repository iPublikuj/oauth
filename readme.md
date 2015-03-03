# oAuth

[![Build Status](https://img.shields.io/travis/iPublikuj/oauth.svg?style=flat-square)](https://travis-ci.org/iPublikuj/oauth)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/iPublikuj/oauth.svg?style=flat-square)](https://scrutinizer-ci.com/g/iPublikuj/oauth/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/ipub/oauth.svg?style=flat-square)](https://packagist.org/packages/ipub/oauth)
[![Composer Downloads](https://img.shields.io/packagist/dt/ipub/oauth.svg?style=flat-square)](https://packagist.org/packages/ipub/oauth)

OAuth API client with authorization for [Nette Framework](http://nette.org/)

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
	oauth: IPub\OAuth\DI\OAuthExtension
```

## Documentation

Learn how to authenticate the user using OAuth's oauth or call OAuth's api in [documentation](https://github.com/iPublikuj/oauth/blob/master/docs/en/index.md).

***
Homepage [http://www.ipublikuj.eu](http://www.ipublikuj.eu) and repository [http://github.com/iPublikuj/oauth](http://github.com/iPublikuj/oauth).