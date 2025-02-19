<?php

namespace QuickFeather\EntityManager;

use DateTime;
use PDO;
use QuickFeather\EntityManager\Attributes\ColumnDefinition;
use QuickFeather\EntityManager\Error\EntityError;
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
 * @template T of \QuickFeather\EntityManager\IEntity
 */
readonly class Repository {
	private array $properties;

	/**
	 * Repository constructor.
	 * @param PDO $pdo
	 * @param class-string<T> $entityClass
	 * @throws ReflectionException
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
	 * @return \QuickFeather\EntityManager\IEntity|null
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws \QuickFeather\EntityManager\Error\SQLError
	 * @throws TypeError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public function getOne(string|null       $where = null,
						   int|null          $limit = null,
						   array|string|null $orderBy = null,
						   array|null        $addColumns = null
	): IEntity|null {
		$columns = [];
		$joins = [];
		foreach ($this->properties as $property) {
			if ($property['isRemote'] && !in_array($property->getName(), $addColumns ?? [], true)) {
				continue;
			}
			$columns[] = $property['dbName'] . ' as "' . $property['const'] . '"';

			$where = (string)$this->translateColumnName($property['const'], $property['dbName'], $where);
			$orderBy = $this->translateColumnName($property['const'], $property['dbName'], $orderBy);

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
	 * @return \QuickFeather\EntityManager\IEntity|null
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws TypeError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
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
	 * @return integer
	 * @throws SQLError
	 */
	public function getAggregate(string            $aggregationColumn = 'count(*) AS count',
								 string|null       $alias = "count",
								 string|null       $where = null,
								 string|array|null $groupBy = null
	): int {
		foreach ($this->properties as $property) {
			$where = (string)$this->translateColumnName($property['const'], $property['dbName'], $where);
			$groupBy = (array)$this->translateColumnName($property['const'], $property['dbName'], $groupBy);
		}

		return db::fetchCount(
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
	 * @param string $aggregationColumn
	 * @param string|null $alias
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @return int
	 * @throws SQLError
	 */
	public function getCount(string            $aggregationColumn = 'count(*) AS count',
							 string|null       $alias = "count",
							 string|null       $where = null,
							 string|array|null $groupBy = null
	): int {
		return $this->getAggregate($aggregationColumn, $alias, $where, $groupBy);

	}

	/**
	 * @param string $aggregationColumn
	 * @param string|null $alias
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @return int
	 * @throws SQLError
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
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws \QuickFeather\EntityManager\Error\TypeError
	 * @throws EntityError
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
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
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
	 * @param \QuickFeather\EntityManager\IEntity $entity
	 * @return int|null
	 * @throws SQLError
	 * @throws ReflectionException
	 */
	public function insert(IEntity $entity): ?int {
		$data = $this->entity2db($entity);
		unset($data['id']);

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
	 * @param \QuickFeather\EntityManager\IEntity $entity
	 * @param string $where
	 * @return bool
	 * @throws ReflectionException
	 * @throws SQLError
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
	 * @param \QuickFeather\EntityManager\IEntity $entity
	 * @param int $id
	 * @return bool
	 * @throws ReflectionException
	 * @throws \QuickFeather\EntityManager\Error\SQLError
	 */
	public function updateById(IEntity $entity, int $id): bool {
		return $this->update($entity, db::pkIs($id));
	}

	/**
	 * @param \QuickFeather\EntityManager\IEntity $entity
	 * @return bool
	 * @throws ReflectionException
	 * @throws \QuickFeather\EntityManager\Error\SQLError
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
	 */
	public function deleteById(int $id): bool {
		return $this->delete(db::pkIs($id));
	}

	/**
	 * @param string $where
	 * @return bool
	 * @throws SQLError
	 */
	public function delete(string $where): bool {
		return db::run(
			sql: db::delete(($this->entityClass)::source, $where),
			pdo: $this->pdo,
		);
	}

	/**
	 * @param IEntity $entity
	 * @return string
	 */
	public function entity2json(IEntity $entity): string {
		return json_encode($entity) ?? '';
	}

	/**
	 * @param \QuickFeather\EntityManager\IEntity $entity
	 * @param array $data
	 * @return \QuickFeather\EntityManager\IEntity
	 */
	public function fillFromArray(IEntity $entity, array $data): IEntity {
		foreach ($this->properties as $property) {
			if (array_key_exists($property['dbName'], $data)) {
				$entity->{$property['name']} = $data[$property['dbName']];
			}
		}
		return $entity;
	}

	/**
	 * @param string $columnName
	 * @param string $dbName
	 * @param array|string|null $subject
	 * @return array|string|null
	 */
	private function translateColumnName(string $columnName, string $dbName, array|string|null $subject): array|string|null {
		if ($subject === null) {
			return null;
		}

		if (is_array($subject)) {
			return array_map(static fn($item) => str_replace($columnName, $dbName, $item), $subject);
		}

		return str_replace($columnName, $dbName, $subject);
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
			/** @var \QuickFeather\EntityManager\Attributes\ColumnDefinition $columnDefinition */
			$columnDefinition = $attributes[0]->newInstance();

			$item['name'] = $property->getName();
			$item['const'] = constant($this->entityClass . '::' . $property->getName());
			$item['isRemote'] = $this->entityClass::source !== $columnDefinition->sourceTable;
			if ($item['isRemote']) {
				$item['dbName'] = $columnDefinition->dbColumn;
			} else {
				$item['dbName'] = $columnDefinition->sourceTable . '.' . $columnDefinition->dbColumn;
			}
			$item['type'] = $property->getType()?->getName();
			$item['null'] = $property->getType()?->allowsNull();
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
		return $result;
	}

	/**
	 * @param array $data
	 * @param array|null $addColumns
	 * @return \QuickFeather\EntityManager\IEntity
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws \QuickFeather\EntityManager\Error\TypeError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
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
	 * @throws \QuickFeather\EntityManager\Error\TypeError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	private function db2array(array $data, array|null $addColumns = null): array {
		//var_dump($data);die();
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
	 * @throws ReflectionException
	 */
	private function entity2db(IEntity $entity): array {
		$output = [];
		bdump($entity);
		foreach ($this->properties as $property) {
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
		bdump($output);
		return $output;
	}
}
