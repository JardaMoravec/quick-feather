<?php

namespace QuickFeather\Html\Button;

use QuickFeather\Routing\Linker;


class RefreshButton extends BaseButton {

	/**
	 * @param Linker $link
	 * @param string|null $title
	 * @param string|null $icon
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $disable
	 */
	public function __construct(Linker $link, ?string $title = null, ?string $icon = null, ?string $cssClass = null, ?string $confirmMessage = null, ?bool $disable = false) {
		if ($title === null) {
			$title = _('Obnovit');
		}
		if ($icon === null) {
			$icon = "fa-refresh";
		}
		if ($cssClass === null) {
			$cssClass = 'btn bg-gray text-white';
		}
		parent::__construct($link, $title, $icon, $cssClass, $confirmMessage, $disable);
	}
}
