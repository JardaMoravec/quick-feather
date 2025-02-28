<?php

namespace QuickFeather\EntityManager\Type\Complex\String;

use JsonSerializable;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;
use ReflectionClass;


class StringLimited implements IType, JsonSerializable {
	use ComplexBaseType;

	private string $value;

	/**
	 * @param string $text
	 */
	public function __construct(string $text) {
		$reflect = new ReflectionClass(static::class);
		$maxLength = (int)substr($reflect->getShortName(), 6);

		if ($maxLength > 0 && strlen($text) > $maxLength) {
			throw new TypeError(_("Text je moc dlouhý!"), self::class, $maxLength);
		}
		$this->value = StringType::pgStringDecode($text);
	}

	/**
	 * @param bool $htmlTransform
	 * @return string
	 */
	public function getValue(bool $htmlTransform = false): string {
		if ($htmlTransform) {
			return html_entity_decode($this->value);
		}
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function jsonSerialize(): string {
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function dbTransform(): string {
		return db::aps($this->value);
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
	 * @return IType|static|null
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int  $all = null): IType|static|null {
		$value = StringType::fromVar($value, $required,
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

		if ($value === null || $value === '') {
			return null;
		}

		return new static($value);
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
	 * @throws NullError
	 * @throws EntityError
	 */
	public function applyFilter(bool $required = false, ?int $backSlash = null, ?int $slash = null,
								?int $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								?int $separator = null, ?int $specialChar = null, ?int $transform = null,
								?int $all = null): void {
		$this->value = self::fromVar($this->value, $required,
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
		)?->getValue();
	}
}
