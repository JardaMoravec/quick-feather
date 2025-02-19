<?php

namespace QuickFeather\EntityManager\Type\Complex;

use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;


class PgArray {
	use ComplexBaseType;

	private array $values;

	/**
	 * @param array|string $values
	 * @throws TypeError
	 */
	public function __construct(array|string $values) {
		if (is_array($values)) {
			$this->values = $values;
		} else if (is_string($values)) {
			$this->values = $this->fromPgArray($values);
		} else {
			throw new TypeError('Values must be array or string!', self::class);
		}
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->getValues();
	}

	/**
	 * @return array
	 */
	public function getValues(): array {
		return $this->values;
	}

	/**
	 * @param int|null $key
	 * @return string|null
	 */
	public function getValue(?int $key = null): ?string {
		if ($key === null) {
			$key = (int) array_key_first($this->values);
		}
		return $this->values[$key] ?? null;
	}

	/**
	 * @return array
	 */
	public function __toArray(): array {
		return $this->values;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->getValue() ?? '';
	}

	/**
	 * @param ?string $pqArray
	 * @return array
	 */
	private function fromPgArray(?string $pqArray): array {
		if (str_starts_with($pqArray, '{') && str_ends_with($pqArray, '}')) {
			$str = substr($pqArray, 1, -1);
		} else {
			$str = $pqArray;
		}
		$array = explode(',', $str);
		$words = [];
		foreach ($array as $key => $word) {
			if (isset($word)) {
				$word = str_replace(["\"", "'"], "", $word);
				if ($word === 'NULL') {
					$word = null;
				} else {
					$word = StringType::pgStringDecode($word);
				}
			}
			$words[$key + 1] = $word;
		}
		return $words;
	}

	/**
	 * @return string
	 */
	public function dbTransform(): string {
		foreach ($this->values as &$value) {
			if (empty($value)) {
				$value = "NULL";
			} else {
				$value = str_replace([",", "{", "}"], ["&sbquo;", "&#123;", "&#1252;"], $value);
			}
		}
		unset($value);
		$str = implode(',', $this->values);

		return db::aps("{" . $str . "}");
	}

	/**
	 * @param mixed $identifier
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
	 * @return IType|static|null
	 * @throws \QuickFeather\EntityManager\Error\IdentifierError
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws TypeError
	 */
	public static function fromPost(mixed $identifier, bool $required = false, ?int $backSlash = null, ?int $slash = null,
									?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
									?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
									?int  $all = null): IType|static|null {
		if ($identifier === '' || $identifier === null) {
			throw new IdentifierError('Identifier is empty!', static::class);
		}
		if (!isset($_POST[$identifier]) && $required) {
			throw new IdentifierError("This identifier ($identifier) is not contained in POST data!", static::class);
		}

		$values = filter_input(INPUT_POST, $identifier, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
		return static::fromVar($values, $required,
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
	 * @return IType|Color|null
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws TypeError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int  $all = null): IType|static|null {
		if ($value instanceof self) {
			$array = $value->getValues();
		} else if ($value === null) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), static::class);
			}
			return null;
		} else {
			$array = $value;
		}

		$newArray = [];
		foreach ($array as $key => $string) {
			$filteredValue = StringType::fromVar($string, $required,
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
			if ($filteredValue !== null) {
				$newArray[$key] = $filteredValue;
			}
		}

		if (count($array) !== count($newArray)) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), static::class);
			}
			return null;
		}
		return new self($newArray);
	}

	/**
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
	 * @return void
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public function applyFilter(bool $required = false, ?int $backSlash = null, ?int $slash = null,
								?int $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								?int $separator = null, ?int $specialChar = null, ?int $transform = null,
								?int $all = null): void {
		$this->values = self::fromVar($this->values, $required,
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
		)?->getValues();
	}
}
