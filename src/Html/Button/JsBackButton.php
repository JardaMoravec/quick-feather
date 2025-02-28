<?php

namespace QuickFeather\Html\Button;


use QuickFeather\Routing\Linker;

class JsBackButton extends BaseButton {

	/**
	 * @param Linker $link
	 * @param string|null $title
	 * @param string|null $icon
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $disable
	 */
	public function __construct(Linker $link, ?string $title = null, ?string $icon = null, ?string $cssClass = null, ?string $confirmMessage = null, ?bool $disable = false) {
		$link->setJsLink('javascript:window.history.back();');

		if ($title === null || $title === '') {
			$title = _('Zpět');
		}
		if ($icon === null || $icon === '') {
			$icon = "fa fa-arrow-left";
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'button back-history';
		}
		parent::__construct($link, $title, $icon, $cssClass, $confirmMessage, $disable);
	}
}
