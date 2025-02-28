<?php

namespace QuickFeather\Routing;


use RuntimeException;

class PermissionError extends RuntimeException {

	/**
	 * PermissionException constructor.
	 * @param string $message
	 * @param int $code
	 * @param RuntimeException|null $previous
	 */
	public function __construct(string $message = "", int $code = 0, RuntimeException $previous = null) {
		parent::__construct($message === '' ? 'Permission denied!' : $message, $code, $previous);
	}
}
