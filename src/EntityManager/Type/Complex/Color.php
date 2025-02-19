<?php

namespace QuickFeather\EntityManager\Type\Complex;

use JetBrains\PhpStorm\Immutable;
use JsonSerializable;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;


class Color implements IType, JsonSerializable {
	use ComplexBaseType;

	public const maxLength = 7;

	#[immutable]
	private string $value;

	/**
	 * @param string $color
	 * @throws EntityError
	 */
	public function __construct(string $color) {
		if (strlen($color) > self::maxLength) {
			throw new TypeError(_("Kód je moc dlouhý!"), self::class, self::maxLength);
		}
		if (!preg_match("/^#[a-z\d-]+$/", $color)) {
			throw new TypeError(_('Kód barvy nemá správný formát!'), self::class);
		}
		$this->value = $color;
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
	 * @return \QuickFeather\EntityManager\Type\IType|Color|null
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws EntityError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int $all = null): IType|Color|null {
		$value = StringType::fromVar($value, $required,
			backSlash: BaseType::remove,
			slash: BaseType::remove,
			quote: BaseType::remove,
			whiteSpace: BaseType::remove,
			html: BaseType::remove
		);

		if ($value === null || $value === '') {
			return null;
		}

		return new Color($value);
	}
}
