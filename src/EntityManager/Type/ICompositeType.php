<?php

namespace QuickFeather\EntityManager\Type;


interface ICompositeType {

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
}
