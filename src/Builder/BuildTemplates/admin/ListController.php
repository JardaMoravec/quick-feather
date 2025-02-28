<?php

namespace RmamespaceR;

use Exception;
use ReflectionException;
use Tool\Button\AddButton;
use Tool\dbEntity\Error\IdentifierError;
use Tool\dbEntity\Error\NullError;
use Tool\dbEntity\Error\SQLError;
use Tool\Linker;
use Tool\Table\Column;
use Tool\Table\Table;


class ListController extends BaseController {

	/**
	 * @return void
	 * @throws NullError
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws IdentifierError
	 * @throws Exception
	 */
	public function renderTemplate(): void {
		$columns = [
			Table::createColumn('id', label: _('Id')),
//R_list_R
		];

		if ($this->editLink) {
			$columns[] = Table::createEditColumn($this->editLink);
		}
		if ($this->deleteLink) {
			$columns[] = Table::createDeleteColumn($this->deleteLink);
		}

		if (!Linker::isAjaxRequest()) {
			// toolbar
			$this->toolbar->addTitle(_('seznam'));

			if ($this->addLink) {
				$add = new AddButton($this->addLink);
				$this->toolbar->addButton($add);
			}

			$this->toolbar->Create();

			Table::createHeader('R_module_name_R', $columns);
		} else {
			$totalCount = \Rdao_classR::getCount($this->pdo);

			$parameters = Table::getParameters($columns);
			$values = \Rdao_classR::getListByParameters($this->pdo, $parameters, null, $this->context->getLanguageId());

			Table::createAjax($columns, $values[0], $totalCount, $values[1]);
		}
	}
}
