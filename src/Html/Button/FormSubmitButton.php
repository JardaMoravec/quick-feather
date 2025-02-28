<?php

namespace QuickFeather\Html\Button;


use JetBrains\PhpStorm\Immutable;
use QuickFeather\Routing\Linker;

class FormSubmitButton extends BaseButton {

	#[immutable]
	private string $type = 'submit';
	#[immutable]
	private string $name = 'save';
	#[immutable]
	private ?string $imagePath;

	/**
	 * @param Linker|null $link
	 * @param string|null $title
	 * @param string|null $icon
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $disable
	 * @param string|null $type
	 * @param string|null $name
	 * @param string|null $imagePath
	 */
	public function __construct(?Linker $link = null, ?string $title = null, ?string $icon = null, ?string $cssClass = null, ?string $confirmMessage = null,
								?bool   $disable = false, ?string $type = null, ?string $name = null, ?string $imagePath = null) {
		if ($link === null) {
			$link = self::$context->link;
		}
		if ($title === null || $title === '') {
			$title = _('Uložit');
		}
		if ($icon === null || $icon === '') {
			$icon = "fa-save";
		}
		if ($cssClass === null || $cssClass === '') {
			$cssClass = 'btn btn-outline btn-success';
		}

		parent::__construct($link, $title, $icon, $cssClass, $confirmMessage, $disable);
		if ($name !== null && $name !== '') {
			$this->name = $name;
		}
		if ($type !== null && $type !== '') {
			$this->type = $type;
		}
		if ($imagePath !== null && $imagePath !== '') {
			$this->imagePath = $imagePath;
		}
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getImagePath(): ?string {
		return $this->imagePath;
	}

	/**
	 * @return void
	 */
	public function render(): void {
		$class = ($this->getCssClass() === null || $this->getCssClass() === '') ? "class=\"btn btn-outline btn-primary\" " : "class=\"" . $this->getCssClass() . "\"";

		if ($this->type === "image") {
			echo "<input $class type=\"image\" src=\"" . $this->imagePath . "\" name=\"" . $this->name . "\" title=\"" . $this->title . "\" value=\"" . $this->title . "\" />\n";
		} else if ($this->type === "submit") {
			echo "<button name=\"" . $this->name . "\" type=\"submit\" value=\"" . $this->title . "\" {$class} " . ($this->disable ? ' disabled ' : '') . ">" . "<i class=\"fa " . $this->icon . "\"></i> " . $this->title . "</button>";
		}
	}
}
