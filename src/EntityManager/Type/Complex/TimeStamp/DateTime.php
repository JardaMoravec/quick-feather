<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace QuickFeather\EntityManager\Type\Complex\TimeStamp;

use Exception;
use JsonSerializable;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;
use Stringable;


class DateTime extends \DateTime implements IType, JsonSerializable, Stringable {
	use ComplexBaseType;

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->format(DATETIME_CZ);
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->format(DATETIME_CZ);
	}

	/**
	 * @return DateTime
	 */
	public function jsonSerialize(): DateTime {
		return $this;
	}

	/**
	 * @return string
	 */
	public function dbTransform(): string {
		return db::aps($this->format('Y-m-d H:i:s'));
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
	 * @return \QuickFeather\EntityManager\Type\IType|DateTime|null
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws Exception
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int $all = null): IType|DateTime|null {
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
		return new DateTime($value);
	}
}
