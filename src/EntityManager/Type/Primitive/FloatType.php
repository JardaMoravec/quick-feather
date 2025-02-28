<?php

namespace QuickFeather\EntityManager\Type\Primitive;

use JetBrains\PhpStorm\Pure;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\IPrimitiveType;


class FloatType extends BaseType implements IPrimitiveType {

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return float|null
	 * @throws IdentifierError
	 * @throws NullError
	 */
	public static function fromPost(string $identifier, bool $required = false): ?float {
		self::checkIdentifier($_POST, $identifier, $required);
		$value = filter_input(INPUT_POST, $identifier, FILTER_DEFAULT,FILTER_NULL_ON_FAILURE);
		return self::fromVar($value, $required);
	}

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return float|null
	 * @throws IdentifierError
	 * @throws NullError
	 */
	public static function fromGet(string $identifier, bool $required = false): ?float {
		self::checkIdentifier($_GET, $identifier, $required);
		$value = filter_input(INPUT_GET, $identifier, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
		return self::fromVar($value, $required);
	}

	/**
	 * @param mixed $value
	 * @param bool $required
	 * @return float|null
	 * @throws NullError
	 */
	public static function fromVar(mixed $value, bool $required = false): ?float {
		if ($value === null || $value === '') {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'float');
			}
			return null;
		}

		$value = str_replace([',-', '.-', 'Kč', ','], ['', '', '', '.'], $value);

		$value = self::reformat($value,
			backSlash: self::remove,
			slash: self::remove,
			quote: self::remove,
			whiteSpace: self::remove,
			html: self::remove,
			diacritic: self::remove,
			transform: BaseType::lowerCase
		);

		if ($value === null || $value === '') {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'int');
			}
			return null;
		}

		$value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

		if ($value === null) {
			if ($required) {
				throw new NullError(_('Hodnota není zadána!'), 'float');
			}
			return null;
		}
		return $value;
	}

	/**
	 * @param double|null $number
	 * @param ?int $decimals
	 * @param ?string $decimalSeparator
	 * @param ?string $thousandsSeparator
	 * @return string
	 */
	#[pure]
	public static function format(float|null $number, ?int $decimals = 0, ?string $decimalSeparator = ",", ?string $thousandsSeparator = " "): string {
		return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
	}

	/**
	 * @param int|float $value
	 * @param ?string $unit
	 * @return string
	 */
	#[pure]
	public static function numberUnitConvert(int|float $value, ?string $unit = "B"): string {
		$pref = ["K", "M", "G"];
		$st = $value . " " . $unit;

		for ($i = 0; $i < 3; $i++) {
			if ($value > 512) {
				$value /= 1024;
				$st = sprintf("%.2f %sB", $value, $pref[$i]);
			}
		}
		return $st;
	}
}
