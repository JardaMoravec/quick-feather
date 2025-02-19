<?php

namespace QuickFeather\EntityManager\Type\Primitive;

use JetBrains\PhpStorm\Pure;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\IPrimitiveType;


class IntType extends BaseType implements IPrimitiveType {

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return int|null
	 * @throws IdentifierError
	 * @throws NullError
	 */
	public static function fromPost(string $identifier, bool $required = false): ?int {
		self::checkIdentifier($_POST, $identifier, $required);
		$value = filter_input(INPUT_POST, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		return self::fromVar($value, $required);
	}

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return int|null
	 * @throws IdentifierError
	 * @throws NullError
	 */
	public static function fromGet(string $identifier, bool $required = false): ?int {
		self::checkIdentifier($_GET, $identifier, $required);
		$value = filter_input(INPUT_GET, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		return self::fromVar($value, $required);
	}

	/**
	 * @param mixed $value
	 * @param bool $required
	 * @return int|null
	 * @throws NullError
	 */
	public static function fromVar(mixed $value, ?bool $required = false): ?int {
		if ($value === null || $value === '') {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'int');
			}
			return null;
		}

		$value = str_replace([',-', '.-', 'Kč', ','], ['', '', '', '.'], $value);

		if (str_contains($value, '.')) {
			$value = substr($value, 0, strpos($value, '.'));
		}

		$value = self::reformat($value,
			backSlash: self::remove,
			slash: self::remove,
			quote: self::remove,
			whiteSpace: self::remove,
			html: self::remove,
			diacritic: self::strip,
			separator: self::remove
		);

		if ($value === null || $value === '') {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'int');
			}
			return null;
		}

		// remove left zeros
		if (strlen($value) > 1) {
			$value = ltrim($value, '0');
		}

		$value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

		if ($value === null) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'int');
			}
			return null;
		}
		return $value;
	}

	/**
	 * @param int|null $number
	 * @param ?int $decimals
	 * @param ?string $decimalSeparator
	 * @param ?string $thousandsSeparator
	 * @return string
	 */
	#[pure]
	public static function format(int|null $number, ?int $decimals = 0, ?string $decimalSeparator = ",", ?string $thousandsSeparator = " "): string {
		return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
	}
}
