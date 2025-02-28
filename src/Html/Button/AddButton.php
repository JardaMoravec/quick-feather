<?php

namespace QuickFeather\Html\Button;

use QuickFeather\Routing\Linker;


class AddButton extends BaseButton {

	/**
	 * @param Linker $link
	 * @param string|null $title
	 * @param string|null $icon
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $disable
	 */
	public function __construct(Linker $link, ?string $title = null, ?string $icon = null, ?string $cssClass = null, ?string $confirmMessage = null, ?bool $disable = false) {
		if ($title === null || $title === '') {
			$title = _('Přidat nový...');
		}
		if ($icon === null || $icon === '') {
			$icon = "fa-plus";
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'btn btn-success';
		}

		parent::__construct($link,
			title: $title,
			icon: $icon,
			cssClass: $cssClass,
			confirmMessage: $confirmMessage,
			disable: $disable
		);
	}
}
