<?php

namespace QuickFeather\EntityManager\Type\Complex\TimeStamp;

use DateTime;

use JsonSerializable;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;


class Time extends DateTime implements IType, JsonSerializable {
	use ComplexBaseType;

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->format(TIME_CZ);
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->format(TIME_CZ);
	}

	/**
	 * @return \QuickFeather\EntityManager\Type\Complex\TimeStamp\DateTime
	 */
	public function jsonSerialize(): DateTime {
		return $this;
	}

	/**
	 * @return string
	 */
	public function dbTransform(): string {
		return db::aps($this->format('H:i:s'));
	}

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return ?Time
	 * @throws IdentifierError
	 * @throws NullError
	 * @throws EntityError

	 */
	public static function fromPost(string $identifier, bool $required = false): ?Time {
		$value = StringType::fromPost($identifier, $required,
			backSlash: BaseType::remove,
			slash: BaseType::remove,
			quote: BaseType::remove,
			whiteSpace: BaseType::remove,
			html: BaseType::remove
		);
		if ($value === null || $value === '') {
			return null;
		}
		return new Time($value);
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
	 * @return IType|Time|null
	 * @throws NullError

	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null,  ?int $transform = null,
								   ?int $all = null): IType|Time|null {
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
		return new Time($value);
	}

}
