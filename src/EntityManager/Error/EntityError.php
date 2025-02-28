<?php

namespace QuickFeather\EntityManager\Error;

use RuntimeException;


class EntityError extends DataError {

	private readonly string $entity;
	private readonly ?string $filter;

	/**
	 * @param string $message
	 * @param string $entity
	 * @param string|null $filter
	 * @param int|null $code
	 * @param RuntimeException|null $previous
	 */
	public function __construct(string $message, string $entity, ?string $filter = null, ?int $code = 0, ?RuntimeException $previous = null) {
		parent::__construct($message, $code, $previous);

		$this->entity = $entity;
		$this->filter = $filter;
	}

	/**
	 * @return string
	 */
	public function getEntity(): string {
		return $this->entity;
	}

	/**
	 * @return string|null
	 */
	public function getFilter(): ?string {
		return $this->filter;
	}
}
