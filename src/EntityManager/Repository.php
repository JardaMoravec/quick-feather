<?php

namespace QuickFeather\EntityManager;

use DateTime;
use PDO;
use PDOException;
use QuickFeather\EntityManager\Attributes\ColumnDefinition;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\SQLError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\Complex\PgArray;
use QuickFeather\EntityManager\Type\Complex\Point;
use QuickFeather\EntityManager\Type\ICompositeType;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\BoolType;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * @template T of IEntity
 */
readonly class Repository {
	private array $properties;

	/**
	 * Repository constructor.
	 * @param PDO $pdo
	 * @param class-string<T> $entityClass
	 * @throws ReflectionException
	 * @throws RuntimeException
	 */
	public function __construct(private PDO    $pdo,
								private string $entityClass
	) {
		$interfaces = class_implements($entityClass);
		if (!isset($interfaces[IEntity::class])) {
			throw new RuntimeException("Class {$entityClass} must implement IEntity!");
		}

		$this->properties = $this->loadProperties();
	}

	/**
	 * @param string|null $where
	 * @param int|null $limit
	 * @param array|string|null $orderBy
	 * @param array|null $addColumns
	 * @return IEntity|null
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws IdentifierError
	 * @throws PDOException
	 */
	public function getOne(string|null       $where = null,
						   int|null          $limit = null,
						   array|string|null $orderBy = null,
						   array|null        $addColumns = null
	): IEntity|null {
		$columns = [];
		$joins = [];
		foreach ($this->properties as $property) {
			if ($property['isRemote'] && !in_array($property['name'], $addColumns ?? [], true)) {
				continue;
			}
			$columns[] = $property['dbName'] . ' as "' . $property['const'] . '"';

			if ($where !== null) {
				$where = (string)$this->translateColumnName($property['const'], $property['dbName'], $where);
			}
			if ($orderBy !== null) {
				$orderBy = $this->translateColumnName($property['const'], $property['dbName'], $orderBy);
			}

			// when property is remote, we need to join it
			if ($property['joinSource'] === null) {
				continue;
			}

			$joinCondition = $this->translateColumnName($property['const'], $property['dbName'], $property['joinCondition']);
			$join = db::leftJoin($property['joinSource'], $joinCondition);
			if ($join) {
				$joins[] = $join;
			}
		}

		$item = db::fetchOne(
			sql: db::select(
				columns: $columns,
				source: ($this->entityClass)::source,
				joins: $joins,
				where: $where,
				orderBy: $orderBy,
				limit: $limit,
			), pdo: $this->pdo,
		);
		if (count($item) === 0) {
			return null;
		}
		return $this->db2entity($item, $addColumns);
	}

	/**
	 * @param int $id
	 * @param array|null $addColumns
	 * @return IEntity|null
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function getOneById(int        $id,
							   array|null $addColumns = null
	): IEntity|null {
		return $this->getOne(db::pkIs($id), addColumns: $addColumns);
	}

	/**
	 * @param string $aggregationColumn
	 * @param string|null $alias
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @return integer|string
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function getAggregate(string            $aggregationColumn = 'count(*) AS count',
								 string|null       $alias = "count",
								 string|null       $where = null,
								 string|array|null $groupBy = null
	): int|string {
		foreach ($this->properties as $property) {
			$where = (string)$this->translateColumnName($property['const'], $property['dbName'], $where);
			$groupBy = (array)$this->translateColumnName($property['const'], $property['dbName'], $groupBy);
		}

		return db::fetchAggregate(
			sql: db::select(
				columns: [$aggregationColumn],
				source: ($this->entityClass)::source,
				where: $where,
				groupBy: $groupBy,
			),
			pdo: $this->pdo,
			alias: $alias
		) ?? 0;
	}

	/**
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @return int
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function getCount(string|null       $where = null,
							 string|array|null $groupBy = null
	): int {

		return (int)$this->getAggregate(where: $where, groupBy: $groupBy);
	}

	/**
	 * @param string $aggregationColumn
	 * @param string|null $alias
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @return int
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function getSum(string            $aggregationColumn = 'sum(*) AS sum',
						   string|null       $alias = "sum",
						   string|null       $where = null,
						   string|array|null $groupBy = null
	): int {
		return $this->getAggregate($aggregationColumn, $alias, $where, $groupBy);

	}

	/**
	 * @param string|null $where
	 * @param string|array|null $orderBy
	 * @param int|null $limit
	 * @param int|null $offset
	 * @param string|array|null $groupBy
	 * @param array|null $addColumns
	 * @return array
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException|ReflectionException
	 */
	public function getList(string|null       $where = null,
							string|array|null $orderBy = null,
							int|null          $limit = null,
							int|null          $offset = null,
							string|array|null $groupBy = null,
							array|null        $addColumns = null
	): array {
		$columns = [];
		$joins = [];

		foreach ($this->properties as $property) {
			if ($property['isRemote'] && !in_array($property['const'], $addColumns ?? [], true)) {
				continue;
			}
			$columns[] = $property['dbName'] . ' as "' . $property['const'] . '"';

			$where = (string)$this->translateColumnName($property['const'], $property['dbName'], $where);
			$groupBy = $this->translateColumnName($property['const'], $property['dbName'], $groupBy);
			$orderBy = $this->translateColumnName($property['const'], $property['dbName'], $orderBy);

			// when property is remote, we need to join it
			if ($property['joinSource'] === null) {
				continue;
			}

			$join = db::leftJoin($property['joinSource'], $property['joinCondition']);
			if ($join) {
				$joins[] = $join;
			}
		}

		$list = db::fetchAll(
			sql: db::select(
				columns: $columns,
				source: ($this->entityClass)::source,
				joins: $joins,
				where: $where,
				groupBy: $groupBy,
				orderBy: $orderBy,
				limit: $limit, offset: $offset
			), pdo: $this->pdo,
		);

		if (count($list) === 0) {
			return [];
		}
		foreach ($list as &$item) {
			$item = $this->db2entity($item, $addColumns);
		}
		return $list;
	}

	/**
	 * @param array $parameters
	 * @param string|null $where
	 * @param string|null $groupBy
	 * @param array|null $addColumns
	 * @return array
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws IdentifierError
	 * @throws EntityError
	 * @throws PDOException|ReflectionException
	 */
	public function getListByParameters(array       $parameters,
										string|null $where = null,
										string|null $groupBy = null,
										array|null  $addColumns = null,
	): array {
		// sorting
		$order = [];
		if (count($parameters['order']) > 0) {
			foreach ($parameters['order'] as $column => $way) {
				$class = $this->entityClass;
				$order[] = constant("{$class}::{$column}") . ' ' . $way;
			}
		}
		// filtering
		if (array_key_exists('condition', $parameters) && is_array($parameters['condition']) && count($parameters['condition']) > 0) {
			$filter = [];
			foreach ($parameters['condition'] as $column => $value) {
				$class = $this->entityClass;
				$filter[] = " unaccent((" . constant("{$class}::{$column}") . ")::varchar) ILIKE unaccent('%{$value}%') ";
			}
			if ($where) {
				$where .= ' and ';
			}
			$where .= "(" . implode(' OR ', $filter) . ")";
		}

		$data = $this->getList(
			where: $where,
			orderBy: implode(', ', $order),
			limit: $parameters['count'] ?? null,
			offset: $parameters['from'] ?? null,
			groupBy: $groupBy,
			addColumns: $addColumns
		);

		$count = $this->getAggregate(where: $where, groupBy: $groupBy);

		return [$data, $count];
	}

	/**
	 * @param IEntity $entity
	 * @return int|null
	 * @throws SQLError
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function insert(IEntity $entity): ?int {
		$data = $this->entity2db($entity);

		foreach ($this->properties as $property) {
			if ($property['primaryKey'] === true) {
				unset($data[$property['dbName']]);
			}
		}

		if (property_exists($entity, 'id')) {
			$returning = ['id'];
		} else {
			$returning = [];
		}

		$result = db::fetchOne(
			sql: db::insert(($this->entityClass)::source, $data, $returning),
			pdo: $this->pdo,
		);

		return $result['id'] ?? null;
	}

	/**
	 * @param IEntity $entity
	 * @param string $where
	 * @return bool
	 * @throws SQLError
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function update(IEntity $entity, string $where): bool {
		$data = $this->entity2db($entity);
		unset($data['id']);

		return db::run(
			sql: db::update(($this->entityClass)::source, $data, $where),
			pdo: $this->pdo,
		);
	}

	/**
	 * @param IEntity $entity
	 * @param int $id
	 * @return bool
	 * @throws SQLError
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function updateById(IEntity $entity, int $id): bool {
		return $this->update($entity, db::pkIs($id));
	}

	/**
	 * @param IEntity $entity
	 * @return bool
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws SQLError
	 * @throws PDOException
	 */
	public function updateEntity(IEntity $entity): bool {
		if (!property_exists($entity, 'id')) {
			throw new SQLError('Entity has no ID');
		}
		return $this->update($entity, db::pkIs($entity->id));
	}

	/**
	 * @param int $id
	 * @return bool
	 * @throws SQLError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function deleteById(int $id): bool {
		return $this->delete(db::pkIs($id));
	}

	/**
	 * @param string $where
	 * @return bool
	 * @throws SQLError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function delete(string $where): bool {
		return db::run(
			sql: db::delete(($this->entityClass)::source, $where),
			pdo: $this->pdo,
		);
	}

	/**
	 * @param IEntity $entity
	 * @return bool
	 * @throws SQLError
	 * @throws EntityError
	 * @throws PDOException
	 */
	public function deleteEntity(IEntity $entity): bool {
		if (!property_exists($entity, 'id')) {
			throw new SQLError('Entity has no ID');
		}
		return $this->deleteById($entity->id);

	}

	/**
	 * @param IEntity $entity
	 * @return string
	 */
	public function entity2json(IEntity $entity): string {
		return json_encode($entity) ?? '';
	}

	/**
	 * @param IEntity $entity
	 * @param array $data
	 * @return IEntity
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 */
	public function fillFromArray(IEntity $entity, array $data): IEntity {
		foreach ($this->properties as $property) {
			if (array_key_exists($property['const'], $data)) {
				$entity->{$property['name']} = $data[$property['const']];
			}
		}
		return $entity;
	}

	/**
	 * @param string $columnName
	 * @param string $dbName
	 * @param array|string|null $sqlQuery
	 * @return array|string|null
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 */
	private function translateColumnName(string $columnName, string $dbName, array|string|null $sqlQuery): array|string|null {
		if ($sqlQuery === null) {
			return null;
		}
		// todo nenahrazovat pokud je mezi apostrofy
		// todo testy na tuto funkci, aspoň 10
		/*if (is_array($sqlQuery)) {
			return array_map(static fn($item) => str_replace($columnName, $dbName, $item), $sqlQuery);
		}

		return str_replace($columnName, $dbName, $sqlQuery);
		*/
		if (is_array($sqlQuery)) {
			return array_map(static fn($item) => preg_replace_callback(
				"/'[^']*'(*SKIP)(*F)|\b" . preg_quote($columnName, '/') . "\b/",
				static fn($match) => $dbName,
				$item
			), $sqlQuery);
		}

		return preg_replace_callback(
			"/'[^']*'(*SKIP)(*F)|\b" . preg_quote($columnName, '/') . "\b/",
			static fn($match) => $dbName,
			$sqlQuery
		);
	}

	/**
	 * @return string
	 */
	public function getEntityClass(): string {
		return $this->entityClass;
	}

	/**
	 * @return array
	 */
	public function getProperties(): array {
		return $this->properties;
	}

	/**
	 * @return array
	 * @throws ReflectionException
	 */
	private function loadProperties(): array {
		$class = new ReflectionClass($this->entityClass);
		$properties = $class->getProperties();

		$result = [];

		foreach ($properties as $property) {
			$attributes = $property->getAttributes(ColumnDefinition::class);
			if (count($attributes) === 0) {
				throw new ReflectionException('Missing ColumnDefinition attribute for ' . $property->getName() . ' in ' . $this->entityClass);
			}
			/** @var ColumnDefinition $columnDefinition */
			$columnDefinition = $attributes[0]->newInstance();

			$item['name'] = $property->getName();
			$item['const'] = strtolower(constant($this->entityClass . '::' . $property->getName()));
			$item['isRemote'] = $this->entityClass::source !== $columnDefinition->sourceTable;

			if ($item['isRemote']) {
				$item['dbName'] = $columnDefinition->dbColumn;
			} else {
				$item['dbName'] = $columnDefinition->sourceTable . '.' . $columnDefinition->dbColumn;
			}
			$item['type'] = $property->getType()?->getName();
			$item['null'] = $property->getType()?->allowsNull();
			$item['primaryKey'] = $columnDefinition->primaryKey ?? false;
			if (str_contains($item['type'], 'String')) {
				$item['length'] = (int)substr($item['type'], strrpos($item['type'], 'String') + 6);
			}
			if ($item['isRemote']) {
				$item['joinSource'] = $columnDefinition->sourceTable ?? null;
				$item['joinCondition'] = $columnDefinition->joinCondition ?? null;
			} else {
				$item['joinSource'] = null;
				$item['joinCondition'] = null;
			}

			$result[] = $item;
		}

		foreach ($result as &$item) {
			if ($item['joinCondition'] !== null) {
				foreach ($result as $property) {
					$item['joinCondition'] = $this->translateColumnName($property['const'], $property['dbName'], $item['joinCondition']);
				}
			}
		}
		unset($item);

		return $result;
	}

	/**
	 * @param array $data
	 * @return IEntity
	 */
	public function array2entity(array $data): IEntity {
		return ($this->entityClass)::array2entity($data);
	}

	/**
	 * @param array $data
	 * @param array|null $addColumns
	 * @return IEntity
	 * @throws NullError
	 * @throws TypeError
	 * @throws EntityError|ReflectionException
	 */
	private function db2entity(array $data, array|null $addColumns = null): IEntity {
		$data = $this->db2array($data, $addColumns);
		return ($this->entityClass)::array2entity($data);
	}


	/**
	 * @param array $data
	 * @param array|null $addColumns
	 * @return array
	 * @throws NullError
	 * @throws TypeError
	 * @throws EntityError
	 * @throws ReflectionException
	 */
	private function db2array(array $data, array|null $addColumns = null): array {
		$output = [];
		foreach ($this->properties as $property) {
			if ($property['isRemote'] && !in_array($property['const'], $addColumns ?? [], true)) {
				continue;
			}

			if ($data[$property['const']] !== null) {
				$type = $property['type'];

				if (class_exists($type)) {
					$interfaces = class_implements($type);
					$interfaces = !$interfaces ? [] : $interfaces;
				} else {
					$interfaces = [];
				}

				if ($type === PgArray::class) {
					$output[$property['const']] = new PgArray($data[$property['const']]);

				} else if ($property['isRemote'] === true && in_array(IEntity::class, $interfaces, true)) {
					$dbData = json_decode($data[$property['const']], true);
					if ($dbData === null) {
						$output[$property['const']] = null;
					} else {
						if (!is_array($dbData)) {
							$dbData = [$dbData];
						}

						$repository = new self($this->pdo, $type);
						$entity = [];
						foreach ($dbData as $key => $dbRecord) {
							$parts = explode('.', $type::source);
							$tableName = end($parts);
							foreach ($dbRecord as $name => $value) {
								$dbRecord[$tableName . '.' . $name] = $value;
								unset($dbRecord[$name]);
							}
							$array = $repository->db2array($dbRecord);
							$entity[$key] = $array;
						}
						if (count($entity) === 1) {
							$output[$property['const']] = $entity[0];
						} else {
							$output[$property['const']] = $entity;
						}
					}
				} else if (
					$type === DateTime::class ||
					in_array(IType::class, $interfaces, true) ||
					in_array(IEntity::class, $interfaces, true)
				) {
					$output[$property['const']] = new $type ($data[$property['const']]);

				} else if ($type === Point::class) {
					$output[$property['const']] = new Point($data[$property['const']]);

				} else if ($type === 'array') {
					$output[$property['const']] = json_decode($data[$property['const']], true);

				} else if ($type === 'bool') {
					$output[$property['const']] = BoolType::fromVar($data[$property['const']]);

				} else {
					$output[$property['const']] = $data[$property['const']];
				}
			} else {
				$output[$property['const']] = null;
			}
		}
		return $output;
	}

	/**
	 * @param IEntity $entity
	 * @return array
	 * @throws NullError
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 */
	private function entity2db(IEntity $entity): array {
		$output = [];
		foreach ($this->properties as $property) {
			if ($property['isRemote']) {
				continue;
			}
			$propertyName = $property['name'];
			$type = $property['type'];
			if ($entity->$propertyName !== null) {
				if ($type === 'string') {
					$output[$property['dbName']] = db::aps((string)$entity->$propertyName);

				} else if ($type === DateTime::class) {
					$output[$property['dbName']] = db::aps((string)($entity->$propertyName)->format('Y-m-d H:i:s'));

				} else if ($entity->$propertyName instanceof IEntity && strpos($type, 'Id') === (strlen($type) - 2)) {
					$output[$property['dbName']] = $entity->$propertyName->id;

				} else if (($entity->$propertyName) instanceof IType) {
					$output[$property['dbName']] = $entity->$propertyName->dbTransform();

				} else if (($entity->$propertyName) instanceof ICompositeType) {
					$output[$property['dbName']] = $entity->$propertyName->dbTransform();

				} else if ($type === 'array') {
					$output[$property['dbName']] = "'" . json_encode($entity->$propertyName) . "'";

				} else if ($type === 'bool') {
					$output[$property['dbName']] = match ($entity->$propertyName) {
						true => db::aps('t'),
						false => db::aps('f'),
						default => null
					};

				} else {
					$output[$property['dbName']] = $entity->$propertyName;
				}
			} else {
				$output[$property['dbName']] = null;
			}
		}
		return $output;
	}
}
