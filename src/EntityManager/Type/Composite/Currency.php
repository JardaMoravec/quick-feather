<?php

namespace QuickFeather\EntityManager\Type\Composite;

use JsonSerializable;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\ICompositeType;


readonly class Currency implements ICompositeType, JsonSerializable {

	private string $value;
	private ?string $currencyCode;

	/**
	 * @param mixed $value
	 * @param string|null $currencyCode
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public function __construct(mixed $value, ?string $currencyCode = null) {
		if (is_numeric($value)) {
			$this->value = $value;
		} else {
			throw new TypeError("Hodnota nemá správný formát!", self::class);
		}

		$this->currencyCode = $currencyCode;
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * @return string|null
	 */
	public function getCurrencyCode(): ?string {
		return $this->currencyCode;
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
		return $this->value;
	}
}
