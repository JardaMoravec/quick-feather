<?php

namespace QuickFeather\EntityManager\Type;


interface IType {

	/**
	 * @return mixed
	 */
	public function getValue(): mixed;

	/**
	 * @return string
	 */
	public function __toString(): string;

	/**
	 * @return string
	 */
	public function dbTransform(): string;

	/**
	 * @param mixed $identifier
	 * @param bool $required
	 * @return IType|static|null
	 */
	public static function fromPost(string $identifier, bool $required = false): IType|static|null;

	/**
	 * @param string $identifier
	 * @param bool $required
	 * @return self|null
	 */
	public static function fromGet(string $identifier, bool $required = false): IType|static|null;

	/**
	 * @param mixed $value
	 * @param bool $required
	 * @return self|null
	 */
	public static function fromVar(mixed $value, bool $required = false): IType|static|null;
}
