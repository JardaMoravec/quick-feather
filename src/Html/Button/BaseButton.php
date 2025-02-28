<?php

namespace QuickFeather\Html\Button;

use QuickFeather\Context;
use QuickFeather\Routing\Linker;

class BaseButton {

	public static Context $context;

	protected ?Linker $link;
	protected ?string $title;
	protected ?string $confirmMessage;
	protected ?string $icon;
	protected ?string $cssClass;
	protected bool $disable = false;
	protected ?string $id;
	protected ?array $dataAttr = [];

	/**
	 * @param Linker|null $link
	 * @param string|null $title
	 * @param string|null $icon
	 * @param string|null $cssClass
	 * @param string|null $confirmMessage
	 * @param bool|null $disable
	 * @param string|null $id
	 * @param array|null $dataAttr
	 */
	public function __construct(?Linker $link = null, ?string $title = null, ?string $icon = null, ?string $cssClass = null, ?string $confirmMessage = null,
								?bool   $disable = false, ?string $id = null, ?array $dataAttr = []) {
		$this->link = $link;
		$this->title = $title;
		$this->icon = $icon;
		$this->cssClass = $cssClass;
		$this->confirmMessage = $confirmMessage;
		$this->disable = $disable;
		$this->id = $id;
		$this->dataAttr = $dataAttr;
	}

	/**
	 * @return ?Linker
	 */
	public function getLink(): ?Linker {
		return $this->link;
	}

	/**
	 * @return string|null
	 */
	public function getTitle(): ?string {
		return $this->title;
	}

	/**
	 * @return string|null
	 */
	public function getConfirmMessage(): ?string {
		return $this->confirmMessage;
	}

	/**
	 * @return string|null
	 */
	public function getIcon(): ?string {
		return $this->icon;
	}

	/**
	 * @return string|null
	 */
	public function getCssClass(): ?string {
		return $this->cssClass;
	}

	/**
	 * @return bool
	 */
	public function isDisabled(): bool {
		return $this->disable;
	}

	/**
	 * @return string|null
	 */
	public function getId(): ?string {
		return $this->id;
	}

	/**
	 * @return void
	 */
	public function render(): void {
		$cssClass = ($this->getCssClass() === null || $this->getCssClass() === 'null') ? "class=\"btn btn-outline btn-primary\" " : "class=\"" . $this->getCssClass() . "\"";
		echo "<a " . $cssClass . " href=\"" . $this->link?->toString() . "\" ";
		if ($this->disable) {
			echo ' disabled ';
		}

		if ($this->id !== null) {
			echo "id = \"{$this->id}\"";
		}

		if ($this->link?->getTarget() !== '' && $this->link?->getTarget() !== null) {
			echo $this->link->getTarget();
		}

		if ($this->confirmMessage !== null && $this->confirmMessage !== '') {
			$confirmMessage = "onclick=\"return confirm('" . $this->confirmMessage . "');\"";
		} else {
			$confirmMessage = '';
		}

		foreach ($this->dataAttr as $name => $value) {
			echo " data-{$name}='$value' ";
		}

		echo "title=\"" . $this->title . "\" " . $confirmMessage . ">";
		echo "<i class=\"fa " . $this->icon . "\"></i> ";
		if ($this->title) {
			echo "<span class=\"title\" >" . $this->title . "</span>\n";
		}
		echo "</a>\n";
	}
}
