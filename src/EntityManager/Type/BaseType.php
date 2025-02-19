<?php

namespace QuickFeather\EntityManager\Type;

use QuickFeather\EntityManager\Error\IdentifierError;


abstract class BaseType {

	public const nothing = null;
	public const remove = 1;
	public const encode = 4;
	public const decode = 5;
	public const strip = 6;
	public const trim = 7;
	public const lowerCase = 8;
	public const upperCase = 9;

	/**
	 * @param array $container
	 * @param string $identifier
	 * @param bool $required
	 * @return void
	 * @throws IdentifierError
	 */
	protected static function checkIdentifier(array $container, string $identifier, bool $required = false): void {
		if ($identifier === '') {
			throw new IdentifierError('Identifier is empty!', static::class);
		}
		if (!array_key_exists($identifier, $container) && $required) {
			throw new IdentifierError("This identifier ($identifier) is not contained in array!", static::class);
		}
	}

	/**
	 * @param string $value
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
	 */
	protected static function reformat(mixed $value, ?int $backSlash = null, ?int $slash = null, ?int $quote = null,
									   ?int  $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
									   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
									   ?int $all = null): ?string {
		$value = (string)$value;

		if ($all !== null) {
			$backSlash = $backSlash ?? $all;
			$slash = $slash ?? $all;
			$quote = $quote ?? $all;
			$whiteSpace = $whiteSpace ?? $all;
			$html = $html ?? $all;
			$diacritic = $diacritic ?? $all;
			$separator = $separator ?? $all;
			$specialChar = $specialChar ?? $all;
		}

		$value = match ($transform) {
			self::upperCase => mb_strtoupper($value),
			self::lowerCase => mb_strtolower($value),
			default => $value
		};

		$value = match ($backSlash) {
			self::remove => str_replace("\\", '', $value),
			self::encode => str_replace("\\", "&#92;", $value),
			self::decode => str_replace("&#92;", "\\", $value),
			default => $value
		};

		$value = match ($slash) {
			self::remove => str_replace('/', '', $value),
			self::encode => str_replace('/', '&frasl;', $value),
			self::decode => str_replace('&frasl;', '/', $value),
			default => $value
		};

		$value = match ($quote) {
			self::remove => str_replace(["\"", "'", "’"], '', $value),
			self::encode => str_replace(["\"", "'", "’"], ["&quot;", "&apos;", "&apos;"], $value),
			self::decode => str_replace(["&quot;", "&apos;"], ["\"", "'"], $value),
			default => $value
		};

		$value = match ($whiteSpace) {
			self::remove => str_replace(["\0", "\t", "\n", "\x0B", "\r", " "], '', $value),
			self::encode => str_replace(["\0", "\t", "\n", "\x0B", "\r", " "], '-', $value),
			self::trim => trim($value),
			default => $value
		};

		$value = match ($html) {
			self::remove => strip_tags(str_replace(["&lt;", "&gt;", "&amp;"], ["<", ">", "&"], $value)),
			self::encode => str_replace(["&", "<", ">"], ["&amp;", "&lt;", "&gt;"], $value),
			self::decode => str_replace(["&lt;", "&gt;", "&amp;"], ["<", ">", "&"], $value),
			default => $value
		};

		$badChar = ["á", "ä", "č", "ď", "é", "ě", "ë", "í", "ň", "ó", "ö", "ř", "š", "ť", "ú", "ů", "ü", "ý", "ž", "Á", "Ä",
			"Č", "Ď", "É", "Ě", "Ë", "Í", "Ň", "Ó", "Ö", "Ř", "Š", "Ť", "Ú", "Ů", "Ü", "Ý", "Ž"];
		$goodChar = ["a", "a", "c", "d", "e", "e", "e", "i", "n", "o", "o", "r", "s", "t", "u", "u", "u", "y", "z", "A", "A",
			"C", "D", "E", "E", "E", "I", "N", "O", "O", "R", "S", "T", "U", "U", "U", "Y", "Z",];

		$value = match ($diacritic) {
			self::remove => str_replace($badChar, '', $value),
			self::strip, self::encode => str_replace($badChar, $goodChar, $value),
			default => $value
		};

		$separatorChars = ['_', '+', '.', ',', '?', '!', ':', ';', '~', "(", ")", "{", "}", "[", "]", "|"];

		$value = match ($separator) {
			self::remove => str_replace($separatorChars, '', $value),
			self::strip => str_replace($separatorChars, ' ', $value),
			self::encode => str_replace($separatorChars, '-', $value),
			default => $value
		};

		$specialChars = ["*", "\$", "&", "@", "^", "#", "%"];

		$value = match ($specialChar) {
			self::remove => str_replace($specialChars, '', $value),
			self::strip => str_replace($specialChars, ' ', $value),
			self::encode => str_replace($specialChars, '-', $value),
			default => $value
		};

		if ($value === '') {
			return null;
		}
		return $value;
	}
}
