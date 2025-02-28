<?php

namespace QuickFeather\Html\Table;

use Closure;
use DateTime;
use JetBrains\PhpStorm\Immutable;
use QuickFeather\EntityManager\IEntity;
use QuickFeather\Routing\Linker;
use RuntimeException;


class Column {

	public static mixed $render;
	public static mixed $renderDate;
	public static mixed $renderDateTime;
	public static mixed $renderNumber;
	public static mixed $renderBool;
	public static mixed $renderEmail;
	public static mixed $renderCurrency;
	public static mixed $renderPhone;
	public static mixed $renderButton;
	public static mixed $renderLink;
	public static Closure $renderMonth;

	#[Immutable]
	private string $name;
	#[Immutable]
	private $previewRender;
	#[Immutable]
	private ?Linker $link;
	#[Immutable]
	private ?string $label;
	#[Immutable]
	private ?string $cssClass;
	#[Immutable]
	private ?string $confirmMessage;
	#[Immutable]
	private ?bool $sort;
	#[Immutable]
	private ?string $suffix;

	/**
	 * @param string $name
	 * @param callable $previewRender
	 * @param Linker|null $link
	 * @param string|null $label
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $sort
	 * @param string|null $suffix
	 */
	public function __construct(string  $name, callable $previewRender, ?Linker $link = null, ?string $label = null, ?string $cssClass = null,
								?string $confirmMessage = null, ?bool $sort = true, ?string $suffix = null) {
		$this->name = $name;
		$this->previewRender = $previewRender;
		$this->link = $link;
		$this->label = $label;
		$this->cssClass = $cssClass;
		$this->confirmMessage = $confirmMessage;
		$this->sort = $sort;
		$this->suffix = $suffix;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return callable
	 */
	public function getPreviewRender(): callable {
		return $this->previewRender;
	}

	/**
	 * @return Linker|null
	 */
	public function getLink(): ?Linker {
		return $this->link;
	}

	/**
	 * @return string|null
	 */
	public function getLabel(): ?string {
		return $this->label;
	}

	/**
	 * @return string|null
	 */
	public function getCssClass(): ?string {
		return $this->cssClass;
	}

	/**
	 * @return string|null
	 */
	public function getConfirmMessage(): ?string {
		return $this->confirmMessage;
	}

	/**
	 * @return bool|null
	 */
	public function getSort(): ?bool {
		return $this->sort;
	}

	/**
	 * @return string|null
	 */
	public function getSuffix(): ?string {
		return $this->suffix;
	}

	/**
	 * @return void
	 */
	public static function init(): void {

		self::$render = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			$value .= $column->getSuffix();
			return $value;
		};

		self::$renderDate = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			try {
				if ($value === null || $value === '') {
					return '';
				}
				if (!($value instanceof DateTime)) {
					$value = new DateTime($value);
				}
				return $value->format(DATE_CZ) . $column->getSuffix();
			} catch (RuntimeException) {
				return '';
			}
		};

		self::$renderDateTime = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			try {
				if ($value === null || $value === '') {
					return '';
				}
				if (!($value instanceof DateTime)) {
					$value = new DateTime($value);
				}
				return $value->format(DATETIME_CZ) . $column->getSuffix();
			} catch (RuntimeException) {
				return '';
			}
		};

		self::$renderNumber = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);

			if ($value instanceof IEntity) {
				$value = (string)$value;
			}
			if ($value !== null) {
				return '<span style=\'text-align: right;width: 100%;display: block;\'>' . number_format((float)$value) . $column->getSuffix() . '</span>';
			}
			return '';
		};

		self::$renderCurrency = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			if ($value !== null) {
				return '<span style=\'text-align: right;width: 100%;display: block;\'>' . number_format((float)$value, 2) . $column->getSuffix() . _(' Kč') . '</span>';
			}
			return '';
		};

		self::$renderBool = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			if ($value === true) {
				return '<span class=\'badge bg-success fs-8 bool\'><span class="fa fa-check"></span> ' . _('ano') . '</span>';
			}
			if ($value === false) {
				return '<span class=\'badge bg-danger fs-8 bool\'><span class="fa fa-close"></span> ' . _('ne') . '</span>';
			}
			return '<span class=\'badge bg-warning fs-8 bool\'><span class="fa fa-question"></span> ' . _('neurčeno') . '</span>';
		};

		self::$renderEmail = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			return "<a href=\"mailto:{$value}\" class=\"mail\" >{$value}</a>" . $column->getSuffix();
		};

		self::$renderPhone = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			return "<a href=\"tel:{$value}\" class=\"mail\" >{$value}</a>" . $column->getSuffix();
		};

		self::$renderLink = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			return "<a href=\"{$value}\" class=\"link\" target='_blank'>{$value}</a>" . $column->getSuffix();
		};

		self::$renderMonth = static function (self $column, array|IEntity $values): string {
			$value = self::getValueFromValues($column->getName(), $values);
			return match ((int)$value) {
				1 => "1 - leden,",
				2 => "2 - únor",
				3 => "3 - březen",
				4 => "4 - duben",
				5 => "5 - květen",
				6 => "6 - červen",
				7 => "7 - červenec",
				8 => "8 - srpen",
				9 => "9 - září",
				10 => "10 - říjen",
				11 => "11 - listopad",
				12 => "12 - prosinec",
				default => $value,
			};
		};

		self::$renderButton = static function (self $column): string {
			return "<span>{$column->getSuffix()}</span>";
		};
	}

	/**
	 * @param array|IEntity $values
	 * @return string
	 */
	public function render(array|IEntity $values): string {
		$str = '';

		if ($this->link) {
			if ($this->confirmMessage !== null && $this->confirmMessage !== '') {
				$confirmMessage = "onclick=\"return confirm('{$this->confirmMessage}');\"";
			} else {
				$confirmMessage = '';
			}

			foreach ($this->link->getGetVarsDynamic() as $name => $columnDefine) {
				$value = self::getValueFromValues($columnDefine, $values);
				if ($value !== null) {
					$this->link->addGetVar($name, $value);
				}
			}
			foreach ($this->link->getSeoVarsDynamic() as $index => $columnDefine) {
				$value = self::getValueFromValues($columnDefine, $values);
				if ($value !== null) {
					$this->link->setSeoVar($value, $index);
				}
			}

			$str .= "<a class=\"{$this->cssClass}\" href=\"{$this->link->toString()}\" ";
			$str .= "title=\"{$this->label}\" {$confirmMessage} {$this->link->getTarget()}>";
		}

		$renderFunction = $this->previewRender;
		if ($renderFunction !== null && is_callable($renderFunction)) {
			$str .= $renderFunction($this, $values);
		}

		if ($this->link) {
			$str .= "</a>\n";
		}

		return $str;
	}

	/**
	 * @param string $columnName
	 * @param array|IEntity $values
	 * @return mixed
	 */
	public static function getValueFromValues(string $columnName, array|IEntity $values): mixed {
		if ($columnName === '') {
			return null;
		}

		if (str_contains($columnName, '.')) {
			$parts = explode('.', $columnName);
			if ($parts === null) {
				return null;
			}
			if (count($parts) === 1) {
				return $values->$columnName;
			}
			$columnName = array_shift($parts);
			$value = $values->$columnName;
			foreach ($parts as $part) {
				$value = $value->$part;
			}
			return $value;
		}

		if (is_array($values)) {
			return $values[$columnName];
		}

		if ($values instanceof IEntity) {
			return $values->$columnName;
		}

		return null;
	}
}
