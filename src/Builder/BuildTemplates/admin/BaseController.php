<?php

namespace RmamespaceR;

use Entity\Base\Context;
use Exception;
use Tool\dbEntity\Error\SQLError;
use Tool\dbEntity\Type\Primitive\IntType;
use Tool\Form\Form;
use Tool\Form\GroupBox;
use PDO;
use PDOException;
use Service\Base\PageService;
use Tool\Button\AddButton;
use Tool\Button\BackButton;
use Tool\Button\FormSubmitButton;
use Tool\Button\RefreshButton;
use Tool\Form\CheckboxField;
use Tool\Form\DateTimeField;
use Tool\Form\InputField;
use Tool\Form\NumberField;
use Tool\Form\SelectField;
use Tool\Form\TextAreaField;
use Tool\Linker;
use Tool\ToolBar;


abstract class BaseController extends \Controller\BaseController {

	protected ToolBar $toolbar;
	protected ?int $key;
	protected ?Linker $listLink;
	protected ?Linker $addLink;
	protected ?Linker $editLink;
	protected ?Linker $deleteLink;

	/**
	 * @param PDO $pdo
	 * @param Context $context
	 * @throws SQLError
	 * @throws Exception
	 */
	function __construct(PDO $pdo, Context $context) {
		parent::__construct($pdo, $context);

		// property from get
		$this->key = IntType::fromGet('rowkey');

		// links
		$this->listLink = PageService::getPageLink($this->pdo, Rlist_pageR, $this->context);
		$this->addLink = PageService::getPageLink($this->pdo, Radd_pageR, $this->context);
		$this->editLink = PageService::getPageLink($this->pdo, Redit_pageR, $this->context);
		$this->editLink->addGetVarDynamically('rowkey', 'id');
		$this->deleteLink = PageService::getPageLink($this->pdo, Rdelete_pageR, $this->context);
		$this->deleteLink->addGetVarDynamically('rowkey', 'id');

		// toolbar
		$this->toolbar = new ToolBar($context->getPage()->icon);
		$this->toolbar->addTitle(_('R_label_R'));

		$refresh = new RefreshButton($this->context->getLink());
		$this->toolbar->addButton($refresh);
	}

	/**
	 * @param string $type
	 * @return Form
	 * @throws Exception
	 */
	protected function createForm(string $type): Form {

		$form = new Form(action: $this->context->getLink(), id: 'R_module_name_RForm', cssClass: "form");

		$basicBox = new GroupBox("basic", _('Základní nastavení'), GroupBox::COLS3);

		if ($type == self::FORM_EDIT) {
			$id = new NumberField('id', _('Id'), readOnly: true);
			$basicBox->addElement($id);
		}

//R_form_fields_R

		$form->addElement($basicBox);

		return $form;
	}

	/**
	 * @return ToolBar
	 */
	public function getToolbar(): ToolBar {
		return $this->toolbar;
	}

	/**
	 * @return int|null
	 */
	public function getKey(): ?int {
		return $this->key;
	}

	/**
	 * @return array
	 */
	public static function help(): array {
		return [
			_("Položky označená symbolem * je nutné vyplnit."),
			_("Položky označená symbolem # je možné vyplnit ve více jazycích."),
			_("Po vložení se zobrazí v horní části obrazovky informační hláška."),
		];
	}
}
