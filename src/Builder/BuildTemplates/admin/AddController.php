<?php

namespace RmamespaceR;

use Entity\Base\Context;
use Exception;
use Exception\DataError;
use PDO;
use PDOException;
use phpmailerException;
use ReflectionException;
use Service\Base\PageService;
use Service\System\LogEventService;
use Tool\Button\BackButton;
use Tool\Button\FormSubmitButton;
use Tool\dbEntity\Error\dbEntityError;
use Tool\dbEntity\Error\NullError;
use Tool\dbEntity\Error\SQLError;
use Tool\dbEntity\Error\TypeError;
use Tool\Form\FormEntity;
use Tool\Linker;
use Tool\Message;

class AddController extends BaseController {

	protected string $templatePath = 'shop/R_module_name_R/add';
	private FormEntity $form;

	/**
	 * @throws NullError
	 * @throws phpmailerException
	 * @throws TypeError
	 * @throws SQLError
	 * @throws dbEntityError
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function __construct(PDO $pdo, Context $context) {
		parent::__construct($pdo, $context);

		$savedValues = [];

		$this->form = new FormEntity(
			action: $this->context->getLink(),
			daoClass: \Rdao_classR::class,
			languageId: $this->context->getLanguageId(),
			defaultData: $savedValues,
			unusedFields: [EntityClass::id]
		);


		if (Linker::isPost()) {
			try {
				$this->pdo->beginTransaction();

				$entity = $this->form->getPostedEntity();

				$id = \Rdao_classR::insert($this->pdo, $entity);

				Message::add(_('R_label_R byla úspěšně uložena.'), Message::INFO);
				LogEventService::logEvent($this->pdo, $this->context->getCurrentUser(), "Úspěšně vložena R_label_R s id " . $id, LogEventService::ADD);
				$this->pdo->commit();

				PageService::redirectTo($this->listLink);
			} catch (dbEntityError $e) {
				$this->pdo->rollBack();
				Message::add(_('R_label_R se nepodařilo uložit!'), Message::ERROR);
				LogEventService::logException($this->pdo, $this->context->getCurrentUser(), $e);
			} catch (PDOException|SQLError $e) {
				$this->pdo->rollBack();
				LogEventService::logFatalException($this->pdo, $this->context->getCurrentUser(), $e);
				Message::add(_('R_label_R se nepodařilo uložit!'), Message::ERROR);
			}
		}

		// toolbar
		$back = new BackButton($this->listLink);
		$this->toolbar->addButton($back);
		$save = new FormSubmitButton();
		$this->toolbar->addButton($save);
		$this->toolbar->addTitle(_('nový R_label_R'));
	}

	/**
	 * @return FormEntity
	 */
	public function getForm(): FormEntity {
		return $this->form;
	}
}
