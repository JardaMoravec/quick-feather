<?php

namespace QuickFeather\EntityManager\Type;


interface IPrimitiveType {

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return bool|int|float|string|null
	 */
	public static function fromPost(string $identifier, bool $required = false): bool|int|float|string|null;

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return bool|int|float|string|null
	 */
	public static function fromGet(string $identifier, bool $required = false): bool|int|float|string|null;

	/**
	 * @param mixed $value
	 * @param bool $required
	 * @return bool|int|float|string|null
	 */
	public static function fromVar(mixed $value, bool $required = false): bool|int|float|string|null;
}
