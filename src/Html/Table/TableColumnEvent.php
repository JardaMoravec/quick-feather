<?php

namespace QuickFeather\Html\Table;

use QuickFeather\Routing\Linker;


class TableColumnEvent extends TableColumn {

	/**
	 * @param array|string $name
	 * @param callable|array|null $previewRender
	 * @param Linker|null $link
	 * @param string|null $label
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $sort
	 * @param string|null $suffix
	 */
	public function __construct(array|string        $name,
								callable|array|null $previewRender = null,
								Linker|null         $link = null,
								string|null         $label = null,
								string|null         $cssClass = null,
								string|null         $confirmMessage = null,
								bool|null           $sort = true,
								string|null         $suffix = null
	) {
		if ($previewRender === null) {
			$previewRender = TableColumn::$renderButton;
		}
		if ($sort === null) {
			$sort = false;
		}

		parent::__construct($name, $previewRender, $link, $label, $cssClass, $confirmMessage, $sort, $suffix);
	}
}
