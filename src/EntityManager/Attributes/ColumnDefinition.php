<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace QuickFeather\EntityManager\Attributes;

use Attribute;

#[Attribute]
class ColumnDefinition {

	/**
	 * @param string $sourceTable
	 * @param string $dbColumn
	 * @param bool $primaryKey
	 * @param string|null $joinCondition
	 * @param string|null $joinType
	 */
	public function __construct(
		public string      $sourceTable,
		public string      $dbColumn,
		public bool        $primaryKey = false,
		public string|null $joinCondition = null,
		public string|null $joinType = null,

	) {
	}
}
