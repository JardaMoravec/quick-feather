<?php

namespace QuickFeather\Html\Button;

use Tool\Linker;


class BackButton extends BaseButton {

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
			$title = _('Zpět');
		}
		if ($icon === null || $icon === '') {
			$icon = "fa-long-arrow-left";
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'btn bg-blue text-white';
		}
		parent::__construct($link, $title, $icon, $cssClass, $confirmMessage, $disable);
	}

}
