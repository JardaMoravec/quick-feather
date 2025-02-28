<?php

namespace QuickFeather\EntityManager\Type\Complex;

use JetBrains\PhpStorm\Immutable;
use JsonSerializable;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;


class Password implements IType, JsonSerializable {
	use ComplexBaseType;

	#[immutable]
	private string $value;

	/**
	 * @param string $text
	 * @param bool $encode
	 * @throws TypeError
	 */
	public function __construct(string $text, bool $encode = false) {
		if (strlen($text) > 50) {
			throw new TypeError(_("Heslo je moc dlouhé!"), self::class, 50);
		}

		if ($encode) {
			$this->value = md5($text);
		} else {
			$this->value = $text;
		}
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
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
	 * @return IType|IdNumber|null
	 * @throws NullError
	 * @throws TypeError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int  $all = null): IType|IdNumber|null {
		$value = StringType::fromVar($value, $required,
			backSlash: BaseType::remove,
			quote: BaseType::remove,
			whiteSpace: BaseType::remove,
			html: BaseType::remove
		);

		if ($value === null || $value === '') {
			return null;
		}

		return new Password($value);
	}
}
