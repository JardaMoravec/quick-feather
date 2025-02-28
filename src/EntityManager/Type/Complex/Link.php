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


class Link implements IType, JsonSerializable {
	use ComplexBaseType;

	public const maxLength = 500;

	#[immutable]
	private string $value;

	/**
	 * @param string $email
	 * @throws TypeError
	 */
	public function __construct(string $email) {
		if (strlen($email) > self::maxLength) {
			throw new TypeError(_("Odkaz je moc dlouhý!"), self::class, 100);
		}
		$regex = "((https?|ftp)://)?"; // SCHEME
		$regex .= "([a-z0-9+!*(),;?&=\$_.-]+(:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
		$regex .= "([a-z0-9\-\.]*)\.(([a-z]{2,4})|([0-9]{1,3}\.([0-9]{1,3})\.([0-9]{1,3})))"; // Host or IP
		$regex .= "(:[0-9]{2,5})?"; // Port
		$regex .= "(/([a-z0-9+\$_%-]\.?)+)*/?"; // Path
		$regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+/\$_.-]*)?"; // GET Query
		$regex .= "(#[a-z_.-][a-z0-9+$%_.-]*)?"; // Anchor
		if (!preg_match("/^$regex$/", $email)) {
			throw new TypeError(_('Odkaz nemá správný formát!'), self::class);
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
	 * @return IType|Link|null
	 * @throws NullError
	 * @throws TypeError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int $all = null): IType|Link|null {
		$value = StringType::fromVar($value, $required,
			quote: BaseType::remove,
			whiteSpace: BaseType::remove,
			html: BaseType::remove
		);

		if ($value === null || $value === '') {
			return null;
		}

		$value = strtolower($value);
		return new Link($value);
	}
}
