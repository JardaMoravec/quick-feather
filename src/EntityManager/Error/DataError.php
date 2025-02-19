<?php

namespace QuickFeather\EntityManager\Error;

use Exception;
use RuntimeException;

class DataError extends RuntimeException {

	/**
	 * DataException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Exception|null $previous
	 */
	public function __construct(string $message = '', int $code = 0, Exception $previous = null) {
		parent::__construct($message === '' ? 'Data validation error!' : $message, $code, $previous);
	}
}
