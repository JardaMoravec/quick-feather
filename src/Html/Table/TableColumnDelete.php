<?php

namespace QuickFeather\Html\Table;

use QuickFeather\Routing\Linker;

class TableColumnDelete extends TableColumnEvent {

	/**
	 * @param Linker $link
	 * @param string|null $name
	 * @param callable|null $previewRender
	 * @param string|null $label
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $sort
	 * @param string|null $suffix
	 */
	public function __construct(Linker        $link,
								string|null   $name = null,
								callable|null $previewRender = null,
								string|null   $label = null,
								string|null   $cssClass = null,
								string|null   $confirmMessage = null,
								bool|null     $sort = null,
								string|null   $suffix = null
	) {
		if ($name === null || $name === '') {
			$name = "delete";
		}
		if ($label === null || $label === '') {
			$label = _('Smazat');
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'btn btn-danger text-white';
		}
		if ($confirmMessage === null || $confirmMessage === '') {
			$confirmMessage = _('Opravdu chcete toto smazat!');
		}

		if ($suffix === null || $suffix === '') {
			$suffix = "<span class=\"fa fa-trash-o\"></span>";
		}

		parent::__construct($name, $previewRender, $link, $label, $cssClass, $confirmMessage, $sort, $suffix);
	}

}
