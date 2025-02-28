<?php

namespace QuickFeather\Html\Table;

use DateTime;
use JetBrains\PhpStorm\Immutable;
use QuickFeather\EntityManager\IEntity;
use QuickFeather\Routing\Linker;
use RuntimeException;


class TableColumn {

	public static mixed $render = [self::class, 'defaultRender'];
	public static mixed $renderDate = [self::class, 'renderDate'];
	public static mixed $renderDateTime = [self::class, 'renderDateTime'];
	public static mixed $renderNumber = [self::class, 'renderNumber'];
	public static mixed $renderBool = [self::class, 'renderBool'];
	public static mixed $renderEmail = [self::class, 'renderEmail'];
	public static mixed $renderCurrency = [self::class, 'renderCurrency'];
	public static mixed $renderPhone = [self::class, 'renderPhone'];
	public static mixed $renderButton = [self::class, 'renderButton'];
	public static mixed $renderLink = [self::class, 'renderLink'];
	public static mixed $renderMonth = [self::class, 'renderMonth'];


	#[Immutable]
	private string|array $name;
	#[Immutable]
	private mixed $previewRender;
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
	 * @param string|array $name
	 * @param callable|array|null $previewRender
	 * @param Linker|null $link
	 * @param string|null $label
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $sort
	 * @param string|null $suffix
	 */
	public function __construct(string|array        $name,
								callable|array|null $previewRender = null,
								Linker|null         $link = null,
								string|null         $label = null,
								string|null         $cssClass = null,
								string|null         $confirmMessage = null,
								bool|null           $sort = true,
								string|null         $suffix = null
	) {
		$this->name = $name;
		if ($previewRender !== null) {
			$this->previewRender = $previewRender;
		} else {
			$this->previewRender = null;
		}
		$this->link = $link;
		$this->label = $label;
		$this->cssClass = $cssClass;
		$this->confirmMessage = $confirmMessage;
		$this->sort = $sort;
		$this->suffix = $suffix;
	}

	/**
	 * @return string|array
	 */
	public function getName(): string|array {
		return $this->name;
	}

	/**
	 * @return callable|null
	 */
	public function getPreviewRender(): callable|null {
		return $this->previewRender !== null && is_callable($this->previewRender) ? $this->previewRender : null;
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
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function defaultRender(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		$value .= $column['suffix'];
		return $value;
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 * @throws RuntimeException
	 */
	public static function dateRender(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		try {
			if ($value === null || $value === '') {
				return '';
			}
			if (!($value instanceof DateTime)) {
				$value = new DateTime($value);
			}
			return $value->format(DATE_CZ) . $column['suffix'];
		} catch (RuntimeException) {
			return '';
		}
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderDateTime(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		try {
			if ($value === null || $value === '') {
				return '';
			}
			if (!($value instanceof DateTime)) {
				$value = new DateTime($value);
			}
			return $value->format(DATETIME_CZ) . $column['suffix'];
		} catch (RuntimeException) {
			return '';
		}
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderNumber(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);

		if ($value instanceof IEntity) {
			$value = (string)$value;
		}
		if ($value !== null) {
			return '<span style=\'text-align: right;width: 100%;display: block;\'>' . number_format((float)$value) . $column['suffix'] . '</span>';
		}
		return '';
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderCurrency(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		if ($value !== null) {
			return '<span style=\'text-align: right;width: 100%;display: block;\'>' . number_format((float)$value, 2) . $column['suffix'] . _(' Kč') . '</span>';
		}
		return '';
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderBool(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		if ($value === true) {
			return '<span class=\'badge bg-success fs-8 bool\'><span class="fa fa-check"></span> ' . _('ano') . '</span>';
		}
		if ($value === false) {
			return '<span class=\'badge bg-danger fs-8 bool\'><span class="fa fa-close"></span> ' . _('ne') . '</span>';
		}
		return '<span class=\'badge bg-warning fs-8 bool\'><span class="fa fa-question"></span> ' . _('neurčeno') . '</span>';
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderEmail(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		return "<a href=\"mailto:{$value}\" class=\"mail\" >{$value}</a>" . $column['suffix'];
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderPhone(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		return "<a href=\"tel:{$value}\" class=\"mail\" >{$value}</a>" . $column['suffix'];
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderLink(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
		return "<a href=\"{$value}\" class=\"link\" target='_blank'>{$value}</a>" . $column['suffix'];
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public static function renderMonth(array $column, array|IEntity $values): string {
		$value = self::getValueFromValues($column, $values);
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
	}

	/**
	 * @param array $column
	 * @return string
	 */
	public static function renderButton(array $column): string {
		return "<span>{$column['suffix']}</span>";
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return mixed
	 */
	public static function getValueFromValues(array $column, array|IEntity $values): mixed {
		if ($column['name'] === '') {
			return null;
		}

		if ($column['isRemote'] === true && is_array($column['name'])) {
			$parts = $column['name'];
			if (count($parts) === 1) {
				$columnName = $column['name'][0];
				return $values->$columnName;
			}

			$columnName = array_shift($parts);
			$value = $values->$columnName;

			foreach ($parts as $part) {
				$value = (string)$value->$part;
			}
			return $value;
		}

		if (is_array($values)) {
			return $values[$column['name']];
		}

		if ($values instanceof IEntity) {
			$columnName = $column['name'];
			return $values->$columnName;
		}

		return null;
	}
}
