<?php

namespace QuickFeather\Routing;

use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Primitive\IntType;
use QuickFeather\EntityManager\Type\Primitive\StringType;


class Linker {

	static private ?bool $ajaxRequest = null;

	private array $getVars;
	private array $getVarsDynamic = [];
	private array $seoVars;
	private array $seoVarsDynamic = [];
	private string $target = '';
	private string $jsLink = '';
	private string $baseUrl;

	/**
	 * @param string $baseUrl
	 * @param array|null $seoVars
	 * @param array|null $getVars
	 */
	public function __construct(string $baseUrl, ?array $seoVars = [], ?array $getVars = []) {
		$this->baseUrl = $baseUrl;
		$this->seoVars = $seoVars;
		$this->getVars = $getVars;
	}

	/**
	 * @param string $name
	 * @param string|int|float|bool $value
	 * @return void
	 */
	public function addGetVar(string $name, string|int|float|bool $value): void {
		$this->getVars[$name] = $value;
	}

	/**
	 * @param string $name
	 * @param string $reference
	 * @return void
	 */
	public function addGetVarDynamically(string $name, string $reference): void {
		$this->getVarsDynamic[$name] = $reference;
	}

	/**
	 * @param string $name
	 * @param string|int|float|bool $value
	 * @return void
	 */
	public function setGetVar(string $name, string|int|float|bool $value): void {
		$this->getVars[$name] = $value;
	}

	/**
	 * @param string $value
	 * @param bool $toBegin
	 * @return void
	 */
	public function addSeoVar(string $value, bool $toBegin = false): void {
		if (!$toBegin) {
			$this->seoVars[] = $value;
		} else {
			array_unshift($this->seoVars, $value);
		}
	}

	/**
	 * @param string|array $key
	 * @param int|null $index
	 * @return void
	 */
	public function addSeoVarDynamically(string|array $key, ?int $index = 0): void {
		$this->seoVarsDynamic[$index] = $key;
	}

	/**
	 * @param string $value
	 * @param int $index
	 * @return void
	 */
	public function setSeoVar(string $value, int $index): void {
		$this->seoVars[$index] = $value;
	}

	/**
	 * @return void
	 */
	public function clearAllVars(): void {
		$this->getVars = [];
		$this->getVarsDynamic = [];
		$this->seoVars = [];
		$this->seoVarsDynamic = [];
	}

	/**
	 * @param string $target
	 * @return void
	 */
	public function setTarget(string $target): void {
		$this->target = $target;
	}

	/**
	 * @return string
	 */
	public function getTarget(): string {
		if ($this->target !== '') {
			return " target=\"" . $this->target . "\"";
		}
		return '';
	}

	/**
	 * @param int|null $index
	 * @return string|array
	 */
	public function getSeoVars(int $index = null): string|array {
		if ($index !== null && $index >= 0) {
			return $this->seoVars[$index] ?? '';
		}

		return $this->seoVars;
	}

	/**
	 * @return array
	 */
	public function getAllStaticVars(): array {
		return $this->getVars;
	}

	/**
	 * @return string|null
	 */
	public function getJsLink(): ?string {
		return $this->jsLink;
	}

	/**
	 * @param string $jsLink
	 * @return void
	 */
	public function setJsLink(string $jsLink): void {
		$this->jsLink = $jsLink;
	}

	/**
	 * @param bool $use_html_entity
	 * @return string
	 */
	public function toString(bool $use_html_entity = true): string {
		if ($this->jsLink) {
			return $this->jsLink;
		}
		if ($use_html_entity) {
			$glue = '&amp;';
		} else {
			$glue = '&';
		}

		$base = $this->baseUrl;
		if (count($this->seoVars) > 0) {
			ksort($this->seoVars);
			$base .= implode('/', $this->seoVars) . '.html';
		}
		if (count($this->getVars)) {
			$base .= '?';
			$vars = [];
			foreach ($this->getVars as $name => $value) {
				if ($value !== '') {
					$vars[] = $name . '=' . $value;
				}
			}
			return $base . implode($glue, $vars);
		}
		return $base;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->toString();
	}

	/**
	 * @return bool
	 */
	public static function isPost(): bool {
		return ($_SERVER['REQUEST_METHOD'] ?? null) === 'POST';
	}

	/**
	 * @return bool|null
	 */
	public static function isAjaxRequest(): ?bool {
		if (self::$ajaxRequest === null) {
			$way = StringType::fromGet('way', backSlash: BaseType::remove, slash: BaseType::remove,
				quote: BaseType::remove, whiteSpace: BaseType::remove, html: BaseType::remove,
				diacritic: BaseType::remove, specialChar: BaseType::remove
			);
			$draw = IntType::fromPost('draw');

			if ($way === 'ajax' || $way === 'json') {
				self::$ajaxRequest = true;
			} else if ($draw > 0) {
				self::$ajaxRequest = true;
			} else {
				self::$ajaxRequest = false;
			}
		}
		return self::$ajaxRequest;
	}

	/**
	 * @return array
	 */
	public function getGetVarsDynamic(): array {
		return $this->getVarsDynamic;
	}

	/**
	 * @return array
	 */
	public function getSeoVarsDynamic(): array {
		return $this->seoVarsDynamic;
	}


}
