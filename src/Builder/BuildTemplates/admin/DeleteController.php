<?php

namespace RmamespaceR;


use Entity\Base\Context;
use Exception;
use PDO;
use PDOException;
use phpmailerException;
use Service\Base\PageService;
use Service\System\LogEventService;
use Tool\dbEntity\Error\SQLError;
use Tool\Message;

class DeleteController extends BaseController {

	/**
	 * @param PDO $pdo
	 * @param Context $context
	 * @throws SQLError
	 * @throws phpmailerException
	 * @throws Exception
	 */
	public function __construct(PDO $pdo, Context $context) {
		parent::__construct($pdo, $context);
		try {
			\Rdao_classR::deleteById($this->pdo, $this->key);
			Message::add(_('R_module_name_R byl úspěšně smazán.'), Message::INFO);
			LogEventService::logEvent($this->pdo, $this->context->getCurrentUser(), "R_module_name_R byl úspěšně smazán: " . $this->key, LogEventService::INFO);
		} catch (PDOException $e) {
			LogEventService::logFatalException($this->pdo, $this->context->getCurrentUser(), $e);
			Message::add(_('R_module_name_R se nepodařilo smazát!'), Message::ERROR);
		}
		PageService::redirectTo($this->listLink);
	}

}
