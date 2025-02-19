<?php

namespace QuickFeather\Html\Form;

use DateTime;
use Entity\Base\AdminHelp\AdminHelp;
use Entity\Base\Page\PageId;
use Entity\Cms\BlackWord\BlackWord;
use Exception;
use QuickFeather\Context;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\IdentifierError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\IEntity;
use QuickFeather\EntityManager\ISelectUsable;
use QuickFeather\EntityManager\Repository;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Bound\File;
use QuickFeather\EntityManager\Type\Bound\Image;
use QuickFeather\EntityManager\Type\Complex\Color;
use QuickFeather\EntityManager\Type\Complex\Password;
use QuickFeather\EntityManager\Type\Complex\TimeStamp\Date;
use QuickFeather\EntityManager\Type\Complex\TimeStamp\DateTime as DateTimeType;
use QuickFeather\EntityManager\Type\Complex\TimeStamp\Time;
use QuickFeather\EntityManager\Type\IType;
use QuickFeather\EntityManager\Type\Primitive\BoolType;
use QuickFeather\EntityManager\Type\Primitive\FloatType;
use QuickFeather\EntityManager\Type\Primitive\IntType;
use QuickFeather\EntityManager\Type\Primitive\StringType;
use QuickFeather\Html\Form\Css\BoxCSSClass;
use QuickFeather\Html\Form\Css\FormCSSClass;
use QuickFeather\Html\ToolBar;
use RuntimeException;
use Tool\Linker;
use Tool\Message;


class FormEntity {
	public const MULTIPART = "multipart/form-data";
	private array|IEntity|null $defaultData;
	protected array|null $postedData = [];
	private Repository|null $repository;
	protected ?string $id;
	protected Linker $action;
	protected ?string $enctype;
	protected ?string $cssClass;
	protected bool $insideBox = false;
	private array $fields = [];
	private array $errors = [];
	private ?PageId $pageId = null;
	private ?bool $showEmptyHelpBox;
	private FormCSSClass $defaultFieldsCssClass;

	/**
	 * @param Linker $action
	 * @param array $helpText
	 * @param \QuickFeather\EntityManager\Repository|null $repository
	 * @param string|null $enctype
	 * @param IEntity|array|null $defaultData
	 * @param array $additionalFields
	 * @param array|null $usedFields
	 * @param array|null $unusedFields
	 * @param array|null $errorMessages
	 * @param string|null $id
	 * @param string|null $cssClass
	 * @param array|null $optionList
	 * @param array|null $pathList
	 * @param bool|null $showEmptyHelpBox
	 * @throws Exception
	 */
	public function __construct(Linker  $action, array $helpText, Repository|null $repository = null,
								?string $enctype = null, IEntity|array|null $defaultData = null,
								array   $additionalFields = [], ?array $usedFields = [], ?array $unusedFields = [], ?array $errorMessages = [],
								?string $id = null, ?string $cssClass = 'form', ?array $optionList = [], ?array $pathList = [], ?bool $showEmptyHelpBox = true
	) {
		$this->repository = $repository;
		$this->defaultData = $defaultData;
		$this->action = $action;
		$this->id = $id;
		$this->enctype = $enctype;
		$this->cssClass = $cssClass;
		$this->showEmptyHelpBox = $showEmptyHelpBox;

		if ($this->repository !== null) {
			/** @noinspection PhpUndefinedMethodInspection */
			$fields = $this->repository->getProperties();

			foreach ($fields as $field) {
				if (count($usedFields) > 0) {
					$field['used'] = false;
				} else if (count($unusedFields) > 0) {
					$field['used'] = true;
				}
				$this->fields[constant($this->repository->getEntityClass() . '::' . $field['name'])] = $field;
			}
		}

		foreach ($additionalFields as $identifier => $field) {
			$this->fields[$identifier]['dbname'] = $identifier;
			$this->fields[$identifier]['name'] = $identifier;
			$this->fields[$identifier]['type'] = $field['type'];
			$this->fields[$identifier]['null'] = $field['null'] ?? false;
			$this->fields[$identifier]['used'] = true;
		}
		foreach ($usedFields as $usedField) {
			$this->fields[$usedField]['used'] = true;
		}
		foreach ($unusedFields as $unusedField) {
			$this->fields[$unusedField]['used'] = false;
		}
		foreach ($errorMessages as $errorMessage) {
			$this->fields[$errorMessage]['error_message'] = false;
		}
		foreach ($optionList as $fieldName => $option) {
			$this->fields[$fieldName]['options'] = $option;
		}
		foreach ($pathList as $fieldName => $path) {
			$this->fields[$fieldName]['path'] = $path;
		}
		/** @var AdminHelp $help */
		foreach ($helpText as $help) {
			if ($help->elementRelationship !== null) {
				$fieldName = $help->elementRelationship->getValue();
				$this->fields[$fieldName]['help'] = $help;
			}
		}

		if (count($helpText) > 0 && isset($helpText[0])) {
			$this->pageId = $helpText[0]->pageId;
		}

		$this->defaultFieldsCssClass = new FormCSSClass();
	}

	/**
	 * @param ToolBar|null $toolbar
	 * @return void
	 * @throws Exception
	 */
	public function startForm(ToolBar $toolbar = null): void {
		if ($this->id) {
			$id = "id=\"" . $this->id . "\"";
		} else {
			$id = '';
		}
		echo "<form {$id} method=\"post\" action=\"{$this->action->toString()}\" {$this->getEnctype()} {$this->getCssClass()} >\n";
		$toolbar?->Create();
	}

	/**
	 * @return void
	 */
	public function closeForm(): void {
		if ($this->insideBox) {
			echo "</div></div>\n";
		}
		echo "</form>\n";
	}

	/**
	 * @param string|null $title
	 * @param string|null $id
	 * @param BoxCSSClass|null $cssClass
	 * @return void
	 * @throws NullError
	 */
	public function startBox(?string $title = null, ?string $id = null, ?BoxCSSClass $cssClass = null): void {
		if ($this->insideBox) {
			echo "</div></div>\n";
		}
		$this->insideBox = true;

		if ($cssClass === null) {
			$cssClass = new BoxCSSClass();
		}

		$id = $id ?? StringType::fromVar(strtolower($title), backSlash: BaseType::remove, slash: BaseType::remove, quote: BaseType::remove,
			whiteSpace: BaseType::encode, html: BaseType::remove, diacritic: BaseType::encode,
			separator: BaseType::remove, specialChar: BaseType::remove);

		echo "<div id=\"{$id}\" class=\"" . implode(' ', $cssClass->box) . "\">";
		echo "<div class=\"" . implode(' ', $cssClass->header) . "\">{$title}</div>";
		echo "<div class=\"" . implode(' ', $cssClass->body) . "\" id=\"{$id}_body\">";
	}

	/**
	 * @return void
	 */
	public function closeBox(): void {
		if ($this->insideBox) {
			echo "</div></div>\n";
			$this->insideBox = false;
		}
	}

	/**
	 * @return string
	 */
	public function getFormId(): string {
		return $this->id;
	}

	/**
	 * @param mixed $data
	 * @return mixed|null
	 */
	protected function getValue(IEntity|array|null $data, array $field): mixed {
		if (is_array($data)) {
			$value = ($data[$field['dbname']]) ?? null;
		} else if ($data instanceof IEntity) {
			$namePath = explode(':', $field['name']);
			$value = $data;
			foreach ($namePath as $name) {
				$value = ($value->$name) ?? null;
			}
			$value = $value ?? null;
			if ($value instanceof IEntity) {
				$value = $value->id ?? null;
			} else if ($value instanceof IType && !($value instanceof DateTime)) {
				$value = (string)$value;
			}
		} else {
			$value = null;
		}
		return $value;
	}

	/**
	 * @return string
	 */
	protected function getCssClass(): string {
		if ($this->cssClass) {
			return " class=\"" . $this->cssClass . "\" ";
		}
		return '';
	}

	/**
	 * @return string
	 */
	protected function getEnctype(): string {
		return $this->enctype !== '' ? " enctype=\"" . $this->enctype . "\" " : '';
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param string|null $type
	 * @param bool|null $renderOnlyField
	 * @param array|null $attributes
	 * @param string|null $placeholder
	 * @return void
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws Exception
	 */
	public function renderInput(string $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
								?bool  $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null,
								?array $events = [], ?bool $labelBefore = true, ?string $type = null, ?bool $renderOnlyField = false,
								?array $attributes = [], string|null $placeholder = null
	): void {
		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($id === null) {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($type === null) {
				$type = match ($this->fields[$identifier]['type']) {
					'int', 'float' => 'number',
					Color::class => 'color',
					Password::class => 'password',
					default => 'text',
					DateTimeType::class, DateTime::class => 'datetime-local',
					Date::class => 'date',
					Time::class => 'time',
				};
			}

			if ($cssClass === null) {
				$cssClass = $this->defaultFieldsCssClass;
			}

			// values
			$value = $this->getValue($this->defaultData, $this->fields[$identifier]);
			if ($value !== null && ($this->fields[$identifier]['type'] === DateTimeType::class || $this->fields[$identifier]['type'] === DateTime::class)) {
				$value = $value->format('Y-m-d H:i');
			} else if ($value !== null && $this->fields[$identifier]['type'] === Date::class) {
				$value = $value->format('Y-m-d');
			} else if ($value !== null && $this->fields[$identifier]['type'] === Time::class) {
				$value = $value->format('H:i');
			}
			if ($type === 'password') {
				$value = '';
			}

			$field = RenderField::renderInput(
				name: $this->fields[$identifier]['name'],
				elementId: $id,
				require: !$this->fields[$identifier]['null'],
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				type: $type,
				length: $this->fields[$identifier]['length'] ?? null,
				placeholder: $this->fields[$identifier]['placeholder'] ?? $placeholder ?? null,
				value: $value,
			);

			if (!$renderOnlyField) {
				echo RenderBox::renderBox(
					field: $field,
					identifier: $identifier,
					label: $label,
					elementId: $id,
					description: $this->fields[$identifier]['help'] ?? $description,
					require: !$this->fields[$identifier]['null'],
					cssClass: $cssClass,
					dataAttributes: $dataAttributes,
					events: $events,
					labelBefore: $labelBefore,
					error: $this->errors[$identifier] ?? null,
					pageId: $this->pageId,
					showEmptyHelpBox: $this->showEmptyHelpBox
				);
			} else {
				echo $field;
			}
		} else {
			throw new RuntimeException("This identifier  {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param string|null $type
	 * @param array|null $attributes
	 * @param bool|null $labelBefore
	 * @param bool|null $showEye
	 * @param bool|null $withConfirmField
	 * @return void
	 * @throws NullError
	 * @throws Exception
	 */
	public function renderPassword(string  $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
								   ?bool   $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null, ?array $events = [],
								   ?string $type = null, ?array $attributes = [], ?bool $labelBefore = true, ?bool $showEye = true, ?bool $withConfirmField = false
	): void {
		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($id === null) {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($cssClass === null) {
				$cssClass = $this->defaultFieldsCssClass;
			}

			$field = RenderField::renderInput(
				name: $withConfirmField ? $this->fields[$identifier]['name'] . "[0]" : $this->fields[$identifier]['name'],
				elementId: $withConfirmField ? $id . '-1' : $id,
				require: !$this->fields[$identifier]['null'],
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				type: $type,
				length: $this->fields[$identifier]['length'] ?? null,
				placeholder: $this->fields[$identifier]['placeholder'] ?? null,
			);
			$field = RenderBox::renderPasswordBox(
				field: $field,
				elementId: $withConfirmField ? $id . '-1' : $id,
				showEye: $showEye,
			);
			echo RenderBox::renderBox(
				field: $field,
				identifier: $identifier,
				label: $label,
				elementId: $withConfirmField ? $id . '-1' : $id,
				description: $this->fields[$identifier]['help'] ?? $description,
				require: !$this->fields[$identifier]['null'],
				cssClass: $cssClass,
				dataAttributes: $dataAttributes,
				events: $events,
				labelBefore: $labelBefore,
				error: $this->errors[$identifier] ?? null,
				pageId: $this->pageId,
				showEmptyHelpBox: $this->showEmptyHelpBox
			);

			if ($withConfirmField) {
				if ($id === null) {
					$id = RenderCommon::createUniqueId($this->fields[$identifier]['name'], '_confirm');
				}

				$field = RenderField::renderInput(
					name: $this->fields[$identifier]['name'] . "[1]",
					elementId: $id,
					require: !$this->fields[$identifier]['null'],
					readOnly: $readOnly,
					cssClass: $cssClass,
					attributes: $attributes,
					dataAttributes: $dataAttributes,
					events: $events,
					type: $type,
					length: $this->fields[$identifier]['length'] ?? null,
					placeholder: $this->fields[$identifier]['placeholder'] ?? null,
				);
				$field = RenderBox::renderPasswordBox(
					field: $field,
					elementId: $id,
					showEye: $showEye,
				);
				echo RenderBox::renderBox(
					field: $field,
					identifier: $identifier,
					label: $label . _(' (potvrzení)'),
					elementId: $id,
					description: $this->fields[$identifier]['help'] ?? $description,
					require: !$this->fields[$identifier]['null'],
					cssClass: $cssClass,
					dataAttributes: $dataAttributes,
					events: $events,
					labelBefore: $labelBefore,
					error: $this->errors[$identifier] ?? null,
					pageId: $this->pageId,
					showEmptyHelpBox: $this->showEmptyHelpBox
				);
			}
		} else {
			throw new RuntimeException("This identifier  {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param bool|null $activeDelete
	 * @param bool|null $preview
	 * @param \QuickFeather\Context|null $context
	 * @param bool $ignoreType
	 * @return void
	 * @throws NullError
	 * @throws Exception
	 * @todo improvizovaně přidán $context
	 */
	public function renderUpload(string $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
								 ?bool  $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null,
								 ?array $events = [], ?bool $labelBefore = true, ?bool $activeDelete = false,
								 ?bool  $preview = true, ?Context $context = null, bool $ignoreType = false): void {
		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($id === null) {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($cssClass === null) {
				$cssClass = $this->defaultFieldsCssClass;
			}

			if ($this->fields[$identifier]['type'] === Image::class || $this->fields[$identifier]['type'] === File::class || $ignoreType) {
				$field = RenderField::renderImageUpload(
					name: $this->fields[$identifier]['name'],
					elementId: $id,
					readOnly: $readOnly,
					cssClass: $cssClass,
					dataAttributes: $dataAttributes,
					events: $events,
					activeDelete: $activeDelete,
					preview: $preview,
					folder: $this->fields[$identifier]['path'],
					context: $context,
					value: $this->getValue($this->defaultData, $this->fields[$identifier]),
				);

				echo RenderBox::renderBox(
					field: $field,
					identifier: $identifier,
					label: $label,
					elementId: $id,
					description: $this->fields[$identifier]['help'] ?? $description,
					require: !$this->fields[$identifier]['null'],
					cssClass: $cssClass,
					dataAttributes: $dataAttributes,
					events: $events,
					labelBefore: $labelBefore,
					error: $this->errors[$identifier] ?? null,
					pageId: $this->pageId,
					showEmptyHelpBox: $this->showEmptyHelpBox
				);
			} else {
				throw new RuntimeException("Unsupported field type!");
			}
		} else {
			throw new RuntimeException("This identifier  {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param int|null $rowCount
	 * @param int|null $columnCount
	 * @param int|null $extension
	 * @param array|null $attributes
	 * @return void
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws Exception
	 */
	public function renderTextarea(string $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
								   ?bool  $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null,
								   ?array $events = [], ?bool $labelBefore = true, int $rowCount = null, int $columnCount = null,
								   ?int   $extension = null, ?array $attributes = []): void {
		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($id === null) {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($cssClass === null) {
				$cssClass = $this->defaultFieldsCssClass;
			}

			$field = RenderField::renderTextarea(
				$this->fields[$identifier]['name'],
				elementId: $id,
				require: !$this->fields[$identifier]['null'],
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				length: $this->fields[$identifier]['length'] ?? null,
				rowCount: $rowCount,
				columnCount: $columnCount,
				extension: $extension,
				value: $this->getValue($this->defaultData, $this->fields[$identifier]),
			);

			echo RenderBox::renderBox(
				field: $field,
				identifier: $identifier,
				label: $label,
				elementId: $id,
				description: $this->fields[$identifier]['help'] ?? $description,
				require: !$this->fields[$identifier]['null'],
				cssClass: $cssClass,
				dataAttributes: $dataAttributes,
				events: $events,
				labelBefore: $labelBefore,
				error: $this->errors[$identifier] ?? null,
				pageId: $this->pageId,
				showEmptyHelpBox: $this->showEmptyHelpBox
			);
		} else {
			throw new RuntimeException("This identifier  {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param array|null $attributes
	 * @return void
	 * @throws NullError
	 * @throws Exception
	 */
	public function renderCheckbox(string $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
								   ?bool  $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null,
								   ?array $events = [], ?bool $labelBefore = true, ?array $attributes = []): void {

		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($id === null) {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($cssClass === null) {
				$cssClass = new FormCSSClass(
					box: ['form-check', 'form-switch', 'mt-1'],
					label: ['form-check-label ps-2'],
					input: ['form-check-input'],
				);
			}

			$field = RenderField::renderCheckbox(
				$this->fields[$identifier]['name'],
				elementId: $id,
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				value: $this->getValue($this->defaultData, $this->fields[$identifier]),
			);
			echo RenderBox::renderBox(
				field: $field,
				identifier: $identifier,
				label: $label,
				elementId: $id,
				description: $this->fields[$identifier]['help'] ?? $description,
				require: !$this->fields[$identifier]['null'],
				cssClass: $cssClass,
				dataAttributes: $dataAttributes,
				events: $events,
				labelBefore: $labelBefore,
				error: $this->errors[$identifier] ?? null,
				pageId: $this->pageId,
				showEmptyHelpBox: $this->showEmptyHelpBox
			);
		} else {
			throw new RuntimeException("This identifier {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param array|null $attributes
	 * @param bool|null $showAllOption
	 * @return void
	 * @throws NullError
	 * @throws Exception
	 */
	public function renderCheckboxList(string $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
									   ?bool  $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null,
									   ?array $events = [], ?bool $labelBefore = true, ?array $attributes = [], bool|null $showAllOption = true): void {

		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($this->fields[$identifier]['options'] === null || count($this->fields[$identifier]['options']) === 0) {
				throw new RuntimeException("This element {$identifier} not contains option list!");
			}

			if ($id === null || $id === '') {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($cssClass === null) {
				$cssClass = new FormCSSClass(
					box: ['form-check', 'mt-1'],
					label: ['form-check-label ps-2'],
					input: ['form-check-input'],
				);
			}

			$field = RenderField::renderCheckBoxList(
				$this->fields[$identifier]['name'],
				elementId: $id,
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				options: $this->fields[$identifier]['options'],
				showAllOption: $showAllOption,
				value: $this->getValue($this->defaultData, $this->fields[$identifier]),
			);

			echo RenderBox::renderBox(
				field: $field,
				identifier: $identifier,
				label: $label,
				elementId: $id,
				description: $this->fields[$identifier]['help'] ?? $description,
				require: !$this->fields[$identifier]['null'],
				cssClass: $this->defaultFieldsCssClass,
				dataAttributes: $dataAttributes,
				events: $events,
				labelBefore: $labelBefore,
				error: $this->errors[$identifier] ?? null,
				pageId: $this->pageId,
				showEmptyHelpBox: $this->showEmptyHelpBox
			);

		} else {
			throw new RuntimeException("This identifier {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param int|null $size
	 * @param array|null $attributes
	 * @param bool|null $multiple
	 * @param bool|null $renderOnlyField
	 * @return void
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws Exception
	 */
	public function renderSelect(string $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
								 ?bool  $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null,
								 ?array $events = [], ?bool $labelBefore = true, ?int $size = 0, ?array $attributes = [],
								 ?bool  $multiple = null, ?bool $renderOnlyField = false): void {
		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($this->fields[$identifier]['options'] === null || count($this->fields[$identifier]['options']) === 0) {
				throw new RuntimeException("This element {$identifier} not contains option list!");
			}

			if ($this->fields[$identifier]['null'] && !$multiple) {
				array_unshift($this->fields[$identifier]['options'], ["id" => null, "name" => ""]);
			}

			if ($id === null || $id === '') {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($cssClass === null) {
				$cssClass = $this->defaultFieldsCssClass;
			}

			$field = RenderField::renderSelect(
				$this->fields[$identifier]['name'],
				elementId: $id,
				require: !$this->fields[$identifier]['null'],
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				options: $this->fields[$identifier]['options'],
				size: $size,
				multiple: $multiple,
				value: $this->getValue($this->defaultData, $this->fields[$identifier]),
			);
			if (!$renderOnlyField) {
				echo RenderBox::renderBox(
					field: $field,
					identifier: $identifier,
					label: $label,
					elementId: $id,
					description: $this->fields[$identifier]['help'] ?? $description,
					require: !$this->fields[$identifier]['null'],
					cssClass: $cssClass,
					dataAttributes: $dataAttributes,
					events: $events,
					labelBefore: $labelBefore,
					error: $this->errors[$identifier] ?? null,
					pageId: $this->pageId,
					showEmptyHelpBox: $this->showEmptyHelpBox
				);
			} else {
				echo $field;
			}
		} else {
			throw new RuntimeException("This identifier {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $id
	 * @param string|null $description
	 * @param bool|null $readOnly
	 * @param array|null $dataAttributes
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param bool|null $renderOnlyField
	 * @param array|null $attributes
	 * @return void
	 * @throws NullError
	 * @throws Exception
	 */
	public function renderRangeInput(string $identifier, ?string $label = null, ?string $id = null, ?string $description = null,
									 ?bool  $readOnly = false, ?array $dataAttributes = [], ?FormCSSClass $cssClass = null,
									 ?array $events = [], ?bool $labelBefore = true, ?bool $renderOnlyField = false,
									 ?array $attributes = []
	): void {
		if (array_key_exists($identifier, $this->fields)) {
			$this->fields[$identifier]['used'] = true;

			if ($id === null) {
				$id = RenderCommon::createUniqueId($this->fields[$identifier]['name']);
			}

			if ($cssClass === null) {
				$cssClass = $this->defaultFieldsCssClass;
			}

			$type = 'number';

			// values
			$value1 = $this->defaultData[$identifier . '[from]'];
			$value2 = $this->defaultData[$identifier . '[to]'];

			$field1 = RenderField::renderInput(
				name: $this->fields[$identifier]['name'] . '[from]',
				elementId: $id,
				require: !$this->fields[$identifier]['null'],
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				type: $type,
				length: $this->fields[$identifier]['length'] ?? null,
				placeholder: $this->fields[$identifier]['placeholder'] ?? '',
				value: $value1,
			);

			$field2 = RenderField::renderInput(
				name: $this->fields[$identifier]['name'] . '[to]',
				elementId: $id,
				require: !$this->fields[$identifier]['null'],
				readOnly: $readOnly,
				cssClass: $cssClass,
				attributes: $attributes,
				dataAttributes: $dataAttributes,
				events: $events,
				type: $type,
				length: $this->fields[$identifier]['length'] ?? null,
				placeholder: $this->fields[$identifier]['placeholder'] ?? '',
				value: $value2,
			);

			$html = "<div class=\"input-group mb-3\"><span class=\"input-group-text\">Od</span>";
			$html .= $field1;
			$html .= "<span class=\"input-group-text\">Do</span>";
			$html .= $field2;
			$html .= "</div>";

			if (!$renderOnlyField) {
				echo RenderBox::renderBox(
					field: $html,
					identifier: $identifier,
					label: $label,
					elementId: $id,
					description: $this->fields[$identifier]['help'] ?? $description,
					require: !$this->fields[$identifier]['null'],
					cssClass: $cssClass,
					dataAttributes: $dataAttributes,
					events: $events,
					labelBefore: $labelBefore,
					error: $this->errors[$identifier] ?? null,
					pageId: $this->pageId,
					showEmptyHelpBox: $this->showEmptyHelpBox
				);
			} else {
				echo $field1 . ' ' . $field2;
			}
		} else {
			throw new RuntimeException("This identifier  {$identifier} not exists in the form!");
		}
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @param string|null $icon
	 * @param array|null $cssClass
	 * @param string|null $type
	 * @param bool|null $disable
	 * @param string|null $imagePath
	 * @return void
	 */
	public function renderSubmitButton(string    $name, string $label, string|null $icon = null, array|null $cssClass = [], string|null $type = "submit",
									   bool|null $disable = false, string|null $imagePath = null
	): void {
		if ($cssClass === null || count($cssClass) === 0) {
			$cssClassString = "class=\"btn btn-outline btn-primary\" ";
		} else {
			$cssClassString = "class=\"" . implode(' ', $cssClass) . "\" ";
		}
		if ($icon !== null) {
			$icon = "<i class=\"{$icon}\"></i> ";
		}
		if ($type === "image") {
			echo "<input {$cssClassString} type=\"image\" src=\"{$imagePath}\" name=\"{$name}\" title=\"{$label}\" value=\"{$label}\"  alt=\"{$label}\"/>\n";
		} else if ($type === "submit") {
			echo "<button name=\"{$name}\" type=\"submit\" value=\"{$label}\" {$cssClassString} " . ($disable ? ' disabled ' : '') . ">" . "{$icon}{$label}</button>";
		}
	}

	/**
	 * @param string $template
	 * @param ToolBar|null $toolBar
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function render(string $template, ?ToolBar $toolBar = null): void {
		$form = $this;
		include "{$template}.tpl.php";
	}

	/**
	 * @param bool|null $setPostedDataAsDefault
	 * @return array|null
	 * @throws IdentifierError
	 * @throws EntityError
	 */
	public function getPostedData(?bool $setPostedDataAsDefault = true): array|null {

		foreach ($this->fields as $field) {
			if (!$field['used']) {
				continue;
			}

			try {
				$this->postedData[$field['dbName']] = $this->getPostedValue($field);
			} catch (NullError|TypeError $error) {
				$this->errors[$field['dbName']] = $error->getMessage();
			}
		}

		if ($setPostedDataAsDefault) {
			$this->defaultData = $this->postedData;
		}
		return $this->postedData;
	}

	/**
	 * @param bool|null $setPostedDataAsDefault
	 * @param callable|null $beforeEvents
	 * @return \QuickFeather\EntityManager\IEntity
	 * @throws EntityError
	 */
	public function getPostedEntity(?bool $setPostedDataAsDefault = true, ?callable $beforeEvents = null): IEntity {
		$postedValues = $this->getPostedData(false);

		if ($beforeEvents !== null) {
			try {
				$beforeEvents($this, $postedValues);
			} catch (EntityError $error) {
				Message::add($error->getMessage(), Message::ERROR);
				$this->errors[$error->getEntity()] = $error->getMessage();
			}
		}

		if (Message::exist() || count($this->errors) > 0) {
			if ($setPostedDataAsDefault) {
				$this->defaultData = $this->postedData;
			}
			throw new EntityError(_('Data obsahují chyby!'), BlackWord::class);
		}

		if ($this->defaultData instanceof IEntity) {
			/** @noinspection PhpUndefinedMethodInspection */
			$entity = $this->repository->fillFromArray($this->defaultData, $postedValues);
		} else if ($this->defaultData !== null) {
			/** @noinspection PhpUndefinedMethodInspection */
			$entity = $this->repository->array2entity(array_merge($this->defaultData, $postedValues));
		} else {
			/** @noinspection PhpUndefinedMethodInspection */
			$entity = $this->repository->array2entity($postedValues);
		}

		if ($setPostedDataAsDefault) {
			$this->defaultData = $this->postedData;
		}
		return $entity;
	}

	/**
	 * @param $field
	 * @return bool|float|int|mixed|null
	 * @throws IdentifierError
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 * @throws EntityError
	 * @throws Exception
	 */
	private function getPostedValue($field): mixed {
		if (class_exists($field['type'])) {
			$interfaces = class_implements($field['type']);
		} else {
			$interfaces = [];
		}

		if (str_contains($field['type'], 'MultiString')) {
			return $field['type']::fromPost($field['name'], !$field['null']);

		}

		if (str_contains($field['type'], 'String')) {
			return $field['type']::fromPost($field['name'], !$field['null']);

		}

		if ($field['type'] === 'string') {
			return StringType::fromPost($field['name'], !$field['null']);

		}

		if ($field['type'] === 'bool') {
			return BoolType::fromPost($field['name'], !$field['null']);

		}

		if ($field['type'] === 'int') {
			return IntType::fromPost($field['name'], !$field['null']);

		}

		if ($field['type'] === 'float') {
			return FloatType::fromPost($field['name'], !$field['null']);

		}

		if ($field['type'] === DateTime::class) {
			return DateTimeType::fromPost($field['name'], !$field['null']);

		}

		if (in_array(IType::class, $interfaces, true)) {
			return ($field['type'])::fromPost($field['name'], !$field['null']);

		}

		if (in_array(IEntity::class, $interfaces, true)) {
			$value = StringType::fromPost($field['name'], !$field['null']);
			if ($value === null) {
				return null;
			}
			if (isset($field['options']) && is_array($field['options']) && count($field['options']) > 0) {
				foreach ($field['options'] as $option) {
					if ($option instanceof ISelectUsable) {
						if (($option->id ?? null) === (int)$value && $value > 0) {
							return new $field['type']((int)$value);
						}
					} else if (is_array($option)) {
						if ($option['id'] == $value && $value > 0) {
							if (is_int($option['id'])) {
								return new $field['type']((int)$value);
							}
							return $value;
						}
					} else {
						throw new RuntimeException("Not valid option input!");
					}
				}
				throw new TypeError(_('Tato možnost není v seznamu!'), $field['type']);
			}

			if ($value > 0) {
				return new $field['type']((int)$value);
			}

			return $value;
		}
		return null;
	}

	/**
	 * @param string $field
	 * @param string $errorDescription
	 * @return void
	 */
	public function addError(string $field, string $errorDescription): void {
		$this->errors[$field] = $errorDescription;
	}
}
