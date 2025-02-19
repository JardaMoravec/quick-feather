<?php

namespace QuickFeather\Html\Button;


use JetBrains\PhpStorm\Immutable;
use QuickFeather\Html\Form\FormEntity;

class JsFormSubmitButton extends BaseButton {

	#[immutable]
	private string $name = 'save';
	#[immutable]
	private FormEntity $form;

	/**
	 * @param FormEntity $form
	 * @param string|null $title
	 * @param string|null $icon
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $disable
	 * @param string|null $name
	 */
	public function __construct(FormEntity $form, ?string $title = null, ?string $icon = null, ?string $cssClass = null, ?string $confirmMessage = null,
								?bool      $disable = false, ?string $name = null) {

		$this->form = $form;

		if ($title === null || $title === '') {
			$title = _('Uložit');
		}
		if ($icon === null || $icon === '') {
			$icon = "fa fa-save";
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'btn btn-outline btn-success';
		}

		parent::__construct(null, $title, $icon, $cssClass, $confirmMessage, $disable);

		if ($name !== null && $name !== '') {
			$this->name = $name;
		}
	}

	/**
	 * @return void
	 */
	public function render(): void {
		$cssClass = ($this->getCssClass() === null || $this->getCssClass() === '') ? "btn btn-outline btn-primary" : $this->getCssClass();

		$cssClass = "remote-form-submit " . $cssClass;

		echo "<button name=\"{$this->name}\" type=\"submit\" value=\"{$this->title}\" class=\"{$cssClass}\" data-form-id='{$this->form->getFormId()}' " . ($this->disable ? ' disabled ' : '') . ">";
		echo "<i class=\"{$this->icon}\"></i> {$this->title}";
		echo "</button>";
	}
}
