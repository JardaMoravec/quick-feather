<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace QuickFeather\EntityManager\Attributes;

use Attribute;

#[Attribute]
class ColumnDefinition {

	/**
	 * @param string $sourceTable
	 * @param string $dbColumn
	 * @param string|null $joinCondition
	 * @param string|null $joinType
	 */
	public function __construct(
		public string      $sourceTable,
		public string      $dbColumn,
		public string|null $joinCondition = null,
		public string|null $joinType = null
	) {
	}
}
