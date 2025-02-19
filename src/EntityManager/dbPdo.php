<?php

namespace QuickFeather\EntityManager;

use PDO;
use PDOException;
use PDOStatement;


class dbPdo extends PDO {

	private array $queryLog = [];
	private bool $debug;

	/**
	 * (PHP 5 &gt;= 5.1.0, PHP 7, PECL pdo &gt;= 0.1.0)<br/>
	 * Creates a PDO instance representing a connection to a database
	 * @link https://php.net/manual/en/pdo.construct.php
	 * @param string $dsn
	 * @param string $username [optional]
	 * @param string $password [optional]
	 * @param array $options [optional]
	 * @param bool $debug [optional]
	 * @throws PDOException if the attempt to connect to the requested database fails.
	 */
	public function __construct(string $dsn, $username = null, $password = null, $options = null, bool $debug = false) {
		parent::__construct($dsn, $username, $password, $options);

		$this->debug = $debug;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0, PHP 7, PECL pdo &gt;= 0.2.0)<br/>
	 * Executes an SQL statement, returning a result set as a PDOStatement object
	 * @link https://php.net/manual/en/pdo.query.php
	 * @param string $query <p>
	 * The SQL statement to prepare and execute.
	 * </p>
	 * <p>
	 * Data inside the query should be properly escaped.
	 * </p>
	 * @param int|null $fetchMode <p>
	 * The fetch mode must be one of the PDO::FETCH_* constants.
	 * </p>
	 * @param mixed $fetch_mode_args <p>
	 * Arguments of custom class constructor when the <i>mode</i>
	 * parameter is set to <b>PDO::FETCH_CLASS</b>.
	 * </p>
	 * @return PDOStatement|false <b>PDO::query</b> returns a PDOStatement object, or <b>FALSE</b>
	 * on failure.
	 * @see PDOStatement::setFetchMode For a full description of the second and following parameters.
	 */

	public function query(string $query, int|null $fetchMode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args): PDOStatement|false {
		if ($this->debug) {
			$start = microtime(true);
			$sta  = parent::query($query);
			$end = microtime(true);
			$this->queryLog[] = [
				'query' => $query,
				'time' => ($end - $start),
				'parameters' => '',
				'rowCount' => $sta->rowCount(),
			];
			return $sta;
		}
		return parent::query($query);
	}

	/**
	 * @return array
	 */
	public function getQueryLog(): array {
		return $this->queryLog;
	}
}
