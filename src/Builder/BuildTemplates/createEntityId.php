<?php

namespace RmamespaceR;

use Entity\IEntity;
use JsonSerializable;


class Rclase_nameRId implements IEntity, JsonSerializable {

	/**
	 * @param int $id
	 */
	public function __construct(
		public int $id
	) {
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return (string) $this->id;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): mixed {
		return $this->id;
	}
}
