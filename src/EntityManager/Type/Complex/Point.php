<?php

namespace QuickFeather\EntityManager\Type\Complex;

use JetBrains\PhpStorm\Immutable;
use JsonSerializable;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;


class Point implements IType, JsonSerializable {
	use ComplexBaseType;

	#[immutable]
	private float $latitude;
	#[immutable]
	private float $longitude;

	/**
	 * @param string|null $input
	 * @param float|null $latitude
	 * @param float|null $longitude
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public function __construct(?string $input = null, ?float $latitude = null, ?float $longitude = null) {
		if ($input !== null && $input !== '') {
			[$latitude, $longitude] = explode(',', substr($input, 1, -1));
			if ($latitude > 0 && $longitude > 0) {
				$this->latitude = $latitude;
				$this->longitude = $longitude;
			} else {
				throw new TypeError(_('Bod nemá správný formát!'), self::class);
			}
		} else if ($latitude > 0 && $longitude > 0) {
			$this->latitude = $latitude;
			$this->longitude = $longitude;
		} else {
			$this->latitude = 0;
			$this->longitude = 0;
		}
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return '(' . $this->latitude . ',' . $this->longitude . ')';
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->getValue();
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [$this->latitude, $this->longitude];
	}

	/**
	 * @return string
	 */
	public function dbTransform(): string {
		return 'POINT (' . $this->latitude . ',' . $this->longitude . ')';
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
	 * @return IType|Point|null
	 * @throws IdentifierError
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int $all = null): IType|Point|null {
		$value = StringType::fromVar($value, $required,
			backSlash: BaseType::remove,
			slash: BaseType::remove,
			quote: BaseType::remove,
			whiteSpace: BaseType::remove,
			html: BaseType::remove,
			transform: BaseType::lowerCase
		);

		if ($value === null || $value === '') {
			return null;
		}

		return new Point($value);
	}
}
