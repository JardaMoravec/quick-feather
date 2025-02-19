<?php

namespace QuickFeather\EntityManager\Type\Bound;

use Entity\Cms\Gallery\SizeSetting;
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


class Image implements IType, JsonSerializable {
	use ComplexBaseType;

	public const maxLength = 200; //150; todo v délce není obsažena cesta

	#[immutable]
	private string $value;

	/**
	 * @param string $path
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public function __construct(string $path) {
		if (strlen($path) > self::maxLength) {
			throw new TypeError(_("Název ($path) je moc dlouhý!"), self::class, self::maxLength);
		}

		$this->value = $path;
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * @param array $sizes
	 * @param string $baseDir
	 * @param bool $useNoImage
	 * @return array
	 * @todo vymyslet pokud je null, protože tato funkce nejde zavolat tím pádem
	 */
	public function getImagePaths(array $sizes, string $baseDir, bool $useNoImage = true): array {
		$paths = [];
		if ($this->value !== '') {
			/** @var SizeSetting $size */
			foreach ($sizes as $size) {
				$paths[] = $baseDir . $size->prefix->getValue() . '/' . $this->value;
			}
		} else if ($useNoImage) {
			/** @var SizeSetting $size */
			foreach ($sizes as $size) {
				$paths[] = $baseDir . 'img/no-image/' . $size->prefix->getValue() . '.png';
			}
		}
		return $paths;
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
	 * @return IType|Image|null
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws EntityError
	 */
	public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int  $all = null): IType|Image|null {
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

		return new Image($value);
	}
}
