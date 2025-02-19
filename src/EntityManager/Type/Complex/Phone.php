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


class Phone implements IType, JsonSerializable {
	use ComplexBaseType;

	#[immutable]
	private string $value;

	/**
	 * @param string $phoneNumber
	 * @throws \QuickFeather\EntityManager\Error\TypeError
	 */
	public function __construct(string $phoneNumber) {

		if (!preg_match("/^\d{9}$/", $phoneNumber)) {
			throw new TypeError(_('Telefonní číslo nemá správný formát!'), self::class);
		}

		$this->value = $phoneNumber;
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
	 * @return mixed
	 */
	public function jsonSerialize(): mixed {
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
	 * @return \QuickFeather\EntityManager\Type\IType|Phone|null
	 * @throws NullError
	 * @throws \QuickFeather\EntityManager\Error\TypeError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int $all = null): IType|Phone|null {
		$value = (string)$value;
		$value = strtolower($value);
		$value = str_replace([' ', '-', '(', ')', '+420'], '', $value);
		if (str_starts_with($value, '00420') && strlen($value) > 9) {
			$value = str_replace('00420', '', $value);
		}

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

		return new Phone($value);
	}
}
