<?php

namespace QuickFeather;

use Exception;
use RuntimeException;

class PermissionError extends RuntimeException {

	/**
	 * PermissionException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Exception|null $previous
	 */
	public function __construct(string $message = "", int $code = 0, Exception $previous = null) {
		parent::__construct($message === '' ? 'Permission denied!' : $message, $code, $previous);
	}
}
