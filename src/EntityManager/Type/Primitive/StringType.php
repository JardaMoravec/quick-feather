<?php

namespace QuickFeather\EntityManager\Type\Primitive;

use JetBrains\PhpStorm\Pure;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\IPrimitiveType;
use Random\RandomException;


class StringType extends BaseType implements IPrimitiveType {


	/**
	 * @param string $identifier
	 * @param bool $required
	 * @param int|null $backSlash
	 * @param int|null $slash
	 * @param int|null $quote
	 * @param int|null $whiteSpace
	 * @param int|null $html
	 * @param int|null $diacritic
	 * @param int|null $separator
	 * @param int|null $specialChar
	 * @param int|null $all
	 * @return string|null
	 * @throws IdentifierError
	 * @throws NullError
	 */
	public static function fromPost(string $identifier, bool $required = false, ?int $backSlash = null, ?int $slash = null,
									?int   $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
									?int   $separator = null, ?int $specialChar = null, ?int $all = null): ?string {
		self::checkIdentifier($_POST, $identifier, $required);
		$value = filter_input(INPUT_POST, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		return static::fromVar($value, $required, $backSlash, $slash, $quote, $whiteSpace, $html, $diacritic, $separator, $specialChar, $all);
	}

	/**
	 * @param string|null $identifier
	 * @param bool $required
	 * @param int|null $backSlash
	 * @param int|null $slash
	 * @param int|null $quote
	 * @param int|null $whiteSpace
	 * @param int|null $html
	 * @param int|null $diacritic
	 * @param int|null $separator
	 * @param int|null $specialChar
	 * @param int|null $all
	 * @return string|null
	 * @throws \QuickFeather\EntityManager\Error\IdentifierError
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 */
	public static function fromGet(?string $identifier, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int    $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int    $separator = null, ?int $specialChar = null, ?int $all = null): ?string {
		self::checkIdentifier($_GET, $identifier, $required);
		$value = filter_input(INPUT_GET, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		return static::fromVar($value, $required, $backSlash, $slash, $quote, $whiteSpace, $html, $diacritic, $separator, $specialChar, $all);
	}

	/**
	 * @param mixed $value
	 * @param bool $required
	 * @param int|null $backSlash
	 * @param int|null $slash
	 * @param int|null $quote
	 * @param int|null $whiteSpace
	 * @param int|null $html
	 * @param int|null $diacritic
	 * @param int|null $separator
	 * @param int|null $specialChar
	 * @param int|null $transform
	 * @param int|null $all
	 * @return string|null
	 * @throws NullError
	 */
	public static function fromVar(mixed $value = null, ?bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int  $all = null): ?string {
		if ($value === null) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), static::class);
			}
			return null;
		}

		$value = (string)$value;

		$value = self::reformat($value,
			backSlash: $backSlash,
			slash: $slash,
			quote: $quote,
			whiteSpace: $whiteSpace,
			html: $html,
			diacritic: $diacritic,
			separator: $separator,
			specialChar: $specialChar,
			transform: $transform,
			all: $all
		);

		if ($value === null) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), static::class);
			}
			return null;
		}
		return $value;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function pgStringDecode(string $text): string {
		return str_replace(["&sbquo;", "&#123;", "&#1252;", "&quot;", "&apos;", "&#92;"], [",", "{", "}", "\"", "'", "\\"], $text);
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function pgStringEncode(string $text): string {
		return str_replace(["\"", "'", "’", "\\"], ["&quot;", "&apos;", "&apos;", "&#92;"], $text);
	}

	/**
	 * @param int $length
	 * @return string
	 * @throws RandomException
	 */
	public static function generateRandomString(int $length = 10): string {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, strlen($characters) - 1)];
		}
		return $randomString;
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param bool $with
	 * @return string
	 */
	#[pure]
	public static function getStringBefore(string $haystack, string $needle, bool $with = true): string {
		if ($with) {
			$move = 1;
		} else {
			$move = 0;
		}

		$pos = strrpos($haystack, $needle);
		if ($pos === false) {
			return $haystack;
		}
		return substr($haystack, 0, $pos + $move);
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @return string
	 */
	#[pure]
	public static function getStringAfter(string $haystack, string $needle): string {
		$pos = strrpos($haystack, $needle);
		if ($pos === false) {
			return $haystack;
		}
		return substr($haystack, $pos + 1);
	}

	/**
	 * @param string $value
	 * @param int $maxLength
	 * @param ?string $delimiter
	 * @return string
	 */
	#[pure]
	public static function cut2maxLength(string $value, int $maxLength, ?string $delimiter = '...'): string {

		$pos = strlen($value);

		for ($i = $maxLength; $i < $pos; $i++) {
			if ($value[$i] === ' ') {
				$pos = $i;
				break;
			}
		}
		if ($pos < strlen($value)) {
			return substr($value, 0, $pos) . $delimiter;
		}
		return $value;
	}

	/**
	 * @param string $string
	 * @param array|string[]|null $delimiters
	 * @param bool $capitalizeFirstCharacter
	 * @return array|string
	 */
	public static function convert2camelcase(string $string, ?array $delimiters = ['-', ' ', '_'], bool $capitalizeFirstCharacter = true): array|string {
		$newString = ucwords($string, implode(',', $delimiters));

		if (!$capitalizeFirstCharacter) {
			$newString = lcfirst($newString);
		}

		return str_replace($delimiters, '', $newString);
	}

	/**
	 * @param string $string
	 * @param string|null $glue
	 * @return string
	 */
	public static function convert2snakeCase(string $string, ?string $glue = '_'): string {
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode($glue, $ret);
	}
}
