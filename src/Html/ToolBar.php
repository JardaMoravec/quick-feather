<?php

namespace QuickFeather\Html;

use Exception;
use QuickFeather\Html\Button\BaseButton;


class ToolBar {

	private array $titles = [];
	private array $buttons = [];
	private ?string $icon;
	private string $separatorIcon = "fa fa-long-arrow-right";

	/**
	 * @param string|null $icon
	 * @param string|null $separatorIcon
	 */
	public function __construct(?string $icon = null, ?string $separatorIcon = null) {
		$this->icon = $icon;
		if ($separatorIcon !== null && $separatorIcon !== "") {
			$this->separatorIcon = $separatorIcon;
		}
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function create(): void {
		if (($this->icon !== null && $this->icon !== '') || count($this->titles) > 0) {
			echo "<div class=\"col-12\">";
			echo "<h3 class=\"mx-0 my-2 c-orange\">";
			if ($this->icon) {
				echo "<i class=\"fa " . $this->icon . "\"></i> ";
			}
			echo implode(" <i class=\"{$this->separatorIcon}\"></i>\n", $this->titles);
			echo "</h3>";
			echo "</div>";
		}
		if (count($this->buttons) > 0) {
			echo "<div class=\"col-12 pb-3\">";
			foreach ($this->buttons as $button) {
				if ($button instanceof BaseButton) {
					$button->render();
				}
				if (is_callable($button)) {
					$button();
				}
			}
			echo "</div>";
		}
	}

	/**
	 * @param \QuickFeather\Html\Button\BaseButton $button
	 * @return void
	 */
	public function addButton(BaseButton $button): void {
		$this->buttons[] = $button;
	}

	/**
	 * @param string $title
	 * @return void
	 */
	public function addTitle(string $title): void {
		$this->titles[] = $title;
	}

	/**
	 * @return array
	 */
	public function getTitles(): array {
		return $this->titles;
	}

	/**
	 * @return array
	 */
	public function getButtons(): array {
		return $this->buttons;
	}

	/**
	 * @return string
	 */
	public function getSeparatorIcon(): string {
		return $this->separatorIcon;
	}

	/**
	 * @return string|null
	 */
	public function getIcon(): ?string {
		return $this->icon;
	}
}
