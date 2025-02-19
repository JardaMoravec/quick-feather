<?php

namespace QuickFeather\EntityManager;


interface ISelectUsable {

	/**
	 * @return array
	 */
	public function toSelectOption(): array;
}
