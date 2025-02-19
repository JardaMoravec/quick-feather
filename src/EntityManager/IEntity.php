<?php

namespace QuickFeather\EntityManager;

interface IEntity {
	/**
	 * @return string
	 */
	public function __toString(): string;

	/**
	 * @return mixed
	 */
	public function jsonSerialize(): mixed;

	/**
	 * @param array $data
	 * @return IEntity
	 */
	public static function array2entity(array $data): IEntity;
}
