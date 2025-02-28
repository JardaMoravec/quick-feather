<?php

namespace QuickFeather\EntityManager\Type\Primitive;

use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\IPrimitiveType;


class BoolType extends BaseType implements IPrimitiveType {

	/**
	 * @param string|null $identifier
	 * @param bool $required
	 * @return bool|null
	 * @throws IdentifierError
	 * @throws NullError
	 */
	public static function fromPost(?string $identifier, bool $required = false): ?bool {
		if ($identifier === '' || $identifier === null) {
			throw new IdentifierError('Identifier is empty!', static::class);
		}

		$value = filter_input(INPUT_POST, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		if ($value === null) {
			return false;
		}
		return self::fromVar($value, $required);
	}

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return bool|null
	 * @throws IdentifierError
	 * @throws NullError
	 */
	public static function fromGet(string $identifier, bool $required = false): ?bool {
		self::checkIdentifier($_GET, $identifier, $required);

		$value = filter_input(INPUT_GET, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		if ($value === null) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'bool');
			}
			return null;
		}
		return self::fromVar($value, $required);
	}

	/**
	 * @param mixed $value
	 * @param bool $required
	 * @return bool|null
	 * @throws NullError
	 */
	public static function fromVar(mixed $value, bool $required = false): ?bool {
		if ($value === null || $value === '') {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'bool');
			}
			return null;
		}

		if ($value === true || $value === false) {
			return $value;
		}

		$value = strtolower($value);

		$value = self::reformat($value,
			backSlash: self::remove,
			slash: self::remove,
			quote: self::remove,
			whiteSpace: self::remove,
			html: self::remove,
			diacritic: self::strip
		);

		if ($value === null || $value === '') {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'bool');
			}
			return null;
		}

		$value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if ($value === null) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'bool');
			}
			return null;
		}
		return (bool)$value;
	}
}
