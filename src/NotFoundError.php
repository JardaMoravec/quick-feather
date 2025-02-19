<?php

namespace QuickFeather;

use Exception;
use RuntimeException;

class NotFoundError extends RuntimeException {

	/**
	 * DataException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Exception|null $previous
	 */
	public function __construct(string $message = '', int $code = 0, Exception $previous = null) {
		parent::__construct($message === '' ? 'Not found!' : $message, $code, $previous);
	}
}
