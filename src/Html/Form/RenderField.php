<?php

namespace QuickFeather\Html\Form;


use QuickFeather\Context;
use QuickFeather\EntityManager\IEntity;
use QuickFeather\EntityManager\ISelectUsable;
use QuickFeather\Html\Form\Css\FormCSSClass;
use RuntimeException;
use Service\Upload\ImageManager;

abstract class RenderField extends RenderCommon {
	public const TYPE_WYSIWYG = 1;
	public const TYPE_WYSIWYG_SIMPLE = 2;
	public const TYPE_EMOJI = 3;

	/**
	 * @param string $name
	 * @param string|null $elementId
	 * @param bool|null $require
	 * @param bool|null $readOnly
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $attributes
	 * @param array|null $dataAttributes
	 * @param array|null $events
	 * @param string|null $type
	 * @param int|null $length
	 * @param string|null $placeholder
	 * @param mixed|null $value
	 * @return string
	 */
	public static function renderInput(string            $name,
									   string|null       $elementId = null,
									   bool|null         $require = false,
									   bool|null         $readOnly = false,
									   FormCSSClass|null $cssClass = null,
									   array|null        $attributes = [],
									   array|null        $dataAttributes = [],
									   array|null        $events = [],
									   string|null       $type = 'text',
									   int|null          $length = null,
									   string|null       $placeholder = null,
									   mixed             $value = null
	): string {
		if ($elementId !== null) {
			$attributes['id'] = $elementId;
		}
		if ($length !== null && $length > 0) {
			$attributes['maxlength'] = $length;
		}
		if ($placeholder !== null && $placeholder !== '') {
			$attributes['placeholder'] = $placeholder;
		}
		$attributes['type'] = $type ?? 'text';

		$str = "<input name=\"" . self::createName($name) . "\" value=\"$value\" ";

		$str .= self::createCssClass($cssClass?->input);
		$str .= self::createDisable($readOnly);
		$str .= self::createRequire($require);
		$str .= self::createAttributes($attributes);
		$str .= self::createDataAttributes($dataAttributes);
		$str .= self::createEvents($events);


		$str .= "/>\n";
		return $str;
	}

	/**
	 * @param string|null $name
	 * @param string|null $elementId
	 * @param bool|null $require
	 * @param bool|null $readOnly
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $attributes
	 * @param array|null $dataAttributes
	 * @param array|null $events
	 * @param int|null $length
	 * @param int|null $rowCount
	 * @param int|null $columnCount
	 * @param int|null $extension
	 * @param mixed|null $value
	 * @return string
	 */
	public static function renderTextarea(string|null       $name,
										  string|null       $elementId = null,
										  bool|null         $require = false,
										  bool|null         $readOnly = false,
										  FormCSSClass|null $cssClass = null,
										  array|null        $attributes = [],
										  array|null        $dataAttributes = [],
										  array|null        $events = [],
										  int|null          $length = null,
										  int|null          $rowCount = null,
										  int|null          $columnCount = null,
										  int|null          $extension = null,
										  mixed             $value = null
	): string {
		$str = '';
		if ($cssClass === null) {
			$cssClass = new FormCSSClass();
		}

		if ($extension === self::TYPE_WYSIWYG) {
			$cssClass->input[] = 'logictype-wysiwyg';
		} else if ($extension === self::TYPE_WYSIWYG_SIMPLE) {
			$cssClass->input[] = 'logictype-wysiwyg-simple';
		} else if ($extension === self::TYPE_EMOJI) {
			$str .= "<div data-emojiarea data-type=\"unicode\" data-global-picker=\"false\">";
		}

		$str .= "<textarea name=\"" . self::createName($name) . "\" ";

		if ($elementId !== null) {
			$attributes['id'] = $elementId;
		}
		if ($length !== null && $length > 0) {
			$attributes['maxlength'] = $length;
		}

		$attributes['rows'] = $rowCount ?? 5;
		$attributes['cols'] = $columnCount ?? 30;

		$str .= self::createCssClass($cssClass->input);
		$str .= self::createDisable($readOnly);
		$str .= self::createRequire($require);
		$str .= self::createAttributes($attributes);
		$str .= self::createDataAttributes($dataAttributes);
		$str .= self::createEvents($events);

		$str .= ">\n";

		$str .= $value;

		$str .= "</textarea>\n";

		if ($extension === self::TYPE_WYSIWYG || $extension === self::TYPE_WYSIWYG_SIMPLE) {
			$str .= "<script>CKEDITOR.replace( '{$elementId}', {language: 'cs'} );</script>\n";
		} else if ($extension === self::TYPE_EMOJI) {
			$str .= "<div class=\"emoji-button fs-5\">&#x1f604;</div>";
			$str .= "</div>";
		}
		return $str;
	}

	/**
	 * @param string $name
	 * @param string|null $elementId
	 * @param bool|null $require
	 * @param bool|null $readOnly
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $attributes
	 * @param array|null $dataAttributes
	 * @param array|null $events
	 * @param array|null $options
	 * @param int|null $size
	 * @param bool|null $multiple
	 * @param mixed|null $value
	 * @return string

	 */
	public static function renderSelect(string            $name,
										string|null       $elementId = null,
										bool|null         $require = false,
										bool|null         $readOnly = false,
										FormCSSClass|null $cssClass = null,
										array|null        $attributes = [],
										array|null        $dataAttributes = [],
										array|null        $events = [],
										array|null        $options = [],
										int|null          $size = 0,
										bool|null         $multiple = false,
										mixed             $value = null
	): string {
		if ($cssClass === null) {
			$cssClass = new FormCSSClass();
		}

		$value = ($value === true || $value === 'true') ? 't' : $value;
		$value = ($value === false || $value === 'false') ? 'f' : $value;

		$str = "<select name=\"" . self::createName($name) . ($multiple ? '[]': '') . "\" ";

		if ($elementId !== null && $elementId !== '') {
			$attributes['id'] = $elementId;
		}
		if ($size !== null && $size > 0) {
			$attributes['size'] = $size;
		}

		$str .= match ($multiple) {
			true => " multiple=\"multiple\" ",
			default => '',
		};

		$str .= self::createCssClass($cssClass->input);
		$str .= self::createDisable($readOnly);
		$str .= self::createRequire($require);
		$str .= self::createAttributes($attributes);
		$str .= self::createDataAttributes($dataAttributes);
		$str .= self::createEvents($events);

		$str .= ">\n";

		if (count($options) > 0) {
			foreach ($options as $opt) {
				if ($opt instanceof ISelectUsable) {
					$opt = $opt->toSelectOption();
				}
				if (!is_array($opt)) {
					throw new RuntimeException('False option format!');
				}
				$str .= "<option value=\"" . $opt["id"] . "\"";
				if ($multiple && is_array($value)) {
					if (in_array($opt["id"], $value)) {
						$str .= " selected=\"selected\"";
					}
				} else if (is_array($value)) {
					if ($opt["id"] == $value[count($value) - 1]['id']) {
						$str .= " selected=\"selected\"";
					}
				} else if ($value instanceof IEntity) {
					if ($opt["id"] == (string)$value) {
						$str .= " selected=\"selected\"";
					}
				} else if ($opt["id"] == $value) {
					$str .= " selected=\"selected\"";
				}
				$str .= ">" . $opt["name"] . "</option>\n";
			}
		}
		$str .= "</select>\n";
		return $str;
	}

	/**
	 * @param string $name
	 * @param string|null $elementId
	 * @param bool|null $readOnly
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $attributes
	 * @param array|null $dataAttributes
	 * @param array|null $events
	 * @param mixed|null $value
	 * @return string
	 */
	public static function renderCheckBox(string            $name,
										  string|null       $elementId = null,
										  bool|null         $readOnly = false,
										  FormCSSClass|null $cssClass = null,
										  array|null        $attributes = [],
										  array|null        $dataAttributes = [],
										  array|null        $events = [],
										  mixed             $value = null
	): string {
		if ($cssClass === null) {
			$cssClass = new FormCSSClass();
		}

		$str = "<input name=\"" . self::createName($name) . "\" type=\"checkbox\" value=\"true\" ";

		if ($elementId !== null && $elementId !== '') {
			$attributes['id'] = $elementId;
		}

		$str .= match ($value) {
			true => " checked=\"checked\" ",
			default => '',
		};

		$cssClass->input[] = 'form-check-input';
		$str .= self::createCssClass($cssClass->input);
		$str .= self::createDisable($readOnly);
		$str .= self::createAttributes($attributes);
		$str .= self::createDataAttributes($dataAttributes);
		$str .= self::createEvents($events);
		$str .= "/>\n";
		return $str;
	}

	/**
	 * @param string $name
	 * @param string|null $elementId
	 * @param bool|null $readOnly
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $attributes
	 * @param array|null $dataAttributes
	 * @param array|null $events
	 * @param array|null $options
	 * @param bool|null $showAllOption
	 * @param mixed|null $value
	 * @return string

	 */
	public static function renderCheckBoxList(string            $name,
											  string|null       $elementId = null,
											  bool|null         $readOnly = false,
											  FormCSSClass|null $cssClass = null,
											  array|null        $attributes = [],
											  array|null        $dataAttributes = [],
											  array|null        $events = [],
											  array|null        $options = [],
											  bool|null         $showAllOption = true,
											  mixed             $value = null
	): string {
		if ($cssClass === null) {
			$cssClass = new FormCSSClass();
		}

		$inputName = self::createName($name);

		$str = '';
		if (count($options) > 6) {
			$str .= "<div class='checkbox-list scrollable'>";
		} else {
			$str .= "<div class='checkbox-list'>";
		}


		if ($showAllOption) {
			$allClass = clone $cssClass;
			$allClass->addInput("check-all");

			$str .= "<div " . self::createCssClass($cssClass->box) . ">";
			$str .= "<label " . self::createCssClass($cssClass->label) . ">";
			$str .= "<input type=\"checkbox\" id=\"" . $inputName . "_all\" title=\"" . _('Označit všechny') . "\" value=\"all\" name=\"" . $inputName . "_all\" " . self::createCssClass($allClass->input) . " />";
			$str .= _('Označit všechny') . "</label>";
			$str .= "</div>\n";
		}

		foreach ($options as $option) {
			if (is_array($option)) {
				$id = $elementId . '_' . $option["id"];
			} else if ($option instanceof ISelectUsable) {
				$option = $option->toSelectOption();
				$id = $elementId . '_' . $option["id"];
			} else {
				throw new RuntimeException('Field options error!');
			}

			if (!isset($option['id'])) {
				throw new RuntimeException('Input array has invalid format! [empty ID value]');
			}
			if (!isset($option['name'])) {
				throw new RuntimeException('Input array has invalid format! [empty NAME value]');
			}

			$str .= "<div " . self::createCssClass($cssClass->box) . ">";
			$str .= "<label for=\"{$id}\" " . self::createCssClass($cssClass->label) . ">";
			$str .= "<input name=\"{$inputName}[]\" type=\"checkbox\" id=\"{$id}\" ";

			if (is_array($value) && in_array($option["id"], $value)) {
				$str .= " checked='checked' ";
			}

			$str .= self::createCssClass($cssClass->input);
			$str .= self::createDisable($readOnly);
			$str .= self::createAttributes($attributes);
			$str .= self::createDataAttributes($dataAttributes);
			$str .= self::createEvents($events);

			$str .= " value=\"{$option["id"]}\"/>{$option["name"]}\n";
			$str .= "</label>";
			$str .= "</div>\n";
		}
		$str .= "</div>";

		return $str;
	}

	/**
	 * @param string $name
	 * @param string|null $elementId
	 * @param bool|null $readOnly
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $attributes
	 * @param array|null $dataAttributes
	 * @param array|null $events
	 * @param bool|null $activeDelete
	 * @param bool|null $preview
	 * @param string|null $folder
	 * @param Context|null $context
	 * @param mixed|null $value
	 * @return string
	 */
	public static function renderImageUpload(string            $name,
											 string|null       $elementId = null,
											 bool|null         $readOnly = false,
											 FormCSSClass|null $cssClass = null,
											 array|null        $attributes = [],
											 array|null        $dataAttributes = [],
											 array|null        $events = [],
											 ?bool             $activeDelete = false,
											 ?bool             $preview = true,
											 ?string           $folder = null,
											 ?Context          $context = null,
											 mixed             $value = null
	): string {
		if ($cssClass === null) {
			$cssClass = new FormCSSClass();
		}

		$str = "<div class=\"image-upload-box\">\n";

		if ($preview && $folder && $context) {
			$imagePreview = $value != '' ? ImageManager::getImagePathBySize($context, $context->config->getBaseUrl() . $folder, $value, 4) : '';
			$str .= "<div class=\"image\" style=\"background-image: url('" . $imagePreview . "');\" title=\"" . $value . "\" ></div>\n";
		}
		$str .= "<input name=\"" . $name . "\" " . $elementId . " type=\"file\" ";
		$str .= self::createCssClass($cssClass->input);
		$str .= self::createDisable($readOnly);
		$str .= self::createAttributes($attributes);
		$str .= self::createDataAttributes($dataAttributes);
		$str .= self::createEvents($events);
		$str .= " />\n";

		if ($activeDelete) {
			$str .= " nebo ";
			$str .= "<label class='delete'>";
			$str .= "<input name=\"" . $name . "_delete\" type=\"checkbox\" value=\"delete\" class=\"delete\" title=\"" . _('Smazat obrázek') .
				"\" " . self::createDisable($readOnly) . "/>\n";
			$str .= _('Smazat') . "</label>\n";
		}
		$str .= "</div>\n";
		return $str;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private static function createName(string $name): string {
		return $name;
	}


	/**
	 * @param bool $disable
	 * @return string
	 */
	private static function createDisable(bool $disable): string {
		return match ($disable) {
			true => " disabled ",
			default => '',
		};
	}

	/**
	 * @param bool $require
	 * @return string
	 */
	protected static function createRequire(bool $require): string {
		if ($require) {
			return ' required ';
		}
		return '';
	}
}
