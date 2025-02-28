<?php

namespace QuickFeather\EntityManager;

use JetBrains\PhpStorm\Pure;
use PDO;
use PDOException;
use QuickFeather\EntityManager\Error\SQLError;
use QuickFeather\EntityManager\Type\Primitive\StringType;


readonly class db {
	/**
	 * @param string ...$terms
	 * @return string
	 */
	public static function and(?string ...$terms): string {
		$terms = array_filter($terms, static fn($value) => !is_null($value) && $value !== '');
		return '(' . implode(' AND ', $terms) . ')';
	}

	/**
	 * @param string ...$terms
	 * @return string
	 */
	public static function or(?string ...$terms): string {
		$terms = array_filter($terms, static fn($value) => !is_null($value) && $value !== '');
		return '(' . implode(' OR ', $terms) . ')';
	}

	/**
	 * @param string $left
	 * @param string $right
	 * @param bool $useApos
	 * @return string
	 */
	public static function is(mixed $left, mixed $right, bool $useApos = false): string {
		$right = self::valueConvert($right);
		if ($useApos && is_string($right)) {
			$right = "'" . $right . "'";
		}
		$left = self::valueConvert($left);
		return $left . ' = ' . $right;
	}

	/**
	 * @param string $left
	 * @param string $right
	 * @param bool $useApos
	 * @return string
	 */
	public static function isNot(mixed $left, mixed $right, bool $useApos = false): string {
		$right = self::valueConvert($right);
		if ($useApos && is_string($right)) {
			$right = "'" . StringType::pgStringEncode($right) . "'";
		}
		$left = self::valueConvert($left);
		return $left . ' != ' . $right;
	}

	/**
	 * @param mixed $term
	 * @return string
	 */
	public static function isNull(mixed $term): string {
		return $term . ' is null';
	}

	/**
	 * @param mixed $term
	 * @return string
	 */
	public static function isNotNull(mixed $term): string {
		return $term . ' is not null';
	}

	/**
	 * @param string|int|float $left
	 * @param string|int|float $right
	 * @param bool $useApos
	 * @return string
	 */
	public static function isBigger(string|int|float $left, string|int|float $right, bool $useApos = false): string {
		if ($useApos && is_string($right)) {
			$right = "'" . StringType::pgStringEncode($right) . "'";
		}
		return $left . ' > ' . $right;
	}

	/**
	 * @param string|int|float $left
	 * @param string|int|float $right
	 * @param bool $useApos
	 * @return string
	 */
	public static function isSmaller(string|int|float $left, string|int|float $right, bool $useApos = false): string {
		if ($useApos && is_string($right)) {
			$right = "'" . StringType::pgStringEncode($right) . "'";
		}
		return $left . ' < ' . $right;
	}

	/**
	 * @param string $left
	 * @param string $right
	 * @return string
	 */
	public static function like(string $left, string $right): string {
		if (str_contains($right, '%')) {
			return $left . " LIKE '{$right}'";
		}

		return $left . " LIKE '%{$right}%'";
	}

	/**
	 * @param string $left
	 * @param string $right
	 * @return string
	 */
	public static function notLike(string $left, string $right): string {
		if (str_contains($right, '%')) {
			return $left . " NOT LIKE '{$right}'";
		}

		return $left . " NOT LIKE '%{$right}%'";
	}

	/**
	 * like without case-sensitive
	 * @param string $left
	 * @param string $right
	 * @return string
	 */
	public static function iLike(string $left, string $right): string {
		if (str_contains($right, '%')) {
			return "lower({$left}) LIKE lower('{$right}')";
		}

		return "lower({$left}) LIKE lower('%{$right}%')";
	}

	/**
	 * not like without case-sensitive
	 * @param string $left
	 * @param string $right
	 * @return string
	 */
	public static function notILike(string $left, string $right): string {
		if (str_contains($right, '%')) {
			return "lower({$left}) NOT LIKE lower('{$right}')";
		}

		return "lower({$left}) NOT LIKE lower('%{$right}%')";
	}

	/**
	 * @param string $left
	 * @param string $right
	 * @return string
	 */
	public static function smartLike(string $left, string $right): string {
		if (str_contains($right, '%')) {
			return "unaccent(lower({$left})) LIKE unaccent(lower('{$right}'))";
		}

		return "unaccent(lower({$left})) LIKE unaccent(lower('%{$right}%'))";
	}


	/**
	 * @param mixed $left
	 * @param array $list
	 * @return string
	 * @throws SQLError
	 */
	public static function in(mixed $left, array $list): string {
		if (count($list) === 0) {
			throw new SQLError("List is empty!");
		}
		return $left . ' in (' . implode(',', $list) . ')';
	}

	/**
	 * @param mixed $left
	 * @param array $list
	 * @return string
	 * @throws SQLError
	 */
	public static function notIn(mixed $left, array $list): string {
		if (count($list) === 0) {
			throw new SQLError("List is empty!");
		}
		return $left . ' not in (' . implode(',', $list) . ')';
	}

	/**
	 * @param string $column
	 * @return string
	 */
	#[pure]
	public static function any(string $column): string {
		return 'any(' . $column . ')';
	}

	/**
	 * @param int $id
	 * @return string
	 */
	#[pure]
	public static function pkIs(int $id): string {
		return sprintf("id = %d", $id);
	}

	/**
	 * @param string ...$values
	 * @return string
	 */
	#[pure]
	public static function orderBy(string  ...$values): string {
		return implode(', ', $values);
	}

	/**
	 * @param string $column
	 * @return string
	 */
	#[pure]
	public static function asc(string $column): string {
		return $column . ' ASC';
	}

	/**
	 * @param string $column
	 * @return string
	 */
	#[pure]
	public static function desc(string $column): string {
		return $column . ' DESC';
	}

	/**
	 * @param string ...$columns
	 * @return string
	 */
	#[pure]
	public static function groupBy(string ...$columns): string {
		return implode(', ', $columns);
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public static function aps(string $value): string {
		return sprintf("'%s'", StringType::pgStringEncode($value));
	}

	/**
	 * @param string $column
	 * @param string|bool|null $alias
	 * @return string
	 */
	public static function max(string $column, string|bool|null $alias = null): string {
		if ($alias === null || $alias === true) {
			$alias = str_replace('.', '_', $column);
		}
		return sprintf("max(%s)", $column) . ($alias ? ' as ' . $alias : '');
	}

	/**
	 * @param string $column
	 * @param string|bool|null $alias
	 * @return string
	 */
	public static function min(string $column, string|bool|null $alias = null): string {
		if ($alias === null || $alias === true) {
			$alias = str_replace('.', '_', $column);
		}
		return sprintf("min(%s)", $column) . ($alias ? ' as ' . $alias : '');
	}

	/**
	 * @param string $column
	 * @param string|bool|null $alias
	 * @param string|null $resultType
	 * @return string
	 */
	public static function avg(string $column, string|bool|null $alias = null, string $resultType = null): string {
		if ($alias === null || $alias === true) {
			$alias = str_replace('.', '_', $column);
		}
		if ($resultType !== null && $resultType !== '') {
			$resultType = '::' . $resultType;
		}
		return sprintf("avg(%s)%s", $column, $resultType) . ($alias ? ' as ' . $alias : '');
	}

	/**
	 * @param string $column
	 * @param string|bool|null $alias
	 * @return string
	 */
	public static function count(string $column, string|bool|null $alias = null): string {
		if ($alias === null || $alias === true) {
			$alias = str_replace('.', '_', $column);
		}
		return sprintf("count(%s)", $column) . ($alias ? ' as ' . $alias : '');
	}

	/**
	 * @param string $column
	 * @param string|bool|null $alias
	 * @return string
	 */
	public static function sum(string $column, string|bool|null $alias = null): string {
		if ($alias === null || $alias === true) {
			$alias = str_replace('.', '_', $column);
		}
		return sprintf("sum(%s)", $column) . ($alias ? ' as ' . $alias : '');
	}

	/**
	 * @param array $columns
	 * @param string $alias
	 * @return string
	 * @throws SQLError
	 */
	public static function coalesce(array $columns, string $alias): string {
		if (count($columns) === 0) {
			throw new SQLError("List is empty!");
		}
		return sprintf("coalesce(%s) as %s", implode(', ', $columns), $alias);
	}

	/**
	 * @param string $column
	 * @param string $type
	 * @param string|null $alias
	 * @return string
	 */
	#[pure]
	public static function extract(string $column, string $type, ?string $alias = null): string {
		return sprintf("extract(%s from %s)", $type, $column) . ($alias ? ' as ' . $alias : '');
	}

	/**
	 * @param string $content
	 * @return string
	 */
	#[pure]
	public static function bracket(string $content): string {
		return sprintf("(%s)", $content);
	}


	/**
	 * @param string $column
	 * @param int $index
	 * @param string|bool|null $alias
	 * @return string
	 */
	public static function arrayIndex(string $column, int $index, string|bool|null $alias = null): string {
		if ($alias === null || $alias === true) {
			$alias = str_replace('.', '_', $column);
		}
		return sprintf("%s[%d]", $column, $index) . ($alias ? ' as ' . $alias : '');
	}

	/**
	 * @param string $column
	 * @param string $property
	 * @param string|null $alias
	 * @return string
	 */
	public static function jsonProp(string $column, string $property, ?string $alias = null): string {
		if ($alias === null || $alias === '') {
			$alias = $column . '_' . $property;
		}
		return sprintf("%s->>'%s' as %s", $column, $property, $alias);
	}

	/**
	 * @param string $source
	 * @param string $on
	 * @param string|null $alias
	 * @return string
	 */
	#[pure]
	public static function leftJoin(string $source, string $on, ?string $alias = null): string {
		return " LEFT JOIN " . $source . ($alias ? ' ' . $alias : '') . " ON " . $on;
	}

	/**
	 * @param string $source
	 * @param string $on
	 * @param string|null $alias
	 * @return string
	 */
	#[pure]
	public static function innerJoin(string $source, string $on, ?string $alias = null): string {
		return " INNER JOIN " . $source . ($alias ? ' ' . $alias : '') . " ON " . $on;
	}

	/**
	 * @param string $source
	 * @param string $on
	 * @param string|null $alias
	 * @return string
	 */
	#[pure]
	public static function rightJoin(string $source, string $on, ?string $alias = null): string {
		return " RIGHT JOIN " . $source . ($alias ? ' ' . $alias : '') . " ON " . $on;
	}

	/**
	 * @param string $source
	 * @param string|null $alias
	 * @return string
	 */
	#[pure]
	public static function crossJoin(string $source, string|null $alias = null): string {
		return " CROSS JOIN " . $source . ($alias ? ' ' . $alias : '');
	}

	/**
	 * @param string $source
	 * @param string $alias
	 * @return string
	 */
	#[pure]
	public static function as(string $source, string $alias): string {
		return $source . ' as ' . $alias;
	}

	/**
	 * @param string $source
	 * @param string $alias
	 * @return string
	 */
	#[pure]
	public static function ch(string $source, string $alias): string {
		[, $column] = explode('.', $source);
		return $alias . '.' . $column;
	}

	public static function md5(string $source, bool $useApos = false): string {
		if ($useApos) {
			return 'md5(\'' . StringType::pgStringEncode($source) . '\')';
		}

		return 'md5(' . $source . ')';
	}

	/**
	 * @param string|array $columns
	 * @param string|array $source
	 * @param string|array|null $joins
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @param string|array|null $having
	 * @param string|array|null $orderBy
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return string
	 * @throws SQLError
	 */
	public static function select(string|array      $columns,
								  string|array      $source,
								  string|array|null $joins = null,
								  string|null       $where = null,
								  string|array|null $groupBy = null,
								  string|array|null $having = null,
								  string|array|null $orderBy = null,
								  int|null          $limit = null,
								  int|null          $offset = null): string {


		if (!$columns || count($columns) === 0) {
			throw new SQLError('Table ' . $source . ' has empty field definition!');
		}

		$columnsNew = [];
		foreach ($columns as $fieldName) {
			if (!str_contains($fieldName, '.') && !str_contains($fieldName, '(')) {
				$fieldName = $source . '.' . $fieldName;
			}
			$columnsNew[] = $fieldName;
		}

		$sql = 'SELECT ';
		$sql .= implode(',', $columnsNew);
		if (is_array($source)) {
			$sql .= ' FROM ' . implode(' ', $source);
		} else {
			$sql .= ' FROM ' . $source;
		}

		if (is_array($joins) && count($joins) > 0) {
			$sql .= ' ' . implode(' ', $joins);
		} else if (is_string($joins) && $joins !== '') {
			$sql .= ' ' . $joins;
		}

		if ($where !== null && $where !== '') {
			$sql .= ' WHERE ' . $where;
		}

		if (is_array($groupBy) && count($groupBy) > 0) {
			$sql .= ' GROUP BY ' . implode(', ', $groupBy);
		} else if (is_string($groupBy) && $groupBy !== '') {
			$sql .= ' GROUP BY ' . $groupBy;
		}

		if (is_array($having) && count($having) > 0) {
			$sql .= ' HAVING ' . implode(', ', $having);
		} else if (is_string($having) && $having !== '') {
			$sql .= ' HAVING ' . $having;
		}

		if (is_array($orderBy) && count($orderBy) > 0) {
			$sql .= ' ORDER BY ' . implode(', ', $orderBy);
		} else if (is_string($orderBy) && $orderBy !== '') {
			$sql .= ' ORDER BY ' . $orderBy;
		}

		if ($limit) {
			$sql .= ' LIMIT ' . $limit;
		}

		if ($offset) {
			$sql .= ' OFFSET ' . $offset;
		}

		return $sql;
	}

	/**
	 * @param string $sql
	 * @param PDO $pdo
	 * @return mixed
	 * @throws SQLError
	 * @throws PDOException
	 */
	public static function fetchValue(string $sql, PDO $pdo): mixed {
		if ($sql === '') {
			throw new SQLError("SQL is empty");
		}
		$query = $pdo->query($sql);
		$data = $query->fetch(PDO::FETCH_ASSOC);
		if (is_array($data)) {
			return $data[array_key_first($data)] ?? null;
		}

		return null;
	}

	/**
	 * @param string $sql
	 * @param PDO $pdo
	 * @return array
	 * @throws SQLError
	 * @throws PDOException
	 */
	public static function fetchOne(string $sql, PDO $pdo): array {
		if ($sql === '') {
			throw new SQLError("SQL is empty");
		}
		$query = $pdo->query($sql);
		return $query->fetch(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * @param string $sql
	 * @param PDO $pdo
	 * @return array
	 * @throws SQLError
	 * @throws PDOException
	 */
	public static function fetchAll(string $sql, PDO $pdo): array {
		if ($sql === '') {
			throw new SQLError("SQL is empty");
		}
		$query = $pdo->query($sql);
		return $query->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * @param string $sql
	 * @param PDO $pdo
	 * @param string|null $alias
	 * @return int
	 * @throws SQLError
	 * @throws PDOException
	 */
	public static function fetchAggregate(string $sql, PDO $pdo, ?string $alias = 'count'): int|string {
		if ($sql === '') {
			throw new SQLError("SQL is empty");
		}
		$query = $pdo->query($sql);
		$data = $query->fetch(PDO::FETCH_ASSOC);
		return $data[$alias] ?? 0;
	}

	/**
	 * @param string $sql
	 * @param PDO $pdo
	 * @return bool
	 * @throws SQLError
	 * @throws PDOException
	 */
	public static function run(string $sql, PDO $pdo): bool {
		if ($sql === '') {
			throw new SQLError("SQL is empty");
		}
		$result = $pdo->query($sql);
		return $result !== false;
	}

	/**
	 * @param string $source
	 * @param array $data
	 * @param string $where
	 * @return string
	 * @throws SQLError
	 */
	public static function update(string $source, array $data, string $where): string {
		if (!$data || count($data) === 0) {
			throw new SQLError('Nothing to save to ' . $source . '!');
		}

		$columns = [];
		foreach ($data as $fieldName => $value) {
			$value = self::valueConvert($value);
			if (str_contains($fieldName, '.')) {
				$fieldName = substr($fieldName, strrpos($fieldName, '.') + 1);
			}
			$columns[] = "\"{$fieldName}\" = {$value}";
		}

		$sql = 'UPDATE ' . $source . ' SET ';
		$sql .= implode(',', $columns);

		if ($where !== '') {
			$sql .= ' WHERE ' . $where;
		}
		return $sql;
	}

	/**
	 * @param string $source
	 * @param array $data
	 * @param array|null $returning
	 * @return string
	 * @throws SQLError
	 */
	public static function insert(string $source, array $data, ?array $returning = []): string {

		if (!$data || count($data) === 0) {
			throw new SQLError('Nothing to save to ' . $source . '!');
		}

		$columns = [];
		$values = [];
		foreach ($data as $fieldName => $value) {
			$value = self::valueConvert($value);
			if (str_contains($fieldName, '.')) {
				$fieldName = substr($fieldName, strrpos($fieldName, '.') + 1);
			}
			$columns[] = '"' . $fieldName . '"';
			$values[] = $value;
		}

		$sql = 'INSERT INTO ' . $source . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';

		if (count($returning) > 0) {
			$sql .= ' RETURNING ' . implode(',', $returning);
		}

		return $sql . ';';
	}

	/**
	 * @param string $source
	 * @param string $where
	 * @return string
	 */
	#[pure]
	public static function delete(string $source, string $where): string {
		$sql = 'DELETE FROM ' . $source;

		if ($where !== '') {
			$sql .= ' WHERE ' . $where;
		}
		return $sql;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private static function valueConvert(mixed $value): mixed {
		return match ($value) {
			true => 'true',
			false => 'false',
			null => 'null',
			default => $value
		};
	}
}
