<?php

namespace QuickFeather\EntityManager\Type\Complex;

use JsonSerializable;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\ComplexBaseType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\StringType;


class Email implements IType, JsonSerializable {
	use ComplexBaseType;

	public const maxLength = 100;

	private string $value;

	/**
	 * @param string $email
	 * @throws EntityError
	 */
	public function __construct(string $email) {
		$email = strtolower($email);
		if (strlen($email) > self::maxLength) {
			throw new TypeError(_("Email je moc dlouhý!"), self::class, self::maxLength);
		}

		if (!preg_match("/^[_a-z\d-]+(\.[_a-z\d-]+)*@[a-z\d-]+(\.[a-z\d-]+)*(\.[a-z]{2,4})$/", $email)) {
			throw new TypeError(_("Chybný formát emailu [$email]!"), self::class);
		}
		$this->value = $email;
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
	 * @return IType|static|null
	 * @throws NullError
	 * @throws EntityError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int  $all = null): IType|static|null {
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

		if (!str_contains($value, '@')) {
			throw new TypeError(_("Chybný formát emailu [$value]!"), self::class);
		}

		[, $domain] = explode('@', $value);
		if ($domain === null || $domain === '') {
			throw new TypeError(_("Chybný formát emailu [$value]!"), self::class);
		}
		if (!checkdnsrr($domain)) {
			throw new TypeError(_("Doména {$domain} použitá v tomto emailu neexistuje!"), self::class);
		}

		return new Email($value);
	}
}
