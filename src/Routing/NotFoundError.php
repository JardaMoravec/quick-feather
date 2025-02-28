<?php

namespace QuickFeather\Routing;


use RuntimeException;

class NotFoundError extends RuntimeException {

	/**
	 * DataException constructor.
	 * @param string $message
	 * @param int $code
	 * @param RuntimeException|null $previous
	 */
	public function __construct(string $message = '', int $code = 0, RuntimeException $previous = null) {
		parent::__construct($message === '' ? 'Not found!' : $message, $code, $previous);
	}
}
