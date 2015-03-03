<?php
/**
 * Url.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:OAuth!
 * @subpackage	Utils
 * @since		5.0
 *
 * @date		02.03.15
 */

namespace IPub\OAuth\Utils;

class Url
{
	/**
	 * @param string|array $input
	 *
	 * @return array|mixed|string
	 */
	public static function urlEncodeRFC3986($input)
	{
		if (is_array($input)) {
			return array_map(['\IPub\OAuth\Utils\Url', 'urlEncodeRFC3986'], $input);

		} else if (is_scalar($input)) {
			return str_replace(
				'+', ' ', str_replace('%7E', '~', rawurlencode($input))
			);

		} else {
			return '';
		}
	}

	/**
	 * This decode function isn't taking into consideration the above
	 * modifications to the encoding process. However, this method does not
	 * seem to be used anywhere so leaving it as is
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function urlDecodeRFC3986($string)
	{
		return urldecode($string);
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public static function buildHttpQuery(array $params)
	{
		if (empty($params)) {
			return '';
		}

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');

		$pairs = [];
		foreach ($params as $parameter => $value) {
			$parameter = self::urlEncodeRFC3986($parameter);

			if ($value instanceof \CURLFile) {
				continue;

			} else if (is_array($value)) {
				// If two or more parameters share the same name, they are sorted by their value
				// Ref: Spec: 9.1.1 (1)
				// June 12th, 2010 - changed to sort because of issue 164 by hidetaka
				sort($value, SORT_STRING);

				foreach ($value as $duplicate_value) {
					$pairs[] = $parameter . '=' . self::urlEncodeRFC3986($duplicate_value);
				}

			} else {
				$pairs[] = $parameter . '=' . self::urlEncodeRFC3986($value);
			}
		}

		// For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
		// Each name-value pair is separated by an '&' character (ASCII code 38)
		return implode('&', $pairs);
	}
}