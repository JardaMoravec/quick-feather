<?php

namespace RmamespaceR;

use Entity\IEntity;
use JsonSerializable;


class Rclase_nameR extends Rclase_nameRId implements IEntity, JsonSerializable {
/*RconstsR*/
	/**
	 * @param int $id
RcolumnsR
	 */
	public function __construct(int $id,
/*RparamsR*/
	) {
		parent::__construct($id);
	}

	/**
	 * @return Rclase_nameR
	 */
	public function jsonSerialize(): Rclase_nameR {
		return $this;
	}
}
