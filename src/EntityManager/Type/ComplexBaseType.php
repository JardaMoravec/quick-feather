<?php

namespace QuickFeather\EntityManager\Type;

use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\TypeError;


trait ComplexBaseType {

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
	 * @return IType|ComplexBaseType|null
	 * @throws \QuickFeather\EntityManager\Error\IdentifierError|\QuickFeather\EntityManager\Error\TypeError
	 */
	abstract public static function fromVar(mixed $value, bool $required = false, ?int $backSlash = null, ?int $slash = null,
											?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
											?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
											?int  $all = null): IType|static|null;

	/**
	 * @param mixed $identifier
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
	 * @return IType|ComplexBaseType|null
	 * @throws IdentifierError|\QuickFeather\EntityManager\Error\TypeError
	 */
	public static function fromPost(mixed $identifier, bool $required = false, ?int $backSlash = null, ?int $slash = null,
									?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
									?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
									?int  $all = null): IType|static|null {
		if ($identifier === '' || $identifier === null) {
			throw new IdentifierError('Identifier is empty!', static::class);
		}
		if (!isset($_POST[$identifier]) && $required) {
			throw new IdentifierError("This identifier ($identifier) is not contained in POST data!", static::class);
		}

		$values = filter_input(INPUT_POST, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		return static::fromVar($values, $required,
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
	}

	/**
	 * @param mixed $identifier
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
	 * @return IType|ComplexBaseType|null
	 * @throws \QuickFeather\EntityManager\Error\IdentifierError|\QuickFeather\EntityManager\Error\TypeError
	 */
	public static function fromGet(mixed $identifier, bool $required = false, ?int $backSlash = null, ?int $slash = null,
								   ?int  $quote = null, ?int $whiteSpace = null, ?int $html = null, ?int $diacritic = null,
								   ?int  $separator = null, ?int $specialChar = null, ?int $transform = null,
								   ?int  $all = null): IType|static|null {
		if ($identifier === '' || $identifier === null) {
			throw new IdentifierError('Identifier is empty!', static::class);
		}
		if (!isset($_GET[$identifier]) && $required) {
			throw new IdentifierError("This identifier ($identifier) is not contained in GET data!", static::class);
		}

		$values = filter_input(INPUT_GET, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		return static::fromVar($values, $required,
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
	}
}
