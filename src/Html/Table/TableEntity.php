<?php

namespace QuickFeather\Html\Table;

use JsonException;
use QuickFeather\EntityManager\IEntity;
use QuickFeather\EntityManager\Repository;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Complex\Email;
use QuickFeather\EntityManager\Type\Complex\Phone;
use QuickFeather\EntityManager\Type\Complex\TimeStamp\Date;
use QuickFeather\EntityManager\Type\Complex\TimeStamp\DateTime;
use QuickFeather\EntityManager\Type\Primitive\StringType;
use RuntimeException;

// todo sort and search by remote columns
// todo remote columns link
class TableEntity {

	public const SORT_ASC = 'asc';
	public const SORT_DESC = 'desc';

	private array $columns = [];

	public function __construct(private readonly Repository  $repository,
								private string|null          $identifier = null,
								array|null                   $columns = [],
								private readonly string|null $cssClass = '',
								private readonly int|null    $sortByColumnPosition = 0,
								private readonly string|null $sortWay = self::SORT_ASC
	) {
		$fields = $this->repository->getProperties();

		foreach ($fields as $field) {
			/** @var TableColumn $column */
			foreach ($columns as $order => $column) {
				if (is_array($column->getName())) {
					$columnName = $column->getName()[0];
				} else {
					$columnName = $column->getName();
				}
				$fieldName = constant($this->repository->getEntityClass() . '::' . $field['name']);
				if ($columnName === $fieldName) {
					$fieldNew = $field;

					if (is_array($column->getName())) {
						$fieldNew['name'] = $column->getName();
						$fieldNew['name'][0] = $field['name'];
						if ($field['isRemote'] === false) {
							throw new RuntimeException('Field is not remote!');
						}
					} else if ($field['isRemote'] === true) {
						throw new RuntimeException('Field is remote!');
					}
					$fieldNew['label'] = $column->getLabel();
					$fieldNew['link'] = $column->getLink();
					$fieldNew['cssClass'] = $column->getCssClass();
					$fieldNew['confirmMessage'] = $column->getConfirmMessage();
					$fieldNew['sort'] = $column->getSort();
					$fieldNew['suffix'] = $column->getSuffix();
					if ($column->getPreviewRender() === null) {
						if ($field['type'] === 'int') {
							$fieldNew['previewRender'] = TableColumn::$renderNumber;
						} else if ($field['type'] === 'bool') {
							$fieldNew['previewRender'] = TableColumn::$renderBool;
						} else if ($field['type'] === DateTime::class) {
							$fieldNew['previewRender'] = TableColumn::$renderDateTime;
						} else if ($field['type'] === Date::class) {
							$fieldNew['previewRender'] = TableColumn::$renderDate;
						} else if ($field['type'] === Email::class) {
							$fieldNew['previewRender'] = TableColumn::$renderEmail;
						} else if ($field['type'] === Phone::class) {
							$fieldNew['previewRender'] = TableColumn::$renderPhone;
						} else {
							$fieldNew['previewRender'] = TableColumn::$render;
						}
					} else {
						$fieldNew['previewRender'] = $column->getPreviewRender();
					}
					$this->columns[$order] = $fieldNew;
				}
			}
		}

		foreach ($columns as $order => $column) {
			if ($column instanceof TableColumnEvent) {
				$fieldNew = [];
				$fieldNew['name'] = $column->getName();
				$fieldNew['label'] = $column->getLabel();
				$fieldNew['link'] = $column->getLink();
				$fieldNew['cssClass'] = $column->getCssClass();
				$fieldNew['confirmMessage'] = $column->getConfirmMessage();
				$fieldNew['sort'] = $column->getSort();
				$fieldNew['suffix'] = $column->getSuffix();
				if ($column->getPreviewRender() === null) {
					$fieldNew['previewRender'] = TableColumn::$renderButton;
				} else {
					$fieldNew['previewRender'] = $column->getPreviewRender();
				}
				$this->columns[$order] = $fieldNew;
			}
		}

		if (count($this->columns) === 0) {
			throw new RuntimeException('No columns found!');
		}
		ksort($this->columns);

		if ($this->identifier === null) {
			$this->identifier = 'table_' . StringType::fromVar($this->repository->getEntityClass(), all: BaseType::remove);
		}
	}

	/**
	 * @return void
	 */
	public function renderHeader(): void {
		if (count($this->columns) === 0) {
			throw new RuntimeException('Column count must be greater than 0!');
		}

		echo "\n<script type=\"text/javascript\">$(function () {\n" .
			"const table = new DataTable('#grid_{$this->identifier}', {\n" .
			"    order: [[ {$this->sortByColumnPosition}, '{$this->sortWay}' ]],\n" .
			"    processing: true,\n" .
			"    serverSide: true,\n" .
			"    stateSave: true,\n" .
			"    pagingType: 'full_numbers',\n" .
			"    pageLength: itemCountOnPage ?? 50,\n" .
			"    ajax: {url: window.location, type: \"POST\"},\n" .
			"    language: dataTableTranslation,\n";

		echo "\tcolumns: [\n";
		foreach ($this->columns as $column) {
			$columnName = is_array($column['name']) ? implode('-', $column['name']) : $column['name'];
			if ($column['sort']) {
				echo "\t\t{\"data\": \"{$columnName}\", \"orderable\": \"true\"},\n";
			} else {
				echo "\t\t{\"data\": \"{$columnName}\", orderable: false, targets: -1},\n";
			}
		}
		echo "    ]\n";
		echo "\t});\n";

		echo "});\n";
		echo "</script>\n";

		echo "<div class=\"card\">\n";
		echo "<div class=\"card-body\">\n";
		echo "<table id=\"grid_{$this->identifier}\" class=\"{$this->cssClass} table table-striped table-hover display \" role=\"grid\" >\n";

		// header
		echo "<thead>\n";
		echo "<tr>\n";

		foreach ($this->columns as $column) {
			if ($column['sort']) {
				echo "<th class=\"sorting\" >" . $column['label'] . "</th>\n";
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
	 * @param array $values
	 * @param int $totalRowCount
	 * @param int $displayRowCount
	 * @return void
	 */
	public function renderJsonBody(array $values, int $totalRowCount, int $displayRowCount): void {
		$output["draw"] = filter_input(INPUT_POST, 'draw', FILTER_VALIDATE_INT);
		$output["recordsTotal"] = $totalRowCount;
		$output["recordsFiltered"] = $displayRowCount;
		$output['data'] = [];

		if (count($values) > 0) {
			foreach ($values as $row) {
				if (is_array($row)) {
					$rec = [];
					foreach ($this->columns as $column) {
						$rec[] = $column->render($row);
					}
					$output['data'][] = $rec;
				} else if ($row instanceof IEntity) {
					$rec = [];
					foreach ($this->columns as $column) {
						if (is_array($column['name']) === true) {
							$rec[implode('-', $column['name'])] = $this->renderField($column, $row);
						} else {
							$rec[$column['name']] = $this->renderField($column, $row);
						}
					}
					$output['data'][] = $rec;
				} else {
					throw new RuntimeException('Data array is not array!');
				}
			}
		}
		try {
			echo json_encode($output, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new RuntimeException($e->getMessage());
		}
	}

	/**
	 * @return array
	 */
	public function getParameters(): array {
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

		$columnCount = count($this->columns);
		for ($i = 0; $i < $columnCount; $i++) {
			$searchable = $parameters['columns'][$i]['searchable'];
			$search = $parameters['columns'][$i]['search']['value'];
			if ($search === null || $search === "") {
				$search = $parameters['search']['value'];
			}
			if ($searchable === "true" && $search !== "" && $search !== null) {
				/** @var Column $column */
				$column = $this->columns[$i];
				if ($column['name'] !== null && $column['name'] !== '' && $column['sort']) {
					$result['condition'][$column['name']] = StringType::fromVar($search, quote: BaseType::remove, separator: BaseType::remove);
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
			$col = $this->columns[$parameters['order'][0]['column']];
			if ($col) {
				$orders[$col['name']] = $dir;
			}
		} else {
			$orders['id'] = "ASC";
		}
		$result['order'] = $orders;

		return $result;
	}

	/**
	 * @param array $column
	 * @param array|IEntity $values
	 * @return string
	 */
	public function renderField(array $column, array|IEntity $values): string {
		$str = '';

		if ($column['link']) {
			if ($column['confirmMessage'] !== null && $column['confirmMessage'] !== '') {
				$confirmMessage = "onclick=\"return confirm('{$column['confirmMessage']}');\"";
			} else {
				$confirmMessage = '';
			}

			foreach ($column['link']->getGetVarsDynamic() as $name => $columnName) {
				$columnDefine = $this->getColumnByName($columnName);
				$value = TableColumn::getValueFromValues($columnDefine, $values);
				if ($value !== null) {
					$column['link']->addGetVar($name, $value);
				}
			}
			foreach ($column['link']->getSeoVarsDynamic() as $index => $columnName) {
				$columnDefine = $this->getColumnByName($columnName);
				$value = TableColumn::getValueFromValues($columnDefine, $values);
				if ($value !== null) {
					$column['link']->setSeoVar($value, $index);
				}
			}

			$str .= "<a class=\"{$column['cssClass']}\" href=\"{$column['link']->toString()}\" ";
			$str .= "title=\"{$column['label']}\" {$confirmMessage} {$column['link']->getTarget()}>";
		}

		$renderFunction = $column['previewRender'];
		if ($renderFunction !== null && is_callable($renderFunction)) {
			$str .= $renderFunction($column, $values);
		}

		if ($column['link']) {
			$str .= "</a>\n";
		}

		return $str;
	}

	/**
	 * @param string|array $columnName
	 * @return array
	 */
	private function getColumnByName(string|array $columnName): array {
		foreach ($this->columns as $column) {
			if (is_array($column['name'])) {
				$column['name'] = $column['name'][0];
			}
			$columnOriginal = $columnName;
			if (is_array($columnName)) {
				$columnName = $columnName[0];
			}

			if ($column['name'] === $columnName) {
				$column['name'] = $columnOriginal;
				return $column;
			}
		}
		throw new RuntimeException('Column ' . $columnName . ' not found!');
	}
}
