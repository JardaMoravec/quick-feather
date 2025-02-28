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


class EditController extends BaseController {

	protected string $templatePath = 'eshop/R_module_name_R/edit';
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

		$savedValues = \Rdao_classR::getOneById($this->pdo, $this->key);

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

				\Rdao_classR::updateById($this->pdo, $entity, $savedValues->id);

				Message::add(_('R_label_R byl úspěšně editován.'), Message::INFO);
				LogEventService::logEvent($this->pdo, $this->context->getCurrentUser(), "Editován R_label_R s id:" . $this->key, LogEventService::EDIT);
				$this->pdo->commit();

				PageService::redirectTo($this->context->getLink());
			} catch (dbEntityError) {
				$this->pdo->rollBack();
				Message::add(_('R_label_R se nepodařilo editovat!'), Message::ERROR);
				LogEventService::logEvent($this->pdo, $this->context->getCurrentUser(), "Nepodařilo se editovat R_label_R se id:" . $this->key, LogEventService::ERROR);
			} catch (PDOException|SQLError $e) {
				$this->pdo->rollBack();
				LogEventService::logFatalException($this->pdo, $this->context->getCurrentUser(), $e);
				Message::add(_('R_label_R se nepodařilo editovat!'), Message::ERROR);
			}
		}

		// toolbar
		$back = new BackButton($this->listLink);
		$this->toolbar->addButton($back);
		$save = new FormSubmitButton();
		$this->toolbar->addButton($save);
		$this->toolbar->addTitle($savedValues->title->getValue($this->context->getLanguageId()));
		$this->toolbar->addTitle(_('upravit R_label_R'));
	}

	/**
	 * @return FormEntity
	 */
	public function getForm(): FormEntity {
		return $this->form;
	}

}
