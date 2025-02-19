<?php

namespace QuickFeather\Html\Table;

use Exception;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\IEntity;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Primitive\StringType;
use RuntimeException;
use Tool\Linker;


class Table {

	public const SORT_ASC = 'asc';
	public const SORT_DESC = 'desc';

	/**
	 * @param string $name
	 * @param callable|null $previewRender
	 * @param Linker|null $link
	 * @param string|null $label
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $sort
	 * @param string|null $suffix
	 * @return Column
	 */
	public static function createColumn(string  $name, ?callable $previewRender = null, ?Linker $link = null, ?string $label = null, ?string $cssClass = null,
										?string $confirmMessage = null, ?bool $sort = true, ?string $suffix = null): Column {
		if ($previewRender === null) {
			$previewRender = Column::$render;
		}
		return new Column($name, $previewRender, $link, $label, $cssClass, $confirmMessage, $sort, $suffix);
	}

	/**
	 * @param Linker $link
	 * @param string|null $name
	 * @param callable|null $previewRender
	 * @param string|null $label
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $sort
	 * @param string|null $suffix
	 * @return Column
	 */
	public static function createEditColumn(Linker  $link, ?string $name = null, ?callable $previewRender = null, ?string $label = null,
											?string $cssClass = null, ?string $confirmMessage = null, ?bool $sort = null, ?string $suffix = null): Column {
		if ($name === null || $name === '') {
			$name = "edit";
		}
		if ($previewRender === null) {
			$previewRender = Column::$renderButton;
		}
		if ($label === null || $label === '') {
			$label = _('Editovat');
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'btn bg-blue text-white';
		}
		if ($sort === null) {
			$sort = false;
		}
		if ($suffix === null || $suffix === '') {
			$suffix = "<span class=\"fa fa-pencil\"></span>";
		}

		return new Column($name, $previewRender, $link, $label, $cssClass, $confirmMessage, $sort, $suffix);
	}

	/**
	 * @param Linker $link
	 * @param string|null $name
	 * @param callable|null $previewRender
	 * @param string|null $label
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $sort
	 * @param string|null $suffix
	 * @return Column
	 */
	public static function createDeleteColumn(Linker  $link, ?string $name = null, ?callable $previewRender = null, ?string $label = null,
											  ?string $cssClass = null, ?string $confirmMessage = null, ?bool $sort = null, ?string $suffix = null): Column {
		if ($name === null || $name === '') {
			$name = "delete";
		}
		if ($previewRender === null) {
			$previewRender = Column::$renderButton;
		}
		if ($label === null || $label === '') {
			$label = _('Smazat');
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'btn btn-danger';
		}
		if ($confirmMessage === null || $confirmMessage === '') {
			$confirmMessage = _('Opravdu chcete toto smazat!');
		}
		if ($sort === null) {
			$sort = false;
		}
		if ($suffix === null || $suffix === '') {
			$suffix = "<span class=\"fa fa-trash-o\"></span>";
		}

		return new Column($name, $previewRender, $link, $label, $cssClass, $confirmMessage, $sort, $suffix);
	}

	/**
	 * @param string $name
	 * @param array $columns
	 * @param string|null $cssClass
	 * @param int|null $sortByColumnPosition
	 * @param string|null $sortWay
	 * @throws Exception
	 */
	public static function createHeader(string $name, array $columns, ?string $cssClass = null, ?int $sortByColumnPosition = null, ?string $sortWay = null): void {
		if (count($columns) === 0) {
			throw new RuntimeException('Column count must be greater than 0!');
		}

		if ($sortByColumnPosition === null) {
			$sortByColumnPosition = 0;
		}
		if ($sortWay === null) {
			$sortWay = self::SORT_ASC;
		}

		echo "\n<script type=\"text/javascript\">$(function () {\n" .
			"const table = new DataTable('#grid_{$name}', {\n" .
			"    order: [[ {$sortByColumnPosition}, '{$sortWay}' ]],\n" .
			"    processing: true,\n" .
			"    serverSide: true,\n" .
			"    stateSave: true,\n" .
			"    pagingType: 'full_numbers',\n" .
			"    pageLength: itemCountOnPage ?? 50,\n" .
			"    ajax: {url: window.location, type: \"POST\"},\n" .
			"    language: dataTableTranslation,\n";

		/** @var Column $column */
		echo "\tcolumns: [\n";
		foreach ($columns as $column) {
			if ($column->getSort()) {
				echo "\t\t{\"data\": \"{$column->getName()}\", \"orderable\": \"true\"},\n";
			} else {
				echo "\t\t{\"data\": \"{$column->getName()}\", orderable: false, targets: -1},\n";
			}
		}
		echo "    ]\n";
		echo "\t});\n";

		echo "});\n";
		echo "</script>\n";

		echo "<div class=\"card\">\n";
		echo "<div class=\"card-body\">\n";
		echo "<table id=\"grid_{$name}\" class=\"{$cssClass} table table-striped table-hover display \" role=\"grid\" >\n";

		// header
		echo "<thead>\n";
		echo "<tr>\n";
		/** @var Column $coll */
		foreach ($columns as $coll) {
			if ($coll->getSort()) {
				echo "<th class=\"sorting\" >" . $coll->getLabel() . "</th>\n";
			} else {
				echo "<td class=\"no-sorting\"></td>\n";
			}
		}
		echo "</tr>\n";
		echo "</thead>\n";
		echo "</table>\n";
		echo "</div>\n";
		echo "</div>\n";
	}

	/**
	 * @param array $columns
	 * @param array $values
	 * @param int $totalRowCount
	 * @param int $displayRowCount
	 * @throws Exception
	 */
	public static function createAjax(array $columns, array $values, int $totalRowCount, int $displayRowCount): void {

		$output["draw"] = filter_input(INPUT_POST, 'draw', FILTER_VALIDATE_INT);
		$output["recordsTotal"] = $totalRowCount;
		$output["recordsFiltered"] = $displayRowCount;
		$output['data'] = [];

		if (count($values) > 0) {
			foreach ($values as $row) {
				if (is_array($row)) {
					$rec = [];
					foreach ($columns as $column) {
						$rec[] = $column->render($row);
					}
					$output['data'][] = $rec;
				} else if ($row instanceof IEntity) {
					$rec = [];
					/** @var Column $column */
					foreach ($columns as $column) {
						$rec[$column->getName()] = $column->render($row);
					}
					$output['data'][] = $rec;
				} else {
					throw new RuntimeException('Data array is not array!');
				}
			}
		}
		echo json_encode($output, JSON_THROW_ON_ERROR);
	}

	/**
	 * @param array $columns
	 * @return array
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 */
	public static function getParameters(array $columns): array {
		$args = [
			'columns' => [
				'filter' => FILTER_SANITIZE_ADD_SLASHES,
				'flags' => FILTER_REQUIRE_ARRAY,
			],
			'order' => [
				'filter' => FILTER_SANITIZE_ADD_SLASHES,
				'flags' => FILTER_REQUIRE_ARRAY,
			],
			'search' => [
				'regexp' => FILTER_SANITIZE_ADD_SLASHES,
				'flags' => FILTER_REQUIRE_ARRAY,
			],
			'draw' => FILTER_VALIDATE_INT,
			'length' => FILTER_VALIDATE_INT,
			'start' => FILTER_SANITIZE_ADD_SLASHES,
			'filter' => [
				'filter' => FILTER_SANITIZE_ADD_SLASHES,
				'flags' => FILTER_REQUIRE_ARRAY,
			],
		];
		$parameters = filter_input_array(INPUT_POST, $args);

		$result['from'] = (int)$parameters['start'];
		$result['count'] = (int)$parameters['length'];

		$columnCount = count($columns);
		for ($i = 0; $i < $columnCount; $i++) {
			$searchable = $parameters['columns'][$i]['searchable'];
			$search = $parameters['columns'][$i]['search']['value'];
			if ($search === null || $search === "") {
				$search = $parameters['search']['value'];
			}
			if ($searchable === "true" && $search !== "" && $search !== null) {
				/** @var Column $column */
				$column = $columns[$i];
				if ($column->getName() !== null && $column->getName() !== '' && $column->getSort()) {
					$result['condition'][$column->getName()] = StringType::fromVar($search, quote: BaseType::remove, separator: BaseType::remove);
				}
			}
		}

		$orders = [];
		if ($parameters['order'] && $parameters['order'][0]['column'] !== '') {
			if ($parameters['order'][0]['dir'] !== 'asc' && $parameters['order'][0]['dir'] !== 'desc') {
				$dir = 'asc';
			} else {
				$dir = $parameters['order'][0]['dir'];
			}
			$col = $columns[$parameters['order'][0]['column']];
			if ($col) {
				$orders[$col->getName()] = $dir;
			}
		} else {
			$orders['id'] = "ASC";
		}
		$result['order'] = $orders;

		return $result;
	}
}
