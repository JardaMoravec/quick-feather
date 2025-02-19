<?php

namespace QuickFeather\EventLogger;

use QuickFeather\Current;
use Throwable;


interface IEventLogger {

	/**
	 * @param string $message
	 * @param string $type
	 * @param Current|null $user
	 * @return int
	 */
	public function logEvent(string $message, string $type, Current|null $user = null): int;

	/**
	 * @param Throwable $e
	 * @param Current|null $user
	 * @return int
	 */
	public function logException(Throwable $e, Current|null $user = null): int;

	/**
	 * @param Throwable $e
	 * @param Current|null $user
	 * @return void
	 */
	public function logFatalException(Throwable $e, Current|null $user = null): void;
}
