<?php

namespace QuickFeather\EntityManager\Error;


use RuntimeException;

class DataError extends RuntimeException {

	/**
	 * @param string $message
	 * @param int $code
	 * @param RuntimeException|null $previous
	 */
	public function __construct(string $message = '', int $code = 0, RuntimeException $previous = null) {
		parent::__construct($message === '' ? 'Data validation error!' : $message, $code, $previous);
	}
}
