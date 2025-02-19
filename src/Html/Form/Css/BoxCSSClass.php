<?php

namespace QuickFeather\Html\Form\Css;

class BoxCSSClass {
	public array $box;
	public array $header;
	public array $body;

	public function __construct(array $box = ['card mb-3'], array $header = ['card-header'], array $body = ['card-body']) {
		$this->box = $box;
		$this->header = $header;
		$this->body = $body;
	}

	/**
	 * Přepíše výchozí hodnoty pro box.
	 *
	 * @param array $box
	 * @return void
	 */
	public function setBox(array $box): void {
		$this->box = $box;
	}

	/**
	 * Přidá třídu do boxu.
	 *
	 * @param string $class
	 * @return void
	 */
	public function addBox(string $class): void {
		$this->box[] = $class;
	}

	/**
	 * Přepíše výchozí hodnoty pro header.
	 *
	 * @param array $header
	 * @return void
	 */
	public function setHeader(array $header): void {
		$this->header = $header;
	}

	/**
	 * Přidá třídu do header.
	 *
	 * @param string $class
	 * @return void
	 */
	public function addHeader(string $class): void {
		$this->header[] = $class;
	}

	/**
	 * Přepíše výchozí hodnoty pro body.
	 *
	 * @param array $body
	 * @return void
	 */
	public function setBody(array $body): void {
		$this->body = $body;
	}

	/**
	 * Přidá třídu do body.
	 *
	 * @param string $class
	 * @return void
	 */
	public function addBody(string $class): void {
		$this->body[] = $class;
	}
}
