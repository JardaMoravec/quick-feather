<?php

namespace RmamespaceR;

use Dao\BaseDao;
use PDO;
use RentityNamespaceR;
use JetBrains\PhpStorm\Immutable;
use ReflectionException;
use Tool\dbEntity\Error\SQLError;


class Rclase_nameR extends BaseDao {

	#[Immutable]
	protected static string $entity = RentityR::class;

	/**
	 * @param array $data
	 * @return RentityR
	 */
	public static function array2entity(array $data): RentityR {
		return new RentityR (
/*FROM_ARRAY_PARAM*/
		);
	}

	/**
	 * @param PDO $pdo
	 * @param int $id
	 * @return ?RentityR|null
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public static function getOneById(PDO $pdo, int $id): ?RentityR {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::getOneById( $pdo, $id);
	}

}
