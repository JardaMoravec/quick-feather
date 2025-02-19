<?php
namespace QuickFeather\Html\Form\Css;

class FormCSSClass {
    public array $box;
    public array $label;
    public array $input;

    public function __construct(array $box = ['form-group mb-3'], array $label = ['form-label c-blue mb-0'], array $input = ['form-control form-control-sm']) {
        $this->box = $box;
        $this->label = $label;
        $this->input = $input;
    }

    /**
     * Přepíše výchozí hodnoty pro box.
     *
     * @param array $box
     * @return void
     */
    public function setBox(array $box): void {
        $this->box = $box;
    }

    /**
     * Přepíše výchozí hodnoty pro label.
     *
     * @param array $label
     * @return void
     */
    public function setLabel(array $label): void {
        $this->label = $label;
    }

    /**
     * Přepíše výchozí hodnoty pro input.
     *
     * @param array $input
     * @return void
     */
    public function setInput(array $input): void {
        $this->input = $input;
    }

    /**
     * Přidá položky do pole box.
     *
     * @param string $cssClass
     * @return void
     */
    public function addBox(string $cssClass): void {
        $this->box[] = $cssClass;
    }

    /**
     * Přidá položky do pole label.
     *
     * @param string $cssClass
     * @return void
     */
    public function addLabel(string $cssClass): void {
        $this->label[] = $cssClass;
    }

    /**
     * Přidá položky do pole input.
     *
     * @param string $cssClass
     * @return void
     */
    public function addInput(string $cssClass): void {
        $this->input[] = $cssClass;
    }
}
