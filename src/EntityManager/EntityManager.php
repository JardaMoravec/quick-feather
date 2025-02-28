<?php

namespace QuickFeather\EntityManager;

use PDO;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\SQLError;
use QuickFeather\EntityManager\Error\TypeError;
use ReflectionException;

readonly class EntityManager {

	/**
	 * @param PDO $pdo
	 */
	public function __construct(private PDO $pdo) {
	}

	/**
	 * @param string $entityClass
	 * @return Repository
	 * @throws ReflectionException
	 */
	public function getRepository(string $entityClass): Repository {
		return new Repository($this->pdo, $entityClass);
	}

	/**
	 * @param string $entityClass
	 * @param string|null $where
	 * @param int|null $limit
	 * @param string|null $orderBy
	 * @return IEntity|null
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws NullError
	 * @throws TypeError
	 * @throws EntityError
	 */
	public function getOne(string $entityClass, ?string $where = null, ?int $limit = null, ?string $orderBy = null): IEntity|null {
		return $this->getRepository($entityClass)->getOne($where, $limit, $orderBy);
	}

	/**
	 * @param string $entityClass
	 * @param int $id
	 * @return IEntity|null
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 */
	public function getOneById(string $entityClass, int $id): IEntity|null {
		return $this->getRepository($entityClass)->getOneById($id);
	}

	/**
	 * @param string $entityClass
	 * @param string $aggregationColumn
	 * @param string $alias
	 * @param string|null $where
	 * @param string|null $groupBy
	 * @return int
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function getAggregate(string      $entityClass,
								 string      $aggregationColumn = 'count(*) AS count',
								 string      $alias = "count",
								 string|null $where = null,
								 string|null $groupBy = null
	): int {
		return $this->getRepository($entityClass)->getAggregate($aggregationColumn, $alias, $where, $groupBy);
	}

	/**
	 * @param string $entityClass
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @return int
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function getCount(string            $entityClass,
							 string|null       $where = null,
							 string|array|null $groupBy = null
	): int {
		return $this->getRepository($entityClass)->getCount(where: $where, groupBy: $groupBy);
	}

	/**
	 * @param string $entityClass
	 * @param string $aggregationColumn
	 * @param string|null $alias
	 * @param string|null $where
	 * @param string|array|null $groupBy
	 * @return int
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function getSum(string            $entityClass,
						   string            $aggregationColumn = 'sum(*) AS sum',
						   string|null       $alias = "sum",
						   string|null       $where = null,
						   string|array|null $groupBy = null
	): int {
		return $this->getRepository($entityClass)->getSum($aggregationColumn, $alias, $where, $groupBy);

	}

	/**
	 * @param string $entityClass
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
	 * @throws TypeError
	 * @throws EntityError
	 */
	public function getList(
		string            $entityClass,
		string|null       $where = null,
		string|array|null $orderBy = null,
		int|null          $limit = null,
		int|null          $offset = null,
		string|array|null $groupBy = null,
		array|null        $addColumns = null):
	array {
		return $this->getRepository($entityClass)->getList($where, $orderBy, $limit, $offset, $groupBy, $addColumns);
	}

	/**
	 * @param string $entityClass
	 * @param array $parameters
	 * @param string|null $where
	 * @param string|null $groupBy
	 * @return array
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws TypeError
	 * @throws EntityError
	 */
	public function getListByParameters(
		string      $entityClass,
		array       $parameters,
		string|null $where,
		string|null $groupBy = null
	): array {
		return $this->getRepository($entityClass)->getListByParameters($parameters, $where, $groupBy);
	}

	/**
	 * @param IEntity $entity
	 * @return int|null
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function insert(IEntity $entity): ?int {
		$entityClass = get_class($entity);
		return $this->getRepository($entityClass)->insert($entity);
	}

	/**
	 * @param IEntity $entity
	 * @param string $where
	 * @return bool
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function update(IEntity $entity, string $where): bool {
		$entityClass = get_class($entity);
		return $this->getRepository($entityClass)->update($entity, $where);
	}

	/**
	 * @param IEntity $entity
	 * @param int $id
	 * @return bool
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function updateById(IEntity $entity, int $id): bool {
		$entityClass = get_class($entity);
		return $this->getRepository($entityClass)->updateById($entity, $id);
	}

	/**
	 * @param IEntity $entity
	 * @return bool
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function updateEntity(IEntity $entity): bool {
		$entityClass = get_class($entity);
		return $this->getRepository($entityClass)->updateEntity($entity);
	}

	/**
	 * @param string $entityClass
	 * @param int $id
	 * @return bool
	 * @throws ReflectionException
	 */
	public function deleteById(string $entityClass, int $id): bool {
		return $this->getRepository($entityClass)->deleteById($id);
	}

	/**
	 * @param string $entityClass
	 * @param string $where
	 * @return bool
	 * @throws SQLError|ReflectionException
	 */
	public function delete(string $entityClass, string $where): bool {
		return $this->getRepository($entityClass)->delete($where);
	}

	/**
	 * @param IEntity $entity
	 * @return bool
	 * @throws ReflectionException
	 */
	public function deleteEntity(IEntity $entity): bool {
		$entityClass = get_class($entity);
		return $this->getRepository($entityClass)->deleteEntity($entity);
	}

	/**
	 * @param IEntity $entity
	 * @return string
	 * @throws ReflectionException
	 */
	public function entity2json(IEntity $entity): string {
		return $this->getRepository($entity)->entity2json($entity);
	}

	/**
	 * @param IEntity $entity
	 * @param array $data
	 * @return IEntity
	 * @throws ReflectionException
	 */
	public function array2entity(IEntity $entity, array $data): IEntity {
		return $this->getRepository($entity)->array2entity($data);
	}
}
